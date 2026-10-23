<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageSettings;
use App\Models\ApiToken;
use App\Models\Lead;
use App\Models\Lookup;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Support\TicketWorkflow;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsCompletionAndApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->seed(LookupSeeder::class);
        $this->admin = User::factory()->withRole('super-admin')->create();
    }

    public function test_set03_new_lead_source_appears_in_lookup_options(): void
    {
        Lookup::query()->create([
            'type' => 'lead_source',
            'value' => 'trade_show_2026',
            'label' => 'Trade Show 2026',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $opts = Lookup::options('lead_source');
        $this->assertArrayHasKey('trade_show_2026', $opts);
        $this->assertSame('Trade Show 2026', $opts['trade_show_2026']);
    }

    public function test_set03_new_ticket_status_label_appears_in_workflow_labels(): void
    {
        Lookup::query()->create([
            'type' => 'ticket_status',
            'value' => 'escalated',
            'label' => 'Escalated',
            'sort_order' => 55,
            'is_active' => true,
        ]);

        $labels = TicketWorkflow::labels();
        $this->assertArrayHasKey('escalated', $labels);
        $this->assertSame('Escalated', $labels['escalated']);
        // Transitions remain code-backed — escalated not in open→… map
        $this->assertFalse(TicketWorkflow::canTransition('open', 'escalated'));
    }

    public function test_set07_non_admin_rejected_from_settings_livewire(): void
    {
        $exec = User::factory()->withRole('sales-executive')->create();
        $this->actingAs($exec);
        $this->assertFalse(Gate::forUser($exec)->allows('settings.update'));

        Livewire::actingAs($exec)
            ->test(ManageSettings::class)
            ->assertForbidden();
    }

    public function test_set08_audit_redacts_secrets(): void
    {
        $summary = app(AuditLogger::class)->redact([
            'smtp_password' => 'super-secret',
            'whatsapp_token' => 'wa-secret',
            'ok' => true,
        ]);
        $this->assertSame('[REDACTED]', $summary['smtp_password']);
        $this->assertSame('[REDACTED]', $summary['whatsapp_token']);
        $this->assertTrue($summary['ok']);
    }

    public function test_set09_setting_persists_after_cache_forget(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('regional.locale', 'en_GB', [
            'group' => 'regional', 'type' => 'string', 'is_sample' => false, 'change_summary' => 'test',
        ], $this->admin->id);

        $this->assertSame('en_GB', $settings->get('regional.locale'));
        $settings->forgetCache();
        Cache::forget('app.settings.map');
        // New resolve from DB
        $this->assertSame('en_GB', app(SettingsService::class)->get('regional.locale'));
    }

    public function test_api_requires_auth_and_respects_enable_flag(): void
    {
        $this->getJson('/api/v1/leads')->assertUnauthorized();

        app(SettingsService::class)->set('api.enabled', false, [
            'group' => 'api', 'type' => 'boolean', 'change_summary' => 'test',
        ], $this->admin->id);

        $issued = ApiToken::issue($this->admin, 't1');
        $this->withToken($issued['plain'])->getJson('/api/v1/leads')->assertStatus(503);
    }

    public function test_api_leads_200_and_scopes_executive(): void
    {
        app(SettingsService::class)->set('api.enabled', true, [
            'group' => 'api', 'type' => 'boolean', 'change_summary' => 'test',
        ], $this->admin->id);

        $exec = User::factory()->withRole('sales-executive')->create();
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Mine',
            'mobile' => '9000000001',
            'assigned_to' => $exec->id,
            'stage' => 'new',
            'source' => 'website',
            'created_by' => $this->admin->id,
        ]);
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Other',
            'mobile' => '9000000002',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'website',
            'created_by' => $this->admin->id,
        ]);

        $adminToken = ApiToken::issue($this->admin, 'admin-api');
        $adminResp = $this->withToken($adminToken['plain'])->getJson('/api/v1/leads');
        $adminResp->assertOk();
        $this->assertGreaterThanOrEqual(2, count($adminResp->json('data')));

        $execToken = ApiToken::issue($exec, 'exec-api');
        $execResp = $this->withToken($execToken['plain'])->getJson('/api/v1/leads');
        $execResp->assertOk();
        $names = collect($execResp->json('data'))->pluck('name')->all();
        $this->assertContains('Mine', $names);
        $this->assertNotContains('Other', $names);
    }

    public function test_api_forbidden_without_leads_view(): void
    {
        app(SettingsService::class)->set('api.enabled', true, [
            'group' => 'api', 'type' => 'boolean', 'change_summary' => 'test',
        ], $this->admin->id);

        // surveyor has no leads.view in default grants
        $surveyor = User::factory()->withRole('surveyor-engineer')->create();
        $token = ApiToken::issue($surveyor, 'no-leads');
        $this->withToken($token['plain'])->getJson('/api/v1/leads')->assertForbidden();
    }

    public function test_deactivated_lookup_preserved_on_options(): void
    {
        $row = Lookup::query()->create([
            'type' => 'lead_stage',
            'value' => 'legacy_stage',
            'label' => 'Legacy',
            'sort_order' => 1,
            'is_active' => false,
        ]);
        $this->assertFalse($row->is_active);
        $without = Lookup::options('lead_stage');
        $this->assertArrayNotHasKey('legacy_stage', $without);
        $with = Lookup::options('lead_stage', 'legacy_stage');
        $this->assertArrayHasKey('legacy_stage', $with);
        $this->assertStringContainsString('inactive', $with['legacy_stage']);
    }
}
