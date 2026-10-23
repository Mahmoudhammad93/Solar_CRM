<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::all() as $item) {
            Permission::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'module' => $item['module'],
                    'action' => $item['action'],
                    'label' => $item['label'],
                ],
            );
        }

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full system access'],
            ['name' => 'Sales Manager', 'slug' => 'sales-manager', 'description' => 'Team leads, approvals, pipeline'],
            ['name' => 'Sales Executive', 'slug' => 'sales-executive', 'description' => 'Assigned leads and own pipeline'],
            ['name' => 'Surveyor / Engineer', 'slug' => 'surveyor-engineer', 'description' => 'Assigned site visits'],
            ['name' => 'Project Manager / Operations', 'slug' => 'project-manager', 'description' => 'Orders, projects, installation'],
            ['name' => 'Inventory / Finance / Service', 'slug' => 'inventory-finance-service', 'description' => 'Stock, tickets, AMC'],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_system' => true,
                ],
            );
        }

        $grants = PermissionCatalog::defaultRoleGrants();
        $permissions = Permission::query()->get()->keyBy('key');

        foreach ($grants as $slug => $keys) {
            $role = Role::query()->where('slug', $slug)->firstOrFail();
            $ids = collect($keys)
                ->map(fn (string $key) => $permissions[$key]->id ?? null)
                ->filter()
                ->values()
                ->all();
            $role->permissions()->sync($ids);
        }
    }
}
