<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SettingVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    private const CACHE_KEY = 'app.settings.map';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $map = $this->all();

        if (! array_key_exists($key, $map)) {
            return $default;
        }

        return $this->castValue($map[$key]['value'], $map[$key]['type']);
    }

    /**
     * @return array<string, array{value: mixed, type: string, group: string, unit: ?string, status: string, is_sample: bool, source_reference: ?string, effective_from: mixed}>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()
                ->where('status', 'active')
                ->get()
                ->keyBy('key')
                ->map(fn (Setting $setting) => [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'unit' => $setting->unit,
                    'status' => $setting->status,
                    'is_sample' => $setting->is_sample,
                    'source_reference' => $setting->source_reference,
                    'effective_from' => $setting->effective_from,
                ])
                ->all();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(string $key, mixed $value, array $attributes = [], ?int $userId = null): Setting
    {
        return DB::transaction(function () use ($key, $value, $attributes, $userId) {
            $setting = Setting::query()->where('key', $key)->lockForUpdate()->first();

            $type = $attributes['type'] ?? $setting?->type ?? 'string';
            $stored = $this->serializeValue($value, $type);

            if (! $setting) {
                $setting = new Setting([
                    'key' => $key,
                    'group' => $attributes['group'] ?? 'general',
                ]);
            }

            $before = $setting->exists ? $setting->value : null;

            $setting->fill([
                'group' => $attributes['group'] ?? $setting->group ?? 'general',
                'value' => $stored,
                'type' => $type,
                'unit' => $attributes['unit'] ?? $setting->unit,
                'effective_from' => $attributes['effective_from'] ?? $setting->effective_from ?? now()->toDateString(),
                'status' => $attributes['status'] ?? $setting->status ?? 'active',
                'source_reference' => $attributes['source_reference'] ?? $setting->source_reference,
                'is_sample' => $attributes['is_sample'] ?? $setting->is_sample ?? true,
                'updated_by' => $userId,
            ]);
            $setting->save();

            SettingVersion::query()->create([
                'setting_id' => $setting->id,
                'value' => $setting->value,
                'type' => $setting->type,
                'unit' => $setting->unit,
                'effective_from' => $setting->effective_from,
                'status' => $setting->status,
                'source_reference' => $setting->source_reference,
                'is_sample' => $setting->is_sample,
                'changed_by' => $userId,
                'change_summary' => $attributes['change_summary'] ?? "Updated setting {$key}",
                'created_at' => now(),
            ]);

            $this->forgetCache();

            $this->auditLogger->log(
                action: 'setting.updated',
                entityType: Setting::class,
                entityId: $setting->id,
                entityReference: $key,
                summary: [
                    'group' => $setting->group,
                    'key' => $key,
                    'before' => $this->safeAuditValue($key, $before),
                    'after' => $this->safeAuditValue($key, $setting->value),
                    'type' => $setting->type,
                ],
            );

            return $setting->fresh();
        });
    }

    public function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => $value === null || $value === '' ? null : (int) $value,
            'float', 'number' => $value === null || $value === '' ? null : (float) $value,
            'json', 'array' => is_string($value) ? (json_decode($value, true) ?? []) : $value,
            default => $value,
        };
    }

    public function serializeValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };
    }

    private function safeAuditValue(string $key, mixed $value): mixed
    {
        $lower = strtolower($key);

        foreach (['password', 'token', 'secret', 'api_key'] as $needle) {
            if (str_contains($lower, $needle)) {
                return '[REDACTED]';
            }
        }

        return $value;
    }
}
