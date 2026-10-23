<?php

namespace App\Services;

use App\Models\User;

/**
 * Demo/Simulated message channel for Email/WhatsApp share actions.
 * Never claims delivery. Phase 10 may add real providers.
 */
class MessageService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{mode: string, status: string, message: string}
     */
    public function share(string $channel, string $template, array $payload, ?User $user = null): array
    {
        $mode = (string) $this->settings->get('integrations.message_mode', 'demo');
        $result = [
            'mode' => $mode === 'live' ? 'live' : 'demo',
            'status' => 'simulated',
            'message' => 'Simulated — message was not sent to a real provider.',
            'channel' => $channel,
            'template' => $template,
        ];

        $this->auditLogger->log(
            action: 'message.simulated',
            entityType: $payload['entity_type'] ?? null,
            entityId: isset($payload['entity_id']) ? (int) $payload['entity_id'] : null,
            entityReference: $payload['entity_reference'] ?? null,
            summary: array_merge($result, ['to' => $payload['to'] ?? null]),
        );

        return $result;
    }
}
