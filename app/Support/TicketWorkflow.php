<?php

namespace App\Support;

final class TicketWorkflow
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'open' => ['assigned', 'closed'],
        'assigned' => ['in_progress', 'waiting_customer', 'waiting_parts', 'closed'],
        'in_progress' => ['waiting_customer', 'waiting_parts', 'resolved', 'closed'],
        'waiting_customer' => ['in_progress', 'resolved', 'closed'],
        'waiting_parts' => ['in_progress', 'resolved', 'closed'],
        'resolved' => ['closed', 'in_progress'],
        'closed' => [],
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
        return [
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'waiting_customer' => 'Waiting for Customer',
            'waiting_parts' => 'Waiting for Parts',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }
}
