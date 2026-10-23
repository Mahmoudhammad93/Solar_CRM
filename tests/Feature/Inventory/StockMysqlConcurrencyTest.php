<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Requires MySQL so lockForUpdate is meaningful across processes.
 */
class StockMysqlConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Concurrency lock test requires MySQL (DB_CONNECTION=mysql).');
        }
    }

    public function test_concurrent_decrements_never_go_below_zero_when_policy_forbids(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);

        app(SettingsService::class)->set('inventory.allow_negative_stock', false, [
            'group' => 'inventory', 'type' => 'boolean', 'change_summary' => 'test',
        ]);

        $warehouse = Warehouse::query()->create(['name' => 'Conc WH', 'code' => 'CWH', 'is_active' => true]);
        $product = Product::query()->create([
            'reference' => app(NumberingService::class)->next('product'),
            'sku' => 'CONC-SKU',
            'category' => 'accessories',
            'brand' => 'SafeGrid',
            'title' => 'Concurrency Widget',
            'unit' => 'pcs',
            'purchase_cost' => 1,
            'sale_price' => 2,
            'gst_rate' => 18,
            'status' => 'active',
            'reorder_threshold' => 0,
        ]);

        app(StockService::class)->receive($product, $warehouse, 5, null, 'OPEN', 'seed', [], 'opening');

        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ];

        $processes = [];
        for ($i = 0; $i < 10; $i++) {
            $processes[] = Process::path(base_path())
                ->env($env)
                ->start([
                    PHP_BINARY,
                    'artisan',
                    'stock:issue',
                    (string) $product->id,
                    (string) $warehouse->id,
                    '1',
                ]);
        }

        $success = 0;
        $failed = 0;
        foreach ($processes as $process) {
            $result = $process->wait();
            if ($result->successful()) {
                $success++;
            } else {
                $failed++;
            }
        }

        $this->assertSame(5, $success, "Expected exactly 5 successful issues; got success={$success} failed={$failed}");
        $this->assertSame(5, $failed);

        $available = (float) StockBalance::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('available');

        $this->assertSame(0.0, $available);
        $this->assertGreaterThanOrEqual(0.0, $available);
    }
}
