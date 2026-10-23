<?php

namespace Tests\Feature\Foundation;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logger_redacts_password_and_secrets(): void
    {
        $logger = app(AuditLogger::class);

        $log = $logger->log('test.sensitive', summary: [
            'password' => 'SuperSecret1!',
            'whatsapp_token' => 'tok_abc',
            'safe_field' => 'ok',
        ]);

        $this->assertStringContainsString('[REDACTED]', (string) $log->summary);
        $this->assertStringNotContainsString('SuperSecret1!', (string) $log->summary);
        $this->assertStringNotContainsString('tok_abc', (string) $log->summary);
        $this->assertStringContainsString('ok', (string) $log->summary);
    }

    public function test_settings_service_redacts_secret_keys_in_audit(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('integration.api_token_demo', 'raw-secret-value', [
            'group' => 'integrations',
            'type' => 'string',
            'is_sample' => true,
        ]);

        $summary = AuditLog::query()->latest('id')->value('summary');

        $this->assertStringContainsString('[REDACTED]', (string) $summary);
        $this->assertStringNotContainsString('raw-secret-value', (string) $summary);
    }
}
