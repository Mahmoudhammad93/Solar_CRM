<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use App\Models\MessageLog;

/**
 * Placeholder for a real Meta/Cloud API (or other) provider.
 * Never invents sent/delivered; returns Not implemented unless explicitly enabled with credentials.
 */
class ConfiguredWhatsAppDriver implements WhatsAppProvider
{
    public function name(): string
    {
        return (string) (config('services.whatsapp.provider_name') ?: 'Configured provider');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    public function send(array $message): array
    {
        if (! $this->isConfigured()) {
            return [
                'status' => MessageLog::STATUS_FAILED,
                'error' => 'WhatsApp credentials are not configured on the server. Set WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID in the environment. Configured provider: Not implemented for live send in this build.',
                'simulated' => false,
            ];
        }

        if (! (bool) config('services.whatsapp.allow_live_send', false)) {
            return [
                'status' => MessageLog::STATUS_FAILED,
                'error' => 'Live WhatsApp send is disabled (WHATSAPP_ALLOW_LIVE_SEND=false). Configured provider: Not implemented — no untested API client ships in this build.',
                'simulated' => false,
            ];
        }

        // Intentionally no HTTP client: do not ship untested provider code.
        return [
            'status' => MessageLog::STATUS_FAILED,
            'error' => 'Configured provider: Not implemented. Live send path is blocked until a tested provider client is added.',
            'simulated' => false,
        ];
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'mode' => 'configured',
                'message' => 'Not configured — add WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID to the server environment (.env). Secrets are never shown or stored in the database.',
                'simulated' => false,
            ];
        }

        if (! (bool) config('services.whatsapp.allow_live_test', false)) {
            return [
                'ok' => false,
                'mode' => 'configured',
                'message' => 'Credentials detected, but live test is disabled (set WHATSAPP_ALLOW_LIVE_TEST=true only when you intentionally enable a provider call). Configured provider client: Not implemented.',
                'simulated' => false,
            ];
        }

        return [
            'ok' => false,
            'mode' => 'configured',
            'message' => 'Configured provider: Not implemented — refusing to call an untested API client.',
            'simulated' => false,
        ];
    }
}
