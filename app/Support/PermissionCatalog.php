<?php

namespace App\Support;

final class PermissionCatalog
{
    public const MODULES = [
        'dashboard',
        'leads',
        'customers',
        'visits',
        'tasks',
        'calculators',
        'quotations',
        'products',
        'documents',
        'orders',
        'projects',
        'inventory',
        'tickets',
        'amc',
        'reports',
        'users',
        'settings',
        'audit',
    ];

    public const ACTIONS = [
        'view',
        'create',
        'edit',
        'delete',
        'export',
        'approve',
        'settings',
    ];

    /**
     * @return list<array{module: string, action: string, key: string, label: string}>
     */
    public static function all(): array
    {
        $items = [];

        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $items[] = [
                    'module' => $module,
                    'action' => $action,
                    'key' => "{$module}.{$action}",
                    'label' => ucfirst($module).' · '.ucfirst($action),
                ];
            }
        }

        return $items;
    }

    /**
     * Default permission keys granted per role slug (excluding super-admin which gets all).
     *
     * @return array<string, list<string>>
     */
    public static function defaultRoleGrants(): array
    {
        $allKeys = array_column(self::all(), 'key');

        $moduleActions = function (array $modules, array $actions) use ($allKeys): array {
            $keys = [];
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    $key = "{$module}.{$action}";
                    if (in_array($key, $allKeys, true)) {
                        $keys[] = $key;
                    }
                }
            }

            return $keys;
        };

        return [
            'super-admin' => $allKeys,
            'sales-manager' => array_values(array_unique(array_merge(
                $moduleActions(['dashboard', 'leads', 'visits', 'tasks', 'calculators', 'quotations', 'customers', 'reports'], ['view', 'create', 'edit', 'export', 'approve']),
                $moduleActions(['leads', 'quotations'], ['delete']),
                ['users.view'],
            ))),
            'sales-executive' => $moduleActions(
                ['dashboard', 'leads', 'customers', 'visits', 'tasks', 'calculators', 'quotations', 'products', 'documents'],
                ['view', 'create', 'edit']
            ),
            'surveyor-engineer' => $moduleActions(
                ['dashboard', 'visits', 'documents', 'tasks'],
                ['view', 'create', 'edit']
            ),
            'project-manager' => $moduleActions(
                ['dashboard', 'orders', 'projects', 'inventory', 'documents', 'tasks', 'reports'],
                ['view', 'create', 'edit', 'export']
            ),
            'inventory-finance-service' => $moduleActions(
                ['dashboard', 'inventory', 'products', 'orders', 'tickets', 'amc', 'reports', 'documents'],
                ['view', 'create', 'edit', 'export']
            ),
        ];
    }
}
