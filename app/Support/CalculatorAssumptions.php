<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\SettingsService;

final class CalculatorAssumptions
{
    public const DISCLAIMER = 'Indicative estimate, not engineering certification. Detailed engineering validation is required before procurement or installation.';

    /**
     * @param  list<string>  $keys
     * @return array<string, array{value:mixed,unit:?string,effective_from:mixed,source_reference:?string,is_sample:bool,type:string}>
     */
    public static function snapshotKeys(SettingsService $settings, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $row = Setting::query()->where('key', $key)->first();
            $meta = $settings->all()[$key] ?? null;
            $out[$key] = [
                'value' => $settings->get($key, $row?->value),
                'unit' => $meta['unit'] ?? $row?->unit,
                'effective_from' => $meta['effective_from'] ?? $row?->effective_from,
                'source_reference' => $meta['source_reference'] ?? $row?->source_reference,
                'is_sample' => (bool) ($meta['is_sample'] ?? $row?->is_sample ?? true),
                'type' => $meta['type'] ?? $row?->type ?? 'string',
            ];
        }

        return $out;
    }

    public static function sampleLabel(array $assumptionRow): string
    {
        if (! empty($assumptionRow['is_sample']) || empty($assumptionRow['source_reference']) || $assumptionRow['source_reference'] === 'seed') {
            return 'Sample/Demo';
        }

        return (string) $assumptionRow['source_reference'];
    }
}
