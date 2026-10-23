<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use App\Models\MessageLog;

class DemoWhatsAppDriver implements WhatsAppProvider
{
    public function name(): string
    {
        return 'Demo';
    }

    public function isConfigured(): bool
    {
        return true; // Demo always "works" locally without credentials
    }

    public function send(array $message): array
    {
        return [
            'status' => MessageLog::STATUS_DEMO_SIMULATED,
            'simulated' => true,
            'response' => [
                'label' => 'SIMULATED – not delivered',
                'to' => $message['to'] ?? null,
                'template' => $message['template'] ?? null,
            ],
        ];
    }

    public function testConnection(): array
    {
        return [
            'ok' => true,
            'mode' => 'demo',
            'message' => 'SIMULATED – Demo mode connection check succeeded. No provider was contacted; nothing was delivered.',
            'simulated' => true,
        ];
    }
}
