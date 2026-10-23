<?php

namespace App\Services;

use App\Models\CalculationRun;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationActivity;
use App\Models\QuotationRevision;
use App\Models\User;
use App\Support\QuotationWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuotationService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly NumberingService $numbering,
        private readonly QuotationTotals $totals,
        private readonly BrandingService $branding,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(array $header, array $lines, ?User $user = null): Quotation
    {
        return DB::transaction(function () use ($header, $lines, $user) {
            $quote = Quotation::query()->create([
                'number' => $this->numbering->next('quote'),
                'public_token' => Str::random(48),
                'proposal_type' => $header['proposal_type'] ?? 'hybrid',
                'owner_id' => $header['owner_id'] ?? $user?->id,
                'lead_id' => $header['lead_id'] ?? null,
                'customer_id' => $header['customer_id'] ?? null,
                'visit_id' => $header['visit_id'] ?? null,
                'calculation_run_id' => $header['calculation_run_id'] ?? null,
            ]);

            $revision = $this->buildRevision($quote, 1, 'draft', $header, $lines, $user, false);
            $quote->update(['current_revision_id' => $revision->id]);

            $this->logActivity($quote, $revision, 'created', null, 'draft', $user, 'Quotation created');

            return $quote->fresh(['currentRevision', 'revisions']);
        });
    }

    /**
     * Update editable draft/review revision in place.
     *
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function updateDraft(Quotation $quote, array $header, array $lines, ?User $user = null): QuotationRevision
    {
        $revision = $quote->currentRevision;
        if (! $revision || ! $revision->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Current revision is locked. Create a new revision to edit.',
            ]);
        }

        return DB::transaction(function () use ($quote, $revision, $header, $lines, $user) {
            $built = $this->preparePayload($quote, $header, $lines);
            $revision->fill($built);
            $revision->created_by = $revision->created_by ?: $user?->id;
            $revision->save();

            $this->logActivity($quote, $revision, 'updated', $revision->status, $revision->status, $user, 'Draft updated');

            return $revision->fresh();
        });
    }

    /**
     * When current revision is locked, editing creates a new unlocked revision.
     *
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function revise(Quotation $quote, array $header, array $lines, ?User $user = null): QuotationRevision
    {
        return DB::transaction(function () use ($quote, $header, $lines, $user) {
            $next = ((int) $quote->revisions()->max('revision')) + 1;
            $revision = $this->buildRevision($quote, $next, 'draft', $header, $lines, $user, false);
            $quote->update(['current_revision_id' => $revision->id]);
            $this->logActivity($quote, $revision, 'revised', null, 'draft', $user, 'New revision '.$next);

            return $revision;
        });
    }

    public function transition(Quotation $quote, string $toStatus, ?User $user = null, ?string $notes = null): QuotationRevision
    {
        $revision = $quote->currentRevision;
        if (! $revision) {
            throw new RuntimeException('Quotation has no revision.');
        }

        $from = strtolower((string) $revision->status);
        $to = strtolower($toStatus);

        if (! QuotationWorkflow::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "Transition {$from} → {$to} is not allowed.",
            ]);
        }

        $requireApproval = (bool) $this->settings->get('quotations.require_manager_approval', false);
        if ($to === 'sent' && $requireApproval && $from !== 'review') {
            throw ValidationException::withMessages([
                'status' => 'Manager approval required: move to Review before Sent.',
            ]);
        }
        if ($to === 'sent' && $requireApproval && $user && ! $user->isSuperAdmin() && ! $user->hasPermission('quotations.approve')) {
            throw ValidationException::withMessages([
                'status' => 'Only users with quotations.approve may send when approval is required.',
            ]);
        }

        return DB::transaction(function () use ($quote, $revision, $from, $to, $user, $notes) {
            if ($revision->is_locked) {
                throw ValidationException::withMessages(['status' => 'Locked revision cannot change status. Create a new revision.']);
            }

            $revision->status = $to;
            if (QuotationWorkflow::locksRevision($to)) {
                $revision->is_locked = true;
            }
            $revision->save();

            $this->logActivity($quote, $revision, 'status_changed', $from, $to, $user, $notes);
            $this->auditLogger->log(
                action: 'quotation.status_changed',
                entityType: Quotation::class,
                entityId: $quote->id,
                entityReference: $quote->number,
                summary: ['from' => $from, 'to' => $to, 'revision' => $revision->revision],
            );

            return $revision->fresh();
        });
    }

    public function attachSignature(QuotationRevision $revision, string $relativePath, ?User $user = null): QuotationRevision
    {
        if (strtolower((string) $revision->status) !== 'accepted' && strtolower((string) $revision->status) !== 'sent') {
            // Allow capture on accept flow
        }

        $revision->signature_path = $relativePath;
        $revision->signed_at = now();
        $revision->save();

        $this->logActivity($revision->quotation, $revision, 'signature_captured', $revision->status, $revision->status, $user, 'Demo e-signature, not legally verified');

        return $revision->fresh();
    }

    /**
     * Build hybrid lines from a CalculationRun + catalogue match by rough SKU heuristics.
     *
     * @return list<array<string, mixed>>
     */
    public function linesFromCalculation(CalculationRun $run): array
    {
        $o = $run->outputs ?? [];
        $panelCount = (int) ($o['panel_count'] ?? 0);
        $battKwh = (float) ($o['battery_nominal_kwh'] ?? 0);
        $gst = (float) $this->settings->get('calc.hybrid.gst_rate', 0.18);

        $lines = [];
        $panel = Product::query()->where('category', 'solar_panels')->where('is_archived', false)->orderByDesc('sale_price')->first();
        $inverter = Product::query()->where('category', 'hybrid_inverters')->where('is_archived', false)->orderBy('sale_price')->first();
        $battery = Product::query()->where('category', 'batteries')->where('is_archived', false)->orderBy('sale_price')->first();
        $structure = Product::query()->where('category', 'structures')->where('is_archived', false)->first();
        $wiring = Product::query()->where('category', 'wiring_protection')->where('is_archived', false)->first();

        if ($panel && $panelCount > 0) {
            $lines[] = $this->productLine($panel, $panelCount, $gst, 'Panels from hybrid calculation');
        } else {
            $lines[] = $this->manualLine('Solar panels', 'PNL-CALC', $panelCount ?: 1, 'pcs', (float) ($this->settings->get('calc.hybrid.panel_unit_price', 120)), $gst);
        }

        if ($inverter) {
            $lines[] = $this->productLine($inverter, 1, $gst, 'Hybrid inverter');
        } else {
            $lines[] = $this->manualLine('Hybrid inverter', 'INV-CALC', 1, 'pcs', (float) $this->settings->get('calc.hybrid.inverter_unit_price', 590), $gst);
        }

        $battPrice = (float) $this->settings->get('calc.hybrid.battery_price_per_kwh', 250);
        if ($battery && $battKwh > 0) {
            $lines[] = $this->productLine($battery, max(1, (int) ceil($battKwh / max(1, (float) ($battery->specs['capacity_kwh'] ?? $battKwh)))), $gst, 'Battery bank');
        } elseif ($battKwh > 0) {
            $lines[] = $this->manualLine('Battery bank', 'BAT-CALC', 1, 'kWh', $battKwh * $battPrice, $gst);
        }

        if ($structure) {
            $kw = (float) ($o['pv_kwp_recommended'] ?? 1);
            $lines[] = $this->productLine($structure, max(1, (int) ceil($kw)), $gst, 'Mounting structure');
        }
        if ($wiring) {
            $lines[] = $this->productLine($wiring, 1, $gst, 'Wiring / protection');
        }

        $lines[] = $this->manualLine('Installation', 'SRV-INSTALL', 1, 'job', (float) $this->settings->get('calc.hybrid.installation_default', 90), $gst, 'installation');
        $lines[] = $this->manualLine('Transport', 'SRV-TRANSPORT', 1, 'job', (float) $this->settings->get('calc.hybrid.transport_default', 120), $gst, 'transport');

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    private function buildRevision(
        Quotation $quote,
        int $revisionNo,
        string $status,
        array $header,
        array $lines,
        ?User $user,
        bool $locked,
    ): QuotationRevision {
        $payload = $this->preparePayload($quote, $header, $lines);

        return QuotationRevision::query()->create(array_merge($payload, [
            'quotation_id' => $quote->id,
            'revision' => $revisionNo,
            'status' => $status,
            'is_locked' => $locked,
            'created_by' => $user?->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function preparePayload(Quotation $quote, array $header, array $lines): array
    {
        $customerState = $header['customer_state'] ?? $quote->customer?->state;
        $advance = (float) ($header['advance_amount'] ?? 0);
        $computed = $this->totals->calculate($lines, $customerState, $advance);

        $validityDays = (int) $this->settings->get('quotations.validity_days', 15);

        return [
            'valid_until' => $header['valid_until'] ?? now()->addDays($validityDays)->toDateString(),
            'system_type' => $header['system_type'] ?? ($quote->proposal_type === 'pump' ? 'solar_water_pump' : 'hybrid'),
            'system_capacity_kw' => $header['system_capacity_kw'] ?? null,
            'site_address' => $header['site_address'] ?? null,
            'customer_state' => $customerState,
            'internal_notes' => $header['internal_notes'] ?? null,
            'customer_terms' => $header['customer_terms'] ?? $this->settings->get('quotations.default_terms', 'Standard terms apply. Indicative estimate.'),
            'payment_schedule' => $header['payment_schedule'] ?? [
                ['milestone' => 'Advance', 'percent' => 30],
                ['milestone' => 'Delivery', 'percent' => 40],
                ['milestone' => 'Commissioning', 'percent' => 30],
            ],
            'advance_amount' => $computed['advance'],
            'pump_details' => $header['pump_details'] ?? null,
            'hybrid_assumptions' => $header['hybrid_assumptions'] ?? null,
            'lines' => $computed['lines'],
            'totals' => $computed,
            'tax_config' => [
                'rule' => $this->settings->get('finance.tax_split_rule', 'auto'),
                'cgst_rate' => $computed['cgst_rate'],
                'sgst_rate' => $computed['sgst_rate'],
                'igst_rate' => $computed['igst_rate'],
                'company_state' => $computed['company_state'],
                'customer_state' => $computed['customer_state'],
                'is_sample' => true,
                'source_reference' => 'Settings finance.* Sample/Demo',
            ],
            'branding_snapshot' => $this->branding->pdfSnapshot(),
            'template_texts' => [
                'scope' => $this->settings->get('quotations.template_scope', 'Supply and installation of the proposed system as per line items.'),
                'exclusions' => $this->settings->get('quotations.template_exclusions', 'Civil works beyond listed structure, utility liaison fees, and unforeseen site constraints.'),
                'commissioning' => $this->settings->get('quotations.template_commissioning', 'Commissioning and handover checklist on completion.'),
                'warranty' => $this->settings->get('quotations.template_warranty', 'Manufacturer warranties as per product datasheets; workmanship warranty per company policy.'),
                'maintenance' => $this->settings->get('quotations.template_maintenance', 'Optional AMC available separately.'),
                'validity' => $this->settings->get('quotations.template_validity', 'Prices valid until the validity date on this quotation.'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productLine(Product $product, float $qty, float $gst, string $note = ''): array
    {
        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'description' => trim(($product->description ?? '').($note ? " — {$note}" : '')),
            'quantity' => $qty,
            'unit' => $product->unit ?? 'pcs',
            'unit_price' => (float) $product->sale_price,
            'discount_percent' => 0,
            'gst_rate' => $gst,
            'line_type' => $product->category,
            'stock_available' => $product->availableStock(),
            'stock_indicator' => $product->availableStock() >= $qty ? 'in_stock' : 'low_or_short',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manualLine(
        string $title,
        string $sku,
        float $qty,
        string $unit,
        float $price,
        float $gst,
        string $type = 'service',
    ): array {
        return [
            'product_id' => null,
            'sku' => $sku,
            'title' => $title,
            'description' => $title,
            'quantity' => $qty,
            'unit' => $unit,
            'unit_price' => $price,
            'discount_percent' => 0,
            'gst_rate' => $gst,
            'line_type' => $type,
            'stock_available' => null,
            'stock_indicator' => 'n/a',
        ];
    }

    private function logActivity(
        Quotation $quote,
        ?QuotationRevision $revision,
        string $action,
        ?string $from,
        ?string $to,
        ?User $user,
        ?string $notes,
    ): void {
        QuotationActivity::query()->create([
            'quotation_id' => $quote->id,
            'revision_id' => $revision?->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
            'user_id' => $user?->id,
            'created_at' => now(),
        ]);
    }
}
