<?php

namespace Tests\Feature\Foundation;

use App\Models\Sequence;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Requires MySQL so lockForUpdate is meaningful across processes.
 * Run: DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3308 DB_DATABASE=solar_crm_test ... php artisan test --filter=NumberingMysqlConcurrencyTest
 */
class NumberingMysqlConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Parallel artisan processes must see committed rows (no test transaction wrap).
     *
     * @var array<int, string>
     */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Concurrency lock test requires MySQL (DB_CONNECTION=mysql).');
        }
    }

    public function test_parallel_processes_produce_no_duplicates_on_mysql(): void
    {
        app(NumberingService::class)->syncFromSettings('lead');

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
        for ($i = 0; $i < 12; $i++) {
            $processes[] = Process::path(base_path())
                ->env($env)
                ->start([
                    PHP_BINARY,
                    'artisan',
                    'numbering:next',
                    'lead',
                ]);
        }

        $refs = [];
        foreach ($processes as $process) {
            $result = $process->wait();
            $this->assertTrue($result->successful(), 'stdout='.$result->output().' stderr='.$result->errorOutput());
            $refs[] = trim($result->output());
        }

        $this->assertCount(12, $refs);
        $this->assertCount(12, array_unique($refs), 'Duplicate references allocated under concurrency: '.implode(',', $refs));

        $sequence = Sequence::query()->where('entity', 'lead')->firstOrFail();
        $this->assertSame(13, (int) $sequence->next_number);
    }
}
