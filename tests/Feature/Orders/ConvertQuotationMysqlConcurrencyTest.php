<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\NumberingService;
use App\Services\QuotationService;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Requires MySQL so lockForUpdate + unique quotation_id are meaningful across processes.
 */
class ConvertQuotationMysqlConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        // Skip before RefreshDatabase migrates — empty $connectionsToTransact would
        // otherwise poison later sqlite :memory: tests in the same PHPUnit process.
        $conn = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'sqlite';
        if ($conn !== 'mysql') {
            $this->markTestSkipped('Parallel conversion lock test requires MySQL (DB_CONNECTION=mysql).');
        }

        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);

        foreach ([
            ['finance.company_state', 'Maharashtra', 'string'],
            ['finance.tax_split_rule', 'auto', 'string'],
            ['finance.cgst_rate', 0.09, 'float'],
            ['finance.sgst_rate', 0.09, 'float'],
            ['finance.igst_rate', 0.18, 'float'],
            ['finance.round_off', 'none', 'string'],
        ] as [$key, $value, $type]) {
            app(SettingsService::class)->set($key, $value, [
                'group' => 'finance', 'type' => $type, 'change_summary' => 'test',
            ]);
        }
    }

    public function test_parallel_processes_create_exactly_one_order(): void
    {
        $admin = User::factory()->withRole('super-admin')->create();
        Warehouse::query()->create(['name' => 'CWH', 'code' => 'CWH', 'is_active' => true]);
        $product = Product::query()->create([
            'reference' => app(NumberingService::class)->next('product'),
            'sku' => 'PAR-SKU',
            'category' => 'solar_panels',
            'brand' => 'HelioCraft',
            'title' => 'Parallel Panel',
            'unit' => 'pcs',
            'purchase_cost' => 10,
            'sale_price' => 100,
            'gst_rate' => 18,
            'status' => 'active',
        ]);

        $quotes = app(QuotationService::class);
        $quote = $quotes->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $admin->id,
            'customer_state' => 'Maharashtra',
            'site_address' => 'Parallel Site',
        ], [
            [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'title' => $product->title,
                'quantity' => 1,
                'unit_price' => 100,
                'discount_percent' => 0,
                'gst_rate' => 0.18,
            ],
        ], $admin);
        $quotes->transition($quote, 'sent', $admin);
        $quotes->transition($quote->fresh(), 'accepted', $admin);
        $quoteId = $quote->id;

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
        for ($i = 0; $i < 6; $i++) {
            $processes[] = Process::path(base_path())
                ->env($env)
                ->start([PHP_BINARY, 'artisan', 'quote:convert', (string) $quoteId]);
        }

        $orderIds = [];
        foreach ($processes as $process) {
            $result = $process->wait();
            $this->assertTrue($result->successful(), 'stdout='.$result->output().' stderr='.$result->errorOutput());
            $parts = explode('|', trim($result->output()));
            $orderIds[] = (int) $parts[0];
        }

        $this->assertCount(1, array_unique($orderIds), 'Duplicate orders under concurrency: '.implode(',', $orderIds));
        $this->assertSame(1, Order::query()->where('quotation_id', $quoteId)->count());
    }
}
