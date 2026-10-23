<?php

namespace App\Support;

/**
 * Rounding policy for quotations (Phase 6):
 * - Work in integer minor units (paise) where 1 major = 100 minor.
 * - Round half-up when converting from float to minor units.
 * - Documented in docs/calculation-spec.md appendix / phase report.
 */
final class Money
{
    public static function toMinor(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function fromMinor(int $minor): float
    {
        return round($minor / 100, 2, PHP_ROUND_HALF_UP);
    }

    public static function roundMajor(float|string $amount): float
    {
        return self::fromMinor(self::toMinor($amount));
    }
}
