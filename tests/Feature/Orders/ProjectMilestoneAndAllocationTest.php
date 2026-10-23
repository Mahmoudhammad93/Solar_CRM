<?php

namespace Tests\Feature\Orders;

use App\Models\Product;
use App\Models\ProjectMaterial;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AllocationService;
use App\Services\ConvertQuotationToProject;
use App\Services\NumberingService;
use App\Services\ProjectMilestoneService;
use App\Services\QuotationService;
use App\Services\SettingsService;
use App\Services\StockService;
use App\Support\ProjectMilestones;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectMilestoneAndAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $warehouse;

    private Product $product;

    private ConvertQuotationToProject $converter;

    private ProjectMilestoneService $milestones;

    private AllocationService $allocation;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);

        $this->admin = User::factory()->withRole('super-admin')->create();
        $this->converter = app(ConvertQuotationToProject::class);
        $this->milestones = app(ProjectMilestoneService::class);
        $this->allocation = app(AllocationService::class);
        $this->stock = app(StockService::class);

        foreach ([
            ['finance.company_state', 'Maharashtra', 'string'],
            ['finance.tax_split_rule', 'auto', 'string'],
            ['finance.cgst_rate', 0.09, 'float'],
            ['finance.sgst_rate', 0.09, 'float'],
            ['finance.igst_rate', 0.18, 'float'],
            ['finance.round_off', 'none', 'string'],
            ['inventory.allow_negative_stock', false, 'boolean'],
        ] as [$key, $value, $type]) {
            app(SettingsService::class)->set($key, $value, [
                'group' => explode('.', $key)[0], 'type' => $type, 'change_summary' => 'test',
            ]);
        }

        $this->warehouse = Warehouse::query()->create([
            'name' => 'Alloc WH',
            'code' => 'AWH',
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'reference' => app(NumberingService::class)->next('product'),
            'sku' => 'ALLOC-SKU-1',
            'category' => 'solar_panels',
            'brand' => 'HelioCraft',
            'title' => 'Alloc Panel',
            'unit' => 'pcs',
            'purchase_cost' => 50,
            'sale_price' => 200,
            'gst_rate' => 18,
            'status' => 'active',
            'reorder_threshold' => 5,
        ]);
    }

    private function convertAcceptedProject(int $requiredQty = 10): ProjectMaterial
    {
        $quotes = app(QuotationService::class);
        $quote = $quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
            'customer_state' => 'Maharashtra',
            'site_address' => 'Site A',
            'system_capacity_kw' => 3,
        ], [
            [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'title' => $this->product->title,
                'quantity' => $requiredQty,
                'unit_price' => 200,
                'discount_percent' => 0,
                'gst_rate' => 0.18,
                'unit' => 'pcs',
            ],
        ], $this->admin);
        $quotes->transition($quote, 'sent', $this->admin);
        $quotes->transition($quote->fresh(), 'accepted', $this->admin);

        $result = $this->converter->convert($quote->fresh(), $this->admin);
        $material = $result['project']->materials->first();
        $material->update(['warehouse_id' => $this->warehouse->id]);

        return $material->fresh();
    }

    public function test_milestone_hold_requires_reason(): void
    {
        $material = $this->convertAcceptedProject(2);
        $project = $material->project()->with('milestones')->first();
        $first = $project->milestones->firstWhere('milestone_key', 'order_confirmed');

        try {
            $this->milestones->transition($first, 'hold', $this->admin, null);
            $this->fail('Expected ValidationException for missing hold reason');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('hold_reason', $e->errors());
        }
    }

    public function test_milestone_transitions_and_percent_complete(): void
    {
        $material = $this->convertAcceptedProject(2);
        $project = $material->project()->with('milestones')->first();
        $first = $project->milestones->firstWhere('milestone_key', 'order_confirmed');

        $this->assertFalse(ProjectMilestones::canTransition('completed', 'active'));

        $this->milestones->transition($first, 'hold', $this->admin, 'Awaiting deposit');
        $this->assertSame('hold', $first->fresh()->status);
        $this->assertSame('Awaiting deposit', $first->fresh()->hold_reason);

        $this->milestones->transition($first->fresh(), 'completed', $this->admin);
        $project->refresh();
        $this->assertEqualsWithDelta(5.0, (float) $project->percent_complete, 0.01);
        $this->assertSame('documents_pending', $project->current_milestone_key);
        $this->assertNotNull($first->fresh()->completed_at);
    }

    public function test_shortage_when_required_exceeds_available(): void
    {
        $this->stock->receive($this->product, $this->warehouse, 3, $this->admin, 'R-SHORT', 'open', [], 'opening');
        $material = $this->convertAcceptedProject(10);

        $avail = $this->allocation->availability($material);
        $this->assertTrue($avail['has_shortage']);
        $this->assertGreaterThan(0, $avail['shortage']);
        $this->assertEquals(3.0, $avail['available']);
        $this->assertEquals(10.0, $avail['required']);
    }

    public function test_allocate_issue_return_reconciles_ledger(): void
    {
        $this->stock->receive($this->product, $this->warehouse, 20, $this->admin, 'R-OK', 'open', [], 'opening');
        $material = $this->convertAcceptedProject(8);

        $this->allocation->reserve($material, 5, $this->admin);
        $this->allocation->allocate($material->fresh(), 5, $this->admin);
        $this->allocation->issue($material->fresh(), 3, $this->admin);
        $this->allocation->returnStock($material->fresh(), 1, $this->admin);

        $material->refresh();
        $this->assertEquals(0.0, (float) $material->quantity_reserved);
        $this->assertEquals(2.0, (float) $material->quantity_allocated);
        $this->assertEquals(2.0, (float) $material->quantity_issued);

        $balance = StockBalance::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->firstOrFail();

        // receive 20; reserve/allocate moved 5; issue 3 from allocated; return 1 to available
        // available: 20 - 5 (reserved from avail) + 1 return = 16; reserved 0; allocated 2; issued 2
        $this->assertEquals(16.0, (float) $balance->available);
        $this->assertEquals(0.0, (float) $balance->reserved);
        $this->assertEquals(2.0, (float) $balance->allocated);
        $this->assertEquals(2.0, (float) $balance->issued);

        $mismatches = $this->stock->reconcile($this->product->id, $this->warehouse->id);
        $this->assertSame([], $mismatches, json_encode($mismatches));
    }

    public function test_reserve_blocked_when_insufficient_stock(): void
    {
        $this->stock->receive($this->product, $this->warehouse, 2, $this->admin, 'R-LOW', 'open', [], 'opening');
        $material = $this->convertAcceptedProject(10);

        $this->expectException(ValidationException::class);
        $this->allocation->reserve($material, 5, $this->admin);
    }
}
