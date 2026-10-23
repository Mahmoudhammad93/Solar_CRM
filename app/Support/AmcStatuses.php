<?php

namespace App\Support;

final class AmcStatuses
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'expiring' => 'Expiring',
            'expired' => 'Expired',
            'renewed' => 'Renewed',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'draft' => ['active', 'cancelled'],
        'active' => ['expiring', 'expired', 'renewed', 'cancelled'],
        'expiring' => ['active', 'expired', 'renewed', 'cancelled'],
        'expired' => ['renewed', 'cancelled'],
        'renewed' => ['active'],
        'cancelled' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(strtolower($to), self::TRANSITIONS[strtolower($from)] ?? [], true);
    }
}
