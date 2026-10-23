<?php

namespace Tests\Feature\Orders;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ConvertQuotationToProject;
use App\Services\NumberingService;
use App\Services\QuotationService;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConvertQuotationToProjectTest extends TestCase
{
    use RefreshDatabase;

    private QuotationService $quotes;

    private ConvertQuotationToProject $converter;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->quotes = app(QuotationService::class);
        $this->converter = app(ConvertQuotationToProject::class);
        $this->admin = User::factory()->withRole('super-admin')->create();

        app(SettingsService::class)->set('finance.company_state', 'Maharashtra', [
            'group' => 'finance', 'type' => 'string', 'change_summary' => 'test',
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
    }

    private function acceptQuote(array $headerExtra = [], array $lines = null): Quotation
    {
        $customer = Customer::query()->create([
            'customer_number' => app(NumberingService::class)->next('customer'),
            'name' => 'Convert Test Customer',
            'mobile' => '9000000001',
            'state' => 'Maharashtra',
            'created_by' => $this->admin->id,
        ]);

        $product = Product::query()->create([
            'reference' => app(NumberingService::class)->next('product'),
            'sku' => 'CONV-PANEL-1',
            'category' => 'solar_panels',
            'brand' => 'HelioCraft',
            'title' => 'Convert Panel',
            'unit' => 'pcs',
            'purchase_cost' => 100,
            'sale_price' => 1000,
            'gst_rate' => 18,
            'status' => 'active',
        ]);

        Warehouse::query()->firstOrCreate(
            ['code' => 'CONV'],
            ['name' => 'Convert WH', 'is_active' => true],
        );

        $lines ??= [
            [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'title' => $product->title,
                'quantity' => 4,
                'unit_price' => 1000,
                'discount_percent' => 0,
                'gst_rate' => 0.18,
                'unit' => 'pcs',
            ],
            [
                'sku' => 'SRV-INSTALL',
                'title' => 'Installation',
                'line_type' => 'service',
                'quantity' => 1,
                'unit_price' => 5000,
                'discount_percent' => 0,
                'gst_rate' => 0.18,
                'unit' => 'job',
            ],
        ];

        $quote = $this->quotes->create(array_merge([
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
            'customer_id' => $customer->id,
            'customer_state' => 'Maharashtra',
            'site_address' => '12 Solar Lane, Pune',
            'system_type' => 'hybrid',
            'system_capacity_kw' => 5.5,
            'payment_schedule' => [
                ['label' => 'Advance', 'percent' => 30],
                ['label' => 'Delivery', 'percent' => 40],
                ['label' => 'Handover', 'percent' => 30],
            ],
        ], $headerExtra), $lines, $this->admin);

        $this->quotes->transition($quote, 'sent', $this->admin);
        $this->quotes->transition($quote->fresh(), 'accepted', $this->admin);

        return $quote->fresh(['currentRevision']);
    }

    public function test_conversion_copies_snapshot_and_links_both_ways(): void
    {
        $quote = $this->acceptQuote();
        $rev = $quote->currentRevision;

        $result = $this->converter->convert($quote, $this->admin);
        $this->assertTrue($result['created']);

        $order = $result['order'];
        $project = $result['project'];

        $this->assertSame($quote->id, $order->quotation_id);
        $this->assertSame($quote->id, $project->quotation_id);
        $this->assertSame($order->id, $project->order_id);
        $this->assertSame($project->id, $order->project_id);
        $this->assertSame($quote->customer_id, $order->customer_id);
        $this->assertSame('12 Solar Lane, Pune', $order->site_address);
        $this->assertEquals((float) $rev->totals['grand_total'], (float) $order->contract_value);
        $this->assertEquals($rev->lines, $order->lines_snapshot);
        $this->assertEquals($rev->totals, $order->totals_snapshot);
        $this->assertEquals($rev->payment_schedule, $order->payment_schedule);
        $this->assertEqualsWithDelta(5.5, (float) $order->capacity_kw, 0.001);
        $this->assertSame('hybrid', $order->project_type);

        $this->assertCount(10, $project->milestones);
        $this->assertCount(2, $project->materials);
        $this->assertNotNull($project->installation);
        $this->assertTrue($project->materials->firstWhere('sku', 'SRV-INSTALL')->is_service);

        $this->assertTrue(AuditLog::query()->where('action', 'quotation.converted')->exists());
        $this->assertSame(1, Order::query()->where('quotation_id', $quote->id)->count());
    }

    public function test_sequential_conversion_is_idempotent(): void
    {
        $quote = $this->acceptQuote();
        $first = $this->converter->convert($quote, $this->admin);
        $second = $this->converter->convert($quote->fresh(), $this->admin);

        $this->assertTrue($first['created']);
        $this->assertFalse($second['created']);
        $this->assertSame($first['order']->id, $second['order']->id);
        $this->assertSame($first['project']->id, $second['project']->id);
        $this->assertStringContainsString('already exists', $second['message']);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Project::query()->count());
    }

    public function test_non_accepted_quotation_cannot_convert(): void
    {
        $quote = $this->quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'X', 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $this->admin);

        $this->expectException(ValidationException::class);
        $this->converter->convert($quote, $this->admin);
    }

}
