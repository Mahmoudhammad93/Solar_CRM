<?php

namespace App\Support;

use App\Models\Lookup;

final class AmcWorkflow
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'draft' => ['active', 'cancelled'],
        'active' => ['suspended', 'expired', 'cancelled'],
        'suspended' => ['active', 'cancelled'],
        'expired' => ['active'],
        'cancelled' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(strtolower($to), self::TRANSITIONS[strtolower($from)] ?? [], true);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $opts = Lookup::options('amc_status');

        return $opts !== [] ? $opts : [
            'draft' => 'Draft',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
        ];
    }
}
