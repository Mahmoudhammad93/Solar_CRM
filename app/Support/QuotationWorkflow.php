<?php

namespace App\Support;

final class QuotationWorkflow
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'draft' => ['review', 'sent', 'rejected'],
        'review' => ['draft', 'sent', 'rejected'],
        'sent' => ['accepted', 'rejected', 'expired'],
        'accepted' => [],
        'rejected' => ['draft'],
        'expired' => ['draft'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        $from = strtolower($from);
        $to = strtolower($to);

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function locksRevision(string $status): bool
    {
        return in_array(strtolower($status), ['sent', 'accepted', 'expired'], true);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'draft' => 'Draft',
            'review' => 'Review',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ];
    }
}
