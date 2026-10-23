<?php

namespace Tests\Feature\Inventory;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);

        $this->stock = app(StockService::class);
        $this->warehouse = Warehouse::query()->create([
            'name' => 'Test WH',
            'code' => 'TWH',
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'reference' => app(NumberingService::class)->next('product'),
            'sku' => 'TEST-SKU-1',
            'category' => 'solar_panels',
            'brand' => 'HelioCraft',
            'title' => 'Test Panel',
            'unit' => 'pcs',
            'purchase_cost' => 10,
            'sale_price' => 20,
            'gst_rate' => 18,
            'status' => 'active',
            'reorder_threshold' => 2,
        ]);
    }

    public function test_ledger_reconciles_to_displayed_quantities(): void
    {
        $this->stock->receive($this->product, $this->warehouse, 10, null, 'R1', 'open', [], 'opening');
        $this->stock->issue($this->product, $this->warehouse, 3, null, 'I1', 'out');
        $this->stock->reserve($this->product, $this->warehouse, 2, null, 'S1', 'hold');

        $balance = StockBalance::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->firstOrFail();

        $this->assertEquals(5.0, (float) $balance->available);
        $this->assertEquals(2.0, (float) $balance->reserved);
        $this->assertEquals(3.0, (float) $balance->issued);

        $mismatches = $this->stock->reconcile($this->product->id, $this->warehouse->id);
        $this->assertSame([], $mismatches, 'Expected ledger to match balances: '.json_encode($mismatches));
    }

    public function test_negative_stock_blocked_when_policy_forbids(): void
    {
        app(SettingsService::class)->set('inventory.allow_negative_stock', false, [
            'group' => 'inventory', 'type' => 'boolean', 'change_summary' => 'test',
        ]);

        $this->stock->receive($this->product, $this->warehouse, 2, null, 'R2', 'open', [], 'opening');

        $this->expectException(ValidationException::class);
        $this->stock->issue($this->product, $this->warehouse, 5, null, 'I2', 'too much');
    }

    public function test_negative_stock_allowed_only_with_policy_and_permission(): void
    {
        app(SettingsService::class)->set('inventory.allow_negative_stock', true, [
            'group' => 'inventory', 'type' => 'boolean', 'change_summary' => 'test enable',
        ]);

        $admin = User::factory()->withRole('super-admin')->create();
        $this->stock->receive($this->product, $this->warehouse, 1, $admin, 'R3', 'open', [], 'opening');
        $this->stock->issue($this->product, $this->warehouse, 4, $admin, 'I3', 'override');

        $balance = StockBalance::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->firstOrFail();

        $this->assertEquals(-3.0, (float) $balance->available);
        $this->assertTrue(
            AuditLog::query()->where('action', 'stock.negative_allowed')->exists()
        );

        // Executive without inventory.settings cannot go negative even if setting is on
        app(SettingsService::class)->set('inventory.allow_negative_stock', true, [
            'group' => 'inventory', 'type' => 'boolean', 'change_summary' => 'still on',
        ]);
        $exec = User::factory()->withRole('sales-executive')->create();
        $this->expectException(ValidationException::class);
        $this->stock->issue($this->product, $this->warehouse, 1, $exec, 'I4', 'denied');
    }

    public function test_archived_product_still_loads_for_historical_records(): void
    {
        $id = $this->product->id;
        $title = $this->product->title;
        $this->product->archive();

        $this->assertTrue(Product::query()->active()->whereKey($id)->doesntExist());

        $historical = Product::query()->findOrFail($id);
        $this->assertSame($title, $historical->title);
        $this->assertTrue($historical->is_archived);
        $this->assertSame('archived', $historical->status);
    }
}
