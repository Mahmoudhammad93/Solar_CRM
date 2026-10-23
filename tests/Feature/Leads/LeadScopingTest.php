<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\User;
use App\Services\NumberingService;
use App\Support\RecordScoping;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScopingTest extends TestCase
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

    public function test_sales_executive_cannot_access_another_executives_lead(): void
    {
        $execA = User::factory()->withRole('sales-executive')->create(['email' => 'a@solar-crm.test']);
        $execB = User::factory()->withRole('sales-executive')->create(['email' => 'b@solar-crm.test']);

        $leadB = Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Lead For B',
            'mobile' => '01000000002',
            'assigned_to' => $execB->id,
            'stage' => 'new',
            'created_by' => $execB->id,
        ]);

        $this->assertFalse(RecordScoping::canAccessOwnedRecord($execA, $leadB->assigned_to));

        $response = $this->actingAs($execA)->get('/admin/leads/'.$leadB->id.'/edit');

        // Scoped query yields 404; policy denial yields 403. Either is acceptable denial.
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_sales_executive_can_view_own_lead(): void
    {
        $execA = User::factory()->withRole('sales-executive')->create();

        $lead = Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Lead For A',
            'mobile' => '01000000001',
            'assigned_to' => $execA->id,
            'stage' => 'new',
            'created_by' => $execA->id,
        ]);

        $this->assertTrue(RecordScoping::canAccessOwnedRecord($execA, $lead->assigned_to));

        $this->actingAs($execA)
            ->get('/admin/leads/'.$lead->id.'/edit')
            ->assertOk();
    }
}
