<?php

namespace App\Services;

use App\Models\Installation;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\QuotationActivity;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ProjectMilestones;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertQuotationToProject
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{order: Order, project: Project, created: bool, message: string}
     */
    public function convert(Quotation $quotation, ?User $user = null): array
    {
        return DB::transaction(function () use ($quotation, $user) {
            /** @var Quotation $locked */
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            $existing = Order::query()->where('quotation_id', $locked->id)->first();
            if ($existing) {
                $project = $existing->project ?? Project::query()->where('order_id', $existing->id)->first();

                return [
                    'order' => $existing->fresh(['project', 'quotation']),
                    'project' => $project?->fresh(['order', 'quotation', 'milestones', 'materials']) ?? throw ValidationException::withMessages([
                        'quotation' => 'Order exists without project; contact admin.',
                    ]),
                    'created' => false,
                    'message' => 'Order/project already exists for this quotation; returning existing records.',
                ];
            }

            $revision = $locked->currentRevision;
            if (! $revision || strtolower((string) $revision->status) !== 'accepted') {
                throw ValidationException::withMessages([
                    'quotation' => 'Only Accepted quotations can be converted to an order/project.',
                ]);
            }

            // Copy exclusively from the immutable revision snapshot
            $lines = $revision->lines ?? [];
            $totals = $revision->totals ?? [];
            $schedule = $revision->payment_schedule ?? [];

            try {
                $order = Order::query()->create([
                    'number' => $this->numbering->next('order'),
                    'ordered_on' => now()->toDateString(),
                    'quotation_id' => $locked->id,
                    'quotation_revision_id' => $revision->id,
                    'customer_id' => $locked->customer_id,
                    'owner_id' => $locked->owner_id ?? $user?->id,
                    'site_address' => $revision->site_address,
                    'project_type' => $revision->system_type ?? $locked->proposal_type,
                    'capacity_kw' => $revision->system_capacity_kw,
                    'contract_value' => (float) ($totals['grand_total'] ?? 0),
                    'confirmed_scope' => $revision->template_texts['scope'] ?? $revision->customer_terms,
                    'payment_schedule' => $schedule,
                    'lines_snapshot' => $lines,
                    'totals_snapshot' => $totals,
                    'delivery_target' => now()->addDays((int) $this->settings->get('projects.default_delivery_days', 45))->toDateString(),
                    'status' => 'pending',
                ]);
            } catch (UniqueConstraintViolationException) {
                $existing = Order::query()->where('quotation_id', $locked->id)->firstOrFail();
                $project = $existing->project ?? Project::query()->where('order_id', $existing->id)->firstOrFail();

                return [
                    'order' => $existing->fresh(['project', 'quotation']),
                    'project' => $project->fresh(['order', 'quotation', 'milestones', 'materials']),
                    'created' => false,
                    'message' => 'Order/project already exists for this quotation; returning existing records.',
                ];
            }

            $slaDays = (int) $this->settings->get('projects.milestone_sla_days', 7);

            $project = Project::query()->create([
                'number' => $this->numbering->next('project'),
                'order_id' => $order->id,
                'quotation_id' => $locked->id,
                'customer_id' => $locked->customer_id,
                'manager_id' => $user?->id,
                'sales_user_id' => $locked->owner_id,
                'planned_start' => now()->toDateString(),
                'planned_end' => now()->addDays((int) $this->settings->get('projects.default_duration_days', 60))->toDateString(),
                'priority' => 'medium',
                'current_milestone_key' => 'order_confirmed',
                'percent_complete' => 0,
                'customer_updates' => [],
                'status' => 'active',
            ]);

            $order->update(['project_id' => $project->id, 'status' => 'active']);

            foreach (ProjectMilestones::defaults() as $i => $ms) {
                ProjectMilestone::query()->create([
                    'project_id' => $project->id,
                    'milestone_key' => $ms['key'],
                    'label' => $ms['label'],
                    'sort_order' => $i,
                    'weight' => $ms['weight'],
                    'status' => $i === 0 ? 'active' : 'pending',
                    'started_at' => $i === 0 ? now() : null,
                    'due_at' => now()->addDays($slaDays * ($i + 1)),
                    'changed_by' => $user?->id,
                ]);
            }

            $defaultWh = Warehouse::query()->where('is_active', true)->orderBy('id')->value('id');

            foreach ($lines as $line) {
                $isService = in_array(($line['line_type'] ?? ''), ['service', 'installation', 'transport', 'services'], true)
                    || str_starts_with((string) ($line['sku'] ?? ''), 'SRV-');

                ProjectMaterial::query()->create([
                    'project_id' => $project->id,
                    'product_id' => $line['product_id'] ?? null,
                    'sku' => $line['sku'] ?? null,
                    'title' => $line['title'] ?? $line['description'] ?? 'Line',
                    'unit' => $line['unit'] ?? 'pcs',
                    'quantity_required' => (float) ($line['quantity'] ?? 0),
                    'warehouse_id' => $isService ? null : $defaultWh,
                    'is_service' => $isService,
                ]);
            }

            Installation::query()->create([
                'project_id' => $project->id,
                'checklist' => [
                    ['item' => 'Site safety briefing', 'done' => false],
                    ['item' => 'Structure fixed', 'done' => false],
                    ['item' => 'Modules installed', 'done' => false],
                    ['item' => 'Electrical terminated', 'done' => false],
                    ['item' => 'Commissioning complete', 'done' => false],
                ],
                'status' => 'planned',
            ]);

            QuotationActivity::query()->create([
                'quotation_id' => $locked->id,
                'revision_id' => $revision->id,
                'action' => 'converted_to_project',
                'from_status' => $revision->status,
                'to_status' => $revision->status,
                'notes' => 'Created order '.$order->number.' / project '.$project->number,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'quotation.converted',
                entityType: Quotation::class,
                entityId: $locked->id,
                entityReference: $locked->number,
                summary: [
                    'order' => $order->number,
                    'project' => $project->number,
                    'revision' => $revision->revision,
                    'contract_value' => $order->contract_value,
                ],
            );

            return [
                'order' => $order->fresh(['project', 'quotation']),
                'project' => $project->fresh(['order', 'quotation', 'milestones', 'materials', 'installation']),
                'created' => true,
                'message' => 'Order and project created from accepted quotation snapshot.',
            ];
        });
    }
}
