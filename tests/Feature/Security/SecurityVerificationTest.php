<?php

namespace Tests\Feature\Security;

use App\Filament\Pages\ChangePassword;
use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\ReportsHub;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\MessageLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\DocumentService;
use App\Services\NumberingService;
use App\Services\QuotationService;
use App\Services\SettingsService;
use App\Support\PermissionCatalog;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Validation V2 — server-side security & authorization (PDF §7C, SET-07, SEC).
 * Exercises HTTP/Livewire/Gates directly; does not rely on UI hiding.
 */
class SecurityVerificationTest extends TestCase
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
        Storage::fake('local');
    }

    public function test_permission_matrix_matches_default_role_grants(): void
    {
        $grants = PermissionCatalog::defaultRoleGrants();
        $allKeys = array_column(PermissionCatalog::all(), 'key');

        foreach ($grants as $slug => $expectedKeys) {
            $role = Role::query()->where('slug', $slug)->firstOrFail();
            $user = User::factory()->withRole($slug)->create();

            foreach ($allKeys as $key) {
                $expected = $slug === 'super-admin' || in_array($key, $expectedKeys, true);
                $actual = $user->hasPermission($key);
                $this->assertSame(
                    $expected,
                    $actual,
                    "Role {$slug} key {$key}: expected ".(int) $expected.' got '.(int) $actual
                );
            }

            if ($slug !== 'super-admin') {
                $dbKeys = $role->permissions()->pluck('key')->sort()->values()->all();
                $want = collect($expectedKeys)->unique()->sort()->values()->all();
                $this->assertSame($want, $dbKeys, "DB grants mismatch for {$slug}");
            }
        }
    }

    public function test_module_action_http_denials_for_surveyor_on_leads_and_settings(): void
    {
        $surveyor = User::factory()->withRole('surveyor-engineer')->create();
        $lead = Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Hidden',
            'mobile' => '01111111111',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($surveyor)->get('/admin/leads')->assertForbidden();

        $status = $this->actingAs($surveyor)->get('/admin/leads/'.$lead->id.'/edit')->status();
        $this->assertContains($status, [403, 404]);

        Livewire::actingAs($surveyor)
            ->test(ManageSettings::class)
            ->assertForbidden();
    }

    public function test_executive_and_surveyor_cross_access_by_id_denied(): void
    {
        $execA = User::factory()->withRole('sales-executive')->create();
        $execB = User::factory()->withRole('sales-executive')->create();
        $survA = User::factory()->withRole('surveyor-engineer')->create();
        $survB = User::factory()->withRole('surveyor-engineer')->create();

        $leadB = Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'B Lead',
            'mobile' => '01222222222',
            'assigned_to' => $execB->id,
            'stage' => 'new',
            'created_by' => $execB->id,
        ]);
        $visitB = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $survB->id,
            'status' => 'scheduled',
        ]);

        $this->assertRecordDenied($this->actingAs($execA)->get('/admin/leads/'.$leadB->id.'/edit'));
        $this->assertRecordDenied($this->actingAs($survA)->get('/admin/visits/'.$visitB->id.'/edit'));
    }

    /**
     * Accept 403/404 (policy/scope) or auth redirect — never 200 with the foreign record.
     */
    private function assertRecordDenied(TestResponse $response): void
    {
        $status = $response->status();
        if (in_array($status, [403, 404], true)) {
            $this->assertTrue(true);

            return;
        }
        $this->assertSame(302, $status);
        $loc = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_contains($loc, '/login') || str_contains($loc, '/admin'),
            "Unexpected redirect for denied record: {$loc}"
        );
        $this->assertStringNotContainsString('/edit', $loc);
    }

    public function test_export_permission_blocks_reports_hub_for_executive(): void
    {
        $exec = User::factory()->withRole('sales-executive')->create();
        $this->assertFalse($exec->hasPermission('reports.export'));
        $this->assertFalse($exec->hasPermission('reports.view'));

        Livewire::actingAs($exec)
            ->test(ReportsHub::class)
            ->assertForbidden();
    }

    public function test_api_token_empty_abilities_and_wrong_scope_denied(): void
    {
        app(SettingsService::class)->set('api.enabled', true, [
            'group' => 'api', 'type' => 'boolean', 'change_summary' => 'v2',
        ], $this->admin->id);

        $issued = ApiToken::issue($this->admin, 'empty', []);
        $this->withToken($issued['plain'])->getJson('/api/v1/leads')->assertForbidden();

        $wrong = ApiToken::issue($this->admin, 'wrong', ['other:scope']);
        $this->withToken($wrong['plain'])->getJson('/api/v1/leads')->assertForbidden();
    }

    public function test_deactivated_user_session_blocked_from_panel_routes(): void
    {
        $user = User::factory()->withRole('sales-executive')->create(['password' => 'Password1!']);
        $this->actingAs($user)->get('/admin')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $response = $this->actingAs($user->fresh())->get('/admin');
        // Filament may 403 (canAccessPanel) or middleware may redirect to login.
        $this->assertContains($response->status(), [302, 403]);
        $this->assertNotSame(200, $response->status());
    }

    public function test_password_change_does_not_mass_assign_role_or_active(): void
    {
        $roleExec = Role::query()->where('slug', 'sales-executive')->firstOrFail();
        $roleAdmin = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user = User::factory()->withRole('sales-executive')->create([
            'password' => 'Password1!',
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->set('data.role_id', $roleAdmin->id)
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass2!', $user->password));
        $this->assertSame($roleExec->id, $user->role_id);
        $this->assertTrue($user->is_active);
    }

    public function test_user_fill_rejects_privileged_role_and_active_fields(): void
    {
        $user = User::factory()->withRole('sales-executive')->create();
        $adminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $originalRole = $user->role_id;

        $user->fill([
            'name' => 'Hacked',
            'role_id' => $adminRole->id,
            'is_active' => false,
        ])->save();

        $user->refresh();
        $this->assertSame('Hacked', $user->name);
        $this->assertSame($originalRole, $user->role_id);
        $this->assertTrue($user->is_active);
    }

    public function test_upload_rejects_oversize_path_traversal_and_stores_safe_name(): void
    {
        app(SettingsService::class)->set('documents.max_upload_kb', 10, [
            'group' => 'documents', 'type' => 'integer', 'change_summary' => 'v2',
        ], $this->admin->id);

        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $this->admin->id,
            'status' => 'scheduled',
        ]);

        $big = UploadedFile::fake()->create('big.pdf', 50);
        try {
            app(DocumentService::class)->store($visit, $big, $this->admin, ['document_type' => 'site_photo']);
            $this->fail('Expected ValidationException for oversize');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }

        // Symfony UploadedFile strips directory segments from client names; service still sanitizes.
        $svc = app(DocumentService::class);
        $this->assertSame('passwd.jpg', $svc->safeOriginalName('../../etc/passwd.jpg'));
        $this->assertSame('upload.bin', $svc->safeOriginalName('..'));

        $ok = UploadedFile::fake()->image('roof photo.jpg');
        $doc = $svc->store($visit, $ok, $this->admin, ['document_type' => 'site_photo']);
        $this->assertStringNotContainsString('..', $doc->path);
        $this->assertStringNotContainsString('/', $doc->stored_name);
        $this->assertSame('roof photo.jpg', $doc->original_name);
        // Storage path uses UUID, never client path.
        $this->assertMatchesRegularExpression('#^documents/visit/\d+/[0-9a-f-]{36}\.jpg$#', $doc->path);
    }

    public function test_quote_verify_idor_wrong_token_and_rate_limit(): void
    {
        $quote = app(QuotationService::class)->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'P', 'title' => 'Panel', 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $this->admin);

        $token = $quote->public_token;
        $this->assertNotNull($token);
        $this->assertGreaterThanOrEqual(40, strlen((string) $token));

        $this->get(route('quotes.verify', ['token' => $token]))->assertOk();
        $this->get(route('quotes.verify', ['token' => 'x'.str_repeat('a', 47)]))->assertNotFound();
        $this->get('/quotes/verify/'.$quote->id)->assertNotFound();

        // Exhaust named limiter for this IP (30/min).
        RateLimiter::clear('illuminate:'.sha1('quote-verify'.'|'.request()->ip()));
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit('illuminate:'.sha1('quote-verify'.'|'.request()->ip()));
        }

        // Prefer real HTTP exhaustion if middleware key differs — probe then force 429 via many requests.
        $last = null;
        for ($i = 0; $i < 35; $i++) {
            $last = $this->get(route('quotes.verify', ['token' => 'bad'.str_pad((string) $i, 45, 'z')]));
            if ($last->status() === 429) {
                break;
            }
        }
        $this->assertSame(429, $last?->status(), 'Quote verify should rate-limit after repeated attempts');
    }

    public function test_xss_in_company_name_escaped_on_verify_page(): void
    {
        app(SettingsService::class)->set('branding.company_name', '<script>alert(1)</script>', [
            'group' => 'branding', 'type' => 'string', 'change_summary' => 'v2',
        ], $this->admin->id);

        $quote = app(QuotationService::class)->create([
            'proposal_type' => 'hybrid',
            'owner_id' => $this->admin->id,
            'customer_state' => 'Maharashtra',
        ], [
            ['sku' => 'P', 'title' => '<img src=x onerror=alert(1)>', 'quantity' => 1, 'unit_price' => 1, 'discount_percent' => 0, 'gst_rate' => 0.18],
        ], $this->admin);

        $html = $this->get(route('quotes.verify', ['token' => $quote->public_token]))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function test_sql_injection_attempt_on_api_stage_filter_is_bound(): void
    {
        app(SettingsService::class)->set('api.enabled', true, [
            'group' => 'api', 'type' => 'boolean', 'change_summary' => 'v2',
        ], $this->admin->id);

        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Safe',
            'mobile' => '01333333333',
            'assigned_to' => $this->admin->id,
            'stage' => 'new',
            'source' => 'website',
            'created_by' => $this->admin->id,
        ]);

        $issued = ApiToken::issue($this->admin, 'sqli');
        $resp = $this->withToken($issued['plain'])
            ->getJson('/api/v1/leads?stage='.urlencode("' OR 1=1 --"));

        $resp->assertOk();
        $this->assertSame([], $resp->json('data'));
    }

    public function test_secrets_never_appear_in_audit_or_message_logs(): void
    {
        $secret = 'v2-super-secret-token-XYZ';
        app(AuditLogger::class)->log(
            action: 'security.probe',
            entityType: User::class,
            entityId: $this->admin->id,
            entityReference: $this->admin->email,
            summary: [
                'whatsapp_token' => $secret,
                'smtp_password' => $secret,
                'api_key' => $secret,
                'note' => 'ok',
            ],
        );

        app(SettingsService::class)->set('integrations.whatsapp_token', $secret, [
            'group' => 'integrations', 'type' => 'string', 'change_summary' => 'v2',
        ], $this->admin->id);

        $auditJson = AuditLog::query()->latest('id')->limit(20)->get()->toJson();
        $this->assertStringNotContainsString($secret, $auditJson);
        $this->assertStringContainsString('[REDACTED]', $auditJson);

        MessageLog::query()->create([
            'channel' => 'whatsapp',
            'recipient' => '+201000000000',
            'status' => MessageLog::STATUS_DEMO_SIMULATED,
            'payload' => app(AuditLogger::class)->redact(['token' => $secret]),
            'created_at' => now(),
        ]);
        $msgJson = MessageLog::query()->latest('id')->first()->toJson();
        $this->assertStringNotContainsString($secret, $msgJson);
    }
}
