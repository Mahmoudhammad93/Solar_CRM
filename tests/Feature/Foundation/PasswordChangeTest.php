<?php

namespace Tests\Feature\Foundation;

use App\Filament\Pages\ChangePassword;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_password_change_succeeds_with_valid_current_password(): void
    {
        $user = User::factory()->withRole('super-admin')->create([
            'password' => 'Password1!',
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass2!', $user->password));
        $this->assertFalse(Hash::check('Password1!', $user->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->withRole('sales-executive')->create([
            'password' => 'Password1!',
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'WrongPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password']);

        $user->refresh();
        $this->assertTrue(Hash::check('Password1!', $user->password));
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $user = User::factory()->withRole('sales-executive')->create([
            'password' => 'Password1!',
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'Mismatch3!',
            ])
            ->call('save')
            ->assertHasFormErrors(['password_confirmation']);
    }
}
