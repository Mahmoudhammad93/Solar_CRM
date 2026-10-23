<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->updateOrCreate(
            ['code' => 'CAI'],
            ['name' => 'Cairo Demo Branch', 'is_active' => true],
        );

        $team = Team::query()->updateOrCreate(
            ['code' => 'SALES-A'],
            ['name' => 'Sales Team A', 'branch_id' => $branch->id, 'is_active' => true],
        );

        $users = [
            [
                'name' => 'Ada Super Admin',
                'email' => 'admin@solar-crm.test',
                'role' => 'super-admin',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Sam Sales Manager',
                'email' => 'manager@solar-crm.test',
                'role' => 'sales-manager',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Eva Sales Executive',
                'email' => 'executive@solar-crm.test',
                'role' => 'sales-executive',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Sid Surveyor',
                'email' => 'surveyor@solar-crm.test',
                'role' => 'surveyor-engineer',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Pat Project Manager',
                'email' => 'pm@solar-crm.test',
                'role' => 'project-manager',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Ivy Inventory Service',
                'email' => 'ops@solar-crm.test',
                'role' => 'inventory-finance-service',
                'password' => 'Password1!',
            ],
            [
                'name' => 'Deactivated Demo User',
                'email' => 'inactive@solar-crm.test',
                'role' => 'sales-executive',
                'password' => 'Password1!',
                'is_active' => false,
            ],
        ];

        foreach ($users as $data) {
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();

            $user = User::query()->firstOrNew(['email' => $data['email']]);
            $user->fill([
                'name' => $data['name'],
                'phone' => '+2010000000'.substr((string) $role->id, -1),
                'password' => $data['password'],
                'branch_id' => $branch->id,
                'team_id' => $team->id,
            ]);
            $user->forceFill([
                'role_id' => $role->id,
                'is_active' => $data['is_active'] ?? true,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
