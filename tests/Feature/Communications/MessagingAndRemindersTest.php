<?php

namespace Tests\Feature\Communications;

use App\Filament\Pages\CommunicationsSettings;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MessageLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\AuditLogger;
use App\Services\MessageService;
use App\Services\MessageTemplateService;
use App\Services\NumberingService;
use App\Support\MessageTemplateCatalog;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->admin = User::factory()->withRole('super-admin')->create();
    }

    public function test_demo_send_logs_demo_simulated_never_sent_or_delivered(): void
    {
        $customer = $this->makeCustomer();
        $customer->recordWhatsAppConsent('opted_in', 'test');

        $result = app(MessageService::class)->sendApprovedTemplate(
            templateKey: 'quotation_ready',
            phone: '+919876543210',
            variables: MessageTemplateCatalog::sampleData(),
            customer: $customer,
            user: $this->admin,
        );

        $this->assertSame(MessageLog::STATUS_DEMO_SIMULATED, $result['status']);
        $this->assertTrue($result['simulated']);
        $this->assertStringContainsString('SIMULATED', $result['message']);
        $this->assertDatabaseHas('message_logs', [
            'id' => $result['log_id'],
            'status' => MessageLog::STATUS_DEMO_SIMULATED,
        ]);
        $this->assertDatabaseMissing('message_logs', ['status' => 'sent']);
        $this->assertDatabaseMissing('message_logs', ['status' => 'delivered']);
        $this->assertDatabaseMissing('message_logs', ['status' => 'queued']);
    }

    public function test_opted_out_recipient_is_blocked(): void
    {
        $customer = $this->makeCustomer();
        $customer->recordWhatsAppConsent('opted_out', 'sms_stop');

        $this->expectException(ValidationException::class);
        app(MessageService::class)->sendApprovedTemplate(
            templateKey: 'quotation_ready',
            phone: '+919876543210',
            variables: MessageTemplateCatalog::sampleData(),
            customer: $customer,
            user: $this->admin,
        );
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $customer->recordWhatsAppConsent('opted_in', 'test');

        try {
            app(MessageService::class)->sendApprovedTemplate(
                templateKey: 'quotation_ready',
                phone: '12',
                variables: MessageTemplateCatalog::sampleData(),
                customer: $customer,
                user: $this->admin,
            );
            $this->fail('Expected validation failure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('phone', $e->errors());
            $this->assertDatabaseHas('message_logs', ['status' => MessageLog::STATUS_FAILED]);
        }
    }

    public function test_unknown_placeholder_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(MessageTemplateService::class)->assertPlaceholdersAllowed('Hello {{not_a_real_field}}');
    }

    public function test_audit_and_log_redact_tokens(): void
    {
        $redacted = app(AuditLogger::class)->redact([
            'whatsapp_token' => 'SECRET_TOKEN_VALUE',
            'ok' => true,
        ]);
        $this->assertSame('[REDACTED]', $redacted['whatsapp_token']);
        $this->assertTrue($redacted['ok']);

        $customer = $this->makeCustomer();
        $customer->recordWhatsAppConsent('opted_in', 'test');
        app(MessageService::class)->sendApprovedTemplate(
            templateKey: 'quotation_ready',
            phone: '9876543210',
            variables: MessageTemplateCatalog::sampleData(),
            customer: $customer,
            user: $this->admin,
        );

        $summary = AuditLog::query()->where('action', 'like', 'message.whatsapp.%')->latest('id')->value('summary');
        $this->assertIsString($summary);
        $this->assertStringNotContainsString('should-never-appear', $summary);
        $this->assertStringContainsString('[REDACTED]', $summary);
    }

    public function test_non_admin_cannot_change_communications_settings(): void
    {
        $exec = User::factory()->withRole('sales-executive')->create();
        $this->actingAs($exec);
        $this->assertFalse(Gate::forUser($exec)->allows('settings.update'));
        $this->assertFalse(CommunicationsSettings::canAccess());

        Livewire::actingAs($exec)
            ->test(CommunicationsSettings::class)
            ->assertForbidden();
    }

    public function test_reminders_dispatch_creates_in_app_notifications(): void
    {
        $owner = User::factory()->withRole('sales-executive')->create();
        Lead::query()->create([
            'reference' => app(NumberingService::class)->next('lead'),
            'name' => 'Remind Me',
            'mobile' => '9000000099',
            'assigned_to' => $owner->id,
            'stage' => 'new',
            'next_follow_up_at' => now()->addHour(),
            'created_by' => $this->admin->id,
        ]);

        $this->artisan('reminders:dispatch')->assertSuccessful();
        $this->assertTrue(
            UserNotification::query()->where('user_id', $owner->id)->where('category', 'follow_up')->exists()
        );
    }

    public function test_test_connection_demo_is_labelled_simulated(): void
    {
        $result = app(MessageService::class)->testConnection();
        $this->assertTrue($result['simulated']);
        $this->assertSame('demo', $result['mode']);
        $this->assertStringContainsString('SIMULATED', $result['message']);
    }

    private function makeCustomer(): Customer
    {
        return Customer::query()->create([
            'customer_number' => app(NumberingService::class)->next('customer'),
            'name' => 'Msg Customer',
            'mobile' => '9876543210',
            'created_by' => $this->admin->id,
        ]);
    }
}
