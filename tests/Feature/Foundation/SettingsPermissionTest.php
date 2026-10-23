<?php

namespace Tests\Feature\Foundation;

use App\Filament\Pages\ManageSettings;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sales_executive_cannot_update_settings_via_gate(): void
    {
        $user = User::factory()->withRole('sales-executive')->create();

        $this->actingAs($user);

        $this->assertFalse(Gate::forUser($user)->allows('settings.update'));
        $this->assertFalse($user->hasPermission('settings.edit'));
        $this->assertFalse(ManageSettings::canAccess());
    }

    public function test_sales_executive_cannot_save_settings_via_livewire(): void
    {
        $user = User::factory()->withRole('sales-executive')->create();

        Livewire::actingAs($user)
            ->test(ManageSettings::class)
            ->assertForbidden();
    }

    public function test_super_admin_can_access_settings(): void
    {
        $user = User::factory()->withRole('super-admin')->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::forUser($user)->allows('settings.update'));
        $this->assertTrue(ManageSettings::canAccess());
    }
}
