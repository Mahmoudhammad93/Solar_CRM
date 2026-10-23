<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'api_key',
        'apikey',
        'access_token',
        'refresh_token',
        'smtp_password',
        'whatsapp_token',
        'mail_password',
        'client_secret',
    ];

    /**
     * @param  array<string, mixed>|string|null  $summary
     */
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $entityReference = null,
        array|string|null $summary = null,
    ): AuditLog {
        $safeSummary = is_array($summary)
            ? json_encode($this->redact($summary), JSON_UNESCAPED_SLASHES)
            : $this->redactString((string) $summary);

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_reference' => $entityReference,
            'summary' => $safeSummary,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redact(array $payload): array
    {
        $out = [];

        foreach ($payload as $key => $value) {
            $normalized = Str::lower((string) $key);

            if ($this->isSensitiveKey($normalized)) {
                $out[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->redact($value);
                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    public function redactString(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        foreach (self::SENSITIVE_KEYS as $needle) {
            if (Str::contains(Str::lower($value), $needle.'=')) {
                return '[REDACTED]';
            }
        }

        return $value;
    }

    private function isSensitiveKey(string $normalized): bool
    {
        foreach (self::SENSITIVE_KEYS as $needle) {
            if ($normalized === $needle || Str::contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
