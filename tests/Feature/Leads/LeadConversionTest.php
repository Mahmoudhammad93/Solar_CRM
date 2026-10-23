<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\NumberingService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadConversionTest extends TestCase
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

    public function test_conversion_is_idempotent(): void
    {
        $user = User::factory()->withRole('super-admin')->create();
        $lead = Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Convert Me',
            'mobile' => '0100111222',
            'email' => 'convert@example.test',
            'assigned_to' => $user->id,
            'stage' => 'qualified',
            'created_by' => $user->id,
        ]);

        $service = app(LeadConversionService::class);
        $first = $service->convert($lead, $user);
        $second = $service->convert($lead->fresh(), $user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($first->id, $lead->fresh()->customer_id);
        $this->assertSame('converted', $lead->fresh()->stage);
    }

    public function test_lead_references_are_unique(): void
    {
        $refs = [];
        for ($i = 0; $i < 5; $i++) {
            $refs[] = app(NumberingService::class)->next('lead');
        }

        $this->assertCount(5, array_unique($refs));
    }
}
