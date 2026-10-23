<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            SequenceSeeder::class,
            LookupSeeder::class,
            DemoUserSeeder::class,
            CatalogueInventorySeeder::class,
            DemoScenarioSeeder::class,
        ]);
    }
}
