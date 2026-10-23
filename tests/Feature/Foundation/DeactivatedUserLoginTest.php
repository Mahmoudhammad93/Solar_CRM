<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeactivatedUserLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_deactivated_user_cannot_access_panel(): void
    {
        $user = User::factory()->withRole('sales-executive')->inactive()->create([
            'password' => 'Password1!',
        ]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_deactivated_user_login_is_rejected(): void
    {
        $user = User::factory()->withRole('sales-executive')->inactive()->create([
            'email' => 'inactive-login@solar-crm.test',
            'password' => 'Password1!',
        ]);

        $response = Livewire::test(\Filament\Auth\Pages\Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'Password1!',
            ])
            ->call('authenticate');

        $response->assertHasFormErrors(['email']);
        $this->assertGuest();
    }

    public function test_active_user_can_access_panel(): void
    {
        $user = User::factory()->withRole('super-admin')->create();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }
}
