<?php

namespace Tests\Feature\Foundation;

use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NumberingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_allocation_is_unique(): void
    {
        $numbering = app(NumberingService::class);
        $numbering->syncFromSettings('lead');

        $refs = [];
        for ($i = 0; $i < 20; $i++) {
            $refs[] = $numbering->next('lead');
        }

        $this->assertCount(20, array_unique($refs));
    }

    public function test_lock_for_update_path_inside_transaction(): void
    {
        app(NumberingService::class)->syncFromSettings('quote');

        $a = null;
        $b = null;

        DB::transaction(function () use (&$a) {
            $a = app(NumberingService::class)->next('quote');
        });
        DB::transaction(function () use (&$b) {
            $b = app(NumberingService::class)->next('quote');
        });

        $this->assertNotSame($a, $b);
        Artisan::call('numbering:next', ['entity' => 'quote']);
    }
}
