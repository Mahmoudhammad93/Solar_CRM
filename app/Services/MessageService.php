<?php

namespace App\Services;

use App\Contracts\WhatsAppProvider;
use App\Models\Customer;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\WhatsApp\ConfiguredWhatsAppDriver;
use App\Services\WhatsApp\DemoWhatsAppDriver;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Honest communication layer: Demo by default; never claims sent/delivered without a real provider.
 */
class MessageService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SettingsService $settings,
        private readonly MessageTemplateService $templates,
    ) {}

    public function whatsappMode(): string
    {
        $mode = strtolower((string) $this->settings->get('whatsapp.mode', 'demo'));

        return $mode === 'configured' ? 'configured' : 'demo';
    }

    public function credentialsStatus(): array
    {
        $configured = filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));

        return [
            'status' => $configured ? 'Configured' : 'Not configured',
            'configured' => $configured,
            'setup_message' => $configured
                ? 'Server credentials are present. Live send remains blocked unless WHATSAPP_ALLOW_LIVE_SEND=true and a tested provider client is implemented (currently: Not implemented).'
                : 'Add WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID to the server environment (.env). Secrets are never stored in the database, shown in the UI, or written to ordinary logs.',
        ];
    }

    public function provider(): WhatsAppProvider
    {
        return $this->whatsappMode() === 'configured'
            ? new ConfiguredWhatsAppDriver
            : new DemoWhatsAppDriver;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{status: string, message: string, log_id: int, simulated: bool, wa_me?: string}
     */
    public function sendApprovedTemplate(
        string $templateKey,
        string $phone,
        array $variables = [],
        ?Customer $customer = null,
        ?Model $subject = null,
        ?User $user = null,
        string $channel = 'whatsapp',
    ): array {
        try {
            $normalized = PhoneNumber::normalize(
                $phone,
                (string) $this->settings->get('branding.default_calling_code', '+91')
            );
        } catch (InvalidArgumentException $e) {
            $log = $this->writeLog(
                channel: $channel,
                templateKey: $templateKey,
                recipient: $phone,
                status: MessageLog::STATUS_FAILED,
                subject: $subject,
                user: $user,
                error: $e->getMessage(),
            );

            throw ValidationException::withMessages(['phone' => $e->getMessage()]);
        }

        if ($customer) {
            $this->assertConsentAllowsSend($customer);
        }

        $body = $channel === 'email'
            ? $this->templates->emailBodyFor($templateKey)
            : $this->templates->bodyFor($templateKey);

        try {
            $rendered = $this->templates->render($body, $variables);
        } catch (ValidationException $e) {
            $this->writeLog(
                channel: $channel,
                templateKey: $templateKey,
                recipient: $normalized,
                status: MessageLog::STATUS_FAILED,
                subject: $subject,
                user: $user,
                error: collect($e->errors())->flatten()->implode(' '),
            );
            throw $e;
        }

        if ($channel === 'email') {
            return $this->sendEmailDemo($templateKey, $normalized, $rendered, $subject, $user);
        }

        $provider = $this->provider();
        $result = $provider->send([
            'to' => $normalized,
            'template' => $templateKey,
            'body' => $rendered,
        ]);

        $status = (string) ($result['status'] ?? MessageLog::STATUS_FAILED);
        // Never invent queued/sent/delivered
        if (! in_array($status, [MessageLog::STATUS_DEMO_SIMULATED, MessageLog::STATUS_FAILED], true)) {
            $status = MessageLog::STATUS_FAILED;
            $result['error'] = ($result['error'] ?? '').' Invalid status from provider; coerced to failed.';
        }

        $safeResponse = $this->auditLogger->redact(is_array($result['response'] ?? null) ? $result['response'] : ['raw' => $result['response'] ?? null]);

        $log = $this->writeLog(
            channel: 'whatsapp',
            templateKey: $templateKey,
            recipient: $normalized,
            status: $status,
            subject: $subject,
            user: $user,
            payload: ['body_preview' => mb_substr($rendered, 0, 280)],
            providerResponse: json_encode($safeResponse),
            error: $result['error'] ?? null,
        );

        $this->auditLogger->log(
            action: 'message.whatsapp.'.$status,
            entityType: $subject ? $subject::class : null,
            entityId: $subject?->getKey(),
            entityReference: $subject?->number ?? $subject?->reference ?? null,
            summary: [
                'template' => $templateKey,
                'to' => $normalized,
                'status' => $status,
                'mode' => $this->whatsappMode(),
                'token' => 'should-never-appear', // redaction check path
            ],
        );

        $simulated = $status === MessageLog::STATUS_DEMO_SIMULATED;

        return [
            'status' => $status,
            'message' => $simulated
                ? 'SIMULATED – not delivered. Demo/WhatsApp provider did not send a real message.'
                : ($result['error'] ?? 'Send failed.'),
            'log_id' => $log->id,
            'simulated' => $simulated,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{mode: string, status: string, message: string, log_id?: int, simulated: bool}
     */
    public function share(string $channel, string $template, array $payload, ?User $user = null): array
    {
        $to = (string) ($payload['to'] ?? '');
        $customer = isset($payload['customer_id'])
            ? Customer::query()->find($payload['customer_id'])
            : null;

        $variables = array_merge([
            'company_name' => (string) $this->settings->get('branding.company_name', 'Solar CRM'),
            'link' => (string) ($payload['link'] ?? ''),
            'customer_name' => (string) ($payload['customer_name'] ?? $customer?->name ?? 'Customer'),
            'quote_number' => (string) ($payload['quote_number'] ?? ''),
        ], $payload['variables'] ?? []);

        $subject = null;
        if (! empty($payload['entity_type']) && ! empty($payload['entity_id'])) {
            $class = (string) $payload['entity_type'];
            if (class_exists($class)) {
                $subject = $class::query()->find($payload['entity_id']);
            }
        }

        try {
            $result = $this->sendApprovedTemplate(
                templateKey: $template,
                phone: $to !== '' ? $to : ($customer?->mobile ?? ''),
                variables: $variables,
                customer: $customer,
                subject: $subject,
                user: $user,
                channel: $channel === 'email' ? 'email' : 'whatsapp',
            );
        } catch (ValidationException $e) {
            return [
                'mode' => $this->whatsappMode(),
                'status' => MessageLog::STATUS_FAILED,
                'message' => collect($e->errors())->flatten()->implode(' '),
                'simulated' => false,
            ];
        }

        return [
            'mode' => $this->whatsappMode(),
            'status' => $result['status'],
            'message' => $result['message'],
            'log_id' => $result['log_id'],
            'simulated' => $result['simulated'],
        ];
    }

    /**
     * Opens the user's own WhatsApp — no delivery tracking.
     *
     * @return array{url: string, label: string}
     */
    public function openConversation(string $phone, ?string $prefill = null): array
    {
        $url = PhoneNumber::waMeUrl(
            $phone,
            $prefill,
        );

        return [
            'url' => $url,
            'label' => 'opens WhatsApp, no delivery tracking',
        ];
    }

    /**
     * @return array{ok: bool, mode: string, message: string, simulated: bool}
     */
    public function testConnection(): array
    {
        $creds = $this->credentialsStatus();
        if ($this->whatsappMode() === 'configured' && ! $creds['configured']) {
            return [
                'ok' => false,
                'mode' => 'configured',
                'message' => $creds['setup_message'],
                'simulated' => false,
            ];
        }

        $result = $this->provider()->testConnection();
        $this->auditLogger->log(
            action: 'whatsapp.test_connection',
            summary: [
                'mode' => $result['mode'] ?? $this->whatsappMode(),
                'ok' => $result['ok'],
                'simulated' => $result['simulated'] ?? false,
                // ensure secrets never appear even if someone passes them
                'whatsapp_token' => config('services.whatsapp.token'),
            ],
        );

        // Double-check logs never contain raw token
        Log::info('whatsapp.test_connection', $this->auditLogger->redact([
            'ok' => $result['ok'],
            'mode' => $result['mode'] ?? null,
            'whatsapp_token' => config('services.whatsapp.token'),
        ]));

        return $result;
    }

    public function assertConsentAllowsSend(Customer $customer): void
    {
        $status = strtolower((string) ($customer->whatsapp_consent_status ?? 'unknown'));
        if ($status === 'opted_out') {
            throw ValidationException::withMessages([
                'consent' => 'Customer has opted out of WhatsApp messages. Sending is blocked.',
            ]);
        }
        if ($status !== 'opted_in') {
            throw ValidationException::withMessages([
                'consent' => 'Customer has not opted in to WhatsApp (consent required). Record opt-in with timestamp/source before sending.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function writeLog(
        string $channel,
        ?string $templateKey,
        string $recipient,
        string $status,
        ?Model $subject = null,
        ?User $user = null,
        ?array $payload = null,
        ?string $providerResponse = null,
        ?string $error = null,
    ): MessageLog {
        return MessageLog::query()->create([
            'channel' => $channel,
            'template_key' => $templateKey,
            'recipient' => $recipient,
            'status' => $status,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_reference' => $subject?->number ?? $subject?->reference ?? null,
            'initiated_by' => $user?->id,
            'payload' => $payload ? $this->auditLogger->redact($payload) : null,
            'provider_response' => $providerResponse,
            'error_message' => $error,
            'retry_count' => 0,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{status: string, message: string, log_id: int, simulated: bool}
     */
    private function sendEmailDemo(
        string $templateKey,
        string $recipient,
        string $body,
        ?Model $subject,
        ?User $user,
    ): array {
        $mode = strtolower((string) $this->settings->get('email.mode', 'log'));
        // Always demo/log in this build — no real SMTP send claimed
        $status = MessageLog::STATUS_DEMO_SIMULATED;
        $log = $this->writeLog(
            channel: 'email',
            templateKey: $templateKey,
            recipient: $recipient,
            status: $status,
            subject: $subject,
            user: $user,
            payload: ['body_preview' => mb_substr($body, 0, 280), 'email_mode' => $mode],
            providerResponse: json_encode(['label' => 'SIMULATED – not delivered', 'mailer' => $mode]),
        );

        return [
            'status' => $status,
            'message' => 'SIMULATED – not delivered. Email mode is '.$mode.'; no SMTP delivery claimed.',
            'log_id' => $log->id,
            'simulated' => true,
        ];
    }
}
