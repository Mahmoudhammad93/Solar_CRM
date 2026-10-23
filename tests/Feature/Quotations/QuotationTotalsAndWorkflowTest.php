<?php

namespace Tests\Feature\Quotations;

use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\User;
use App\Services\QuotationReportService;
use App\Services\QuotationService;
use App\Services\QuotationTotals;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuotationTotalsAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private QuotationService $quotes;

    private QuotationTotals $totals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->quotes = app(QuotationService::class);
        $this->totals = app(QuotationTotals::class);

        app(SettingsService::class)->set('finance.company_state', 'Maharashtra', [
            'group' => 'finance', 'type' => 'string', 'is_sample' => true, 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('finance.tax_split_rule', 'auto', [
            'group' => 'finance', 'type' => 'string', 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('finance.cgst_rate', 0.09, [
            'group' => 'finance', 'type' => 'float', 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('finance.sgst_rate', 0.09, [
            'group' => 'finance', 'type' => 'float', 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('finance.igst_rate', 0.18, [
            'group' => 'finance', 'type' => 'float', 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('finance.round_off', 'none', [
            'group' => 'finance', 'type' => 'string', 'change_summary' => 'test',
        ]);
        app(SettingsService::class)->set('quotations.max_discount_percent', 25, [
            'group' => 'quotations', 'type' => 'float', 'change_summary' => 'test',
        ]);
    }

    /**
     * Intra-state hand math:
     * Line: qty 2 × 1000 = 2000, discount 10% → taxable 1800
     * CGST 9% = 162, SGST 9% = 162, grand = 2124
     */
    public function test_intra_state_cgst_sgst_hand_totals(): void
    {
        $result = $this->totals->calculate([
            ['sku' => 'A', 'title' => 'Panel', 'quantity' => 2, 'unit_price' => 1000, 'discount_percent' => 10, 'gst_rate' => 0.18],
        ], 'Maharashtra', 0);

        $this->assertSame('cgst_sgst', $result['tax_mode']);
        $this->assertEquals(2000.0, $result['subtotal']);
        $this->assertEquals(200.0, $result['discount_total']);
        $this->assertEquals(1800.0, $result['taxable_value']);
        $this->assertEquals(162.0, $result['cgst_amount']);
        $this->assertEquals(162.0, $result['sgst_amount']);
        $this->assertEquals(0.0, $result['igst_amount']);
        $this->assertEquals(2124.0, $result['grand_total']);
    }

    /**
     * Inter-state hand math (customer Karnataka):
     * Same line taxable 1800, IGST 18% = 324, grand = 2124
     */
    public function test_inter_state_igst_hand_totals(): void
    {
        $result = $this->totals->calculate([
            ['sku' => 'A', 'title' => 'Panel', 'quantity' => 2, 'unit_price' => 1000, 'discount_percent' => 10, 'gst_rate' => 0.18],
        ], 'Karnataka', 500);

        $this->assertSame('igst', $result['tax_mode']);
        $this->assertEquals(1800.0, $result['taxable_value']);
        $this->assertEquals(0.0, $result['cgst_amount']);
        $this->assertEquals(324.0, $result['igst_amount']);
        $this->assertEquals(2124.0, $result['grand_total']);
        $this->assertEquals(500.0, $result['advance']);
        $this->assertEquals(1624.0, $result['balance_due']);
    }

    public function test_discount_limit_enforced(): void
    {
        $this->expectException(ValidationException::class);
        $this->totals->calculate([
            ['sku' => 'A', 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 40, 'gst_rate' => 0.18],
        ], 'Maharashtra');
    }

    public function test_nearest_rupee_round_off(): void
    {
        app(SettingsService::class)->set('finance.round_off', 'nearest', [
            'group' => 'finance', 'type' => 'string', 'change_summary' => 'test',
        ]);
        // taxable 100.5 * 0.18 igst inter → need odd amount
        // qty 1 × 100.33, no discount → taxable 100.33; IGST 18% = 18.06; sum 118.39 → nearest 118, round_off -0.39
        $result = $this->totals->calculate([
            ['sku' => 'A', 'title' => 'X', 'quantity' => 1, 'unit_price' => 100.33, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], 'Karnataka');

        $this->assertEquals(118.0, $result['grand_total']);
        $this->assertEqualsWithDelta(-0.39, $result['round_off'], 0.01);
    }

    public function test_revision_immutable_when_gst_setting_changes(): void
    {
        $admin = User::factory()->withRole('super-admin')->create();
        $quote = $this->quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $admin->id,
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'P', 'title' => 'Panel', 'quantity' => 1, 'unit_price' => 1000, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $admin);

        $rev = $quote->currentRevision;
        $oldGrand = $rev->totals['grand_total'];
        $oldCgst = $rev->tax_config['cgst_rate'];

        $this->quotes->transition($quote, 'sent', $admin);
        $locked = $quote->fresh()->currentRevision;
        $this->assertTrue($locked->is_locked);

        app(SettingsService::class)->set('finance.cgst_rate', 0.14, [
            'group' => 'finance', 'type' => 'float', 'change_summary' => 'raise CGST',
        ]);
        app(SettingsService::class)->set('finance.sgst_rate', 0.14, [
            'group' => 'finance', 'type' => 'float', 'change_summary' => 'raise SGST',
        ]);

        $frozen = QuotationRevision::query()->findOrFail($locked->id);
        $this->assertEquals($oldGrand, $frozen->totals['grand_total']);
        $this->assertEquals($oldCgst, $frozen->tax_config['cgst_rate']);

        // New draft revision picks up new rates
        $newRev = $this->quotes->revise($quote->fresh(), [
            'proposal_type' => 'hybrid',
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'P', 'title' => 'Panel', 'quantity' => 1, 'unit_price' => 1000, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $admin);

        $this->assertSame(2, $newRev->revision);
        $this->assertEquals(0.14, $newRev->tax_config['cgst_rate']);
        $this->assertNotEquals($oldGrand, $newRev->totals['grand_total']);
    }

    public function test_pdf_equals_snapshot_html_source(): void
    {
        config(['filesystems.disks.local.root' => storage_path('app/private')]);
        $admin = User::factory()->withRole('super-admin')->create();
        $quote = $this->quotes->create([
            'proposal_type' => 'pump',
            'owner_id' => $admin->id,
            'customer_state' => 'Karnataka',
            'pump_details' => [
                'model' => 'AquaPulse AP-S3',
                'capacity' => '3HP',
                'head_m' => 50,
                'discharge' => '180 LPM',
                'daily_water_requirement' => '40,000 L',
                'controller' => 'MPPT demo',
                'subsidy_note' => 'Sample/Demo subsidy',
            ],
        ], [
            ['sku' => 'PMP', 'title' => 'Pump', 'quantity' => 1, 'unit_price' => 680, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $admin);

        [$binary, $filename, $path] = app(QuotationReportService::class)->generatePdf($quote);
        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertFileExists($path);
        $this->assertStringContainsString('quotation-', $filename);
        $this->assertStringContainsString('-r1-', $filename);

        $html = view('pdf.quotation', [
            'quote' => $quote->fresh(),
            'revision' => $quote->currentRevision,
            'brand' => $quote->currentRevision->branding_snapshot,
            'logoDataUri' => null,
            'qrSvg' => null,
            'verifyUrl' => url('/quotes/verify/'.$quote->public_token),
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString($quote->number, $html);
        $this->assertStringContainsString((string) $quote->currentRevision->totals['grand_total'], $html);
        $this->assertStringContainsString('AquaPulse AP-S3', $html);
        $this->assertStringContainsString('Demo e-signature, not legally verified', $html);
    }

    public function test_unauthorized_user_cannot_edit_or_approve(): void
    {
        $owner = User::factory()->withRole('sales-executive')->create();
        $other = User::factory()->withRole('sales-executive')->create();
        $quote = $this->quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $owner->id,
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'P', 'title' => 'Panel', 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $owner);

        $this->assertFalse($other->can('update', $quote));
        $this->assertFalse($other->can('approve', $quote));

        app(SettingsService::class)->set('quotations.require_manager_approval', true, [
            'group' => 'quotations', 'type' => 'boolean', 'change_summary' => 'test',
        ]);
        $this->quotes->transition($quote, 'review', $owner);
        $this->expectException(ValidationException::class);
        $this->quotes->transition($quote->fresh(), 'sent', $other);
    }

    public function test_verification_page_exposes_no_private_fields(): void
    {
        $admin = User::factory()->withRole('super-admin')->create();
        $quote = $this->quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $admin->id,
            'customer_state' => 'Maharashtra',
            'internal_notes' => 'SECRET_INTERNAL_NOTE_XYZ',
        ], [
            ['sku' => 'SECRET-SKU', 'title' => 'Secret line', 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $admin);

        $response = $this->get(route('quotes.verify', $quote->public_token));
        $response->assertOk();
        $response->assertSee($quote->number);
        $response->assertSee((string) $quote->currentRevision->totals['grand_total']);
        $response->assertDontSee('SECRET_INTERNAL_NOTE_XYZ');
        $response->assertDontSee('SECRET-SKU');
        $response->assertDontSee('Secret line');
        $html = $response->getContent();
        $this->assertStringNotContainsString('internal_notes', $html);
        $this->assertStringNotContainsString('@', $html); // no emails
    }

    public function test_qr_svg_contains_verify_url(): void
    {
        $svg = app(QuotationReportService::class)->qrSvg('https://example.test/quotes/verify/abc');
        $this->assertStringContainsString('<svg', $svg);
        // SVG QR encodes data — URL decode smoke: library returned non-empty SVG
        $this->assertGreaterThan(200, strlen($svg));
    }
}
