<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\SettingsPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        Gate::define('settings.view', [SettingsPolicy::class, 'view']);
        Gate::define('settings.update', [SettingsPolicy::class, 'update']);

        Gate::before(function (?User $user, string $ability) {
            if ($user?->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('permission', function (User $user, string $key): bool {
            return $user->hasPermission($key);
        });
    }
}
