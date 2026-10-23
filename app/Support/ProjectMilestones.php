<?php

namespace App\Support;

final class ProjectMilestones
{
    /**
     * Default milestone catalogue (weights sum to 100).
     *
     * @return list<array{key: string, label: string, weight: float}>
     */
    public static function defaults(): array
    {
        return [
            ['key' => 'order_confirmed', 'label' => 'Order Confirmed', 'weight' => 5],
            ['key' => 'documents_pending', 'label' => 'Documents Pending', 'weight' => 5],
            ['key' => 'site_technical_approval', 'label' => 'Site/Technical Approval', 'weight' => 10],
            ['key' => 'material_planning', 'label' => 'Material Planning', 'weight' => 10],
            ['key' => 'material_ready', 'label' => 'Material Ready', 'weight' => 10],
            ['key' => 'installation_scheduled', 'label' => 'Installation Scheduled', 'weight' => 10],
            ['key' => 'installation_in_progress', 'label' => 'Installation In Progress', 'weight' => 20],
            ['key' => 'inspection_testing', 'label' => 'Inspection/Testing', 'weight' => 10],
            ['key' => 'commissioned_handed_over', 'label' => 'Commissioned/Handed Over', 'weight' => 15],
            ['key' => 'closed', 'label' => 'Closed', 'weight' => 5],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            'pending' => ['active', 'hold', 'exception', 'completed'],
            'active' => ['completed', 'hold', 'exception'],
            'hold' => ['active', 'exception', 'completed'],
            'exception' => ['active', 'hold', 'completed'],
            'completed' => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(strtolower($to), self::transitions()[strtolower($from)] ?? [], true);
    }
}
