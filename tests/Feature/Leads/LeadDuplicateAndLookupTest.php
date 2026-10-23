<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\Lookup;
use App\Models\User;
use App\Services\LeadDuplicateChecker;
use App\Services\NumberingService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDuplicateAndLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->seed(LookupSeeder::class);
    }

    public function test_duplicate_checker_finds_matching_phone_and_email(): void
    {
        $user = User::factory()->withRole('sales-executive')->create();
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Existing',
            'mobile' => '+20 100 111 2222',
            'email' => 'Dup@Example.TEST',
            'assigned_to' => $user->id,
            'stage' => 'new',
        ]);

        $hits = app(LeadDuplicateChecker::class)->findDuplicates('01001112222', 'dup@example.test');

        $this->assertGreaterThanOrEqual(1, $hits->count());
        $this->assertTrue($hits->contains(fn (Lead $l) => $l->name === 'Existing'));
    }

    public function test_new_lookup_source_appears_in_options_for_forms_and_filters(): void
    {
        Lookup::query()->create([
            'type' => 'lead_source',
            'value' => 'trade_show_2026',
            'label' => 'Trade Show 2026',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $options = Lookup::options('lead_source');

        $this->assertArrayHasKey('trade_show_2026', $options);
        $this->assertSame('Trade Show 2026', $options['trade_show_2026']);
    }

    public function test_deactivated_lookup_is_preserved_when_current_value(): void
    {
        Lookup::query()->where('type', 'lead_source')->where('value', 'website')->update(['is_active' => false]);

        $optionsActiveOnly = Lookup::options('lead_source');
        $this->assertArrayNotHasKey('website', $optionsActiveOnly);

        $optionsPreserved = Lookup::options('lead_source', 'website');
        $this->assertArrayHasKey('website', $optionsPreserved);
        $this->assertStringContainsString('inactive', $optionsPreserved['website']);
    }
}
