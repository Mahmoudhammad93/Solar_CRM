<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Policies\CustomerPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\LeadPolicy;
use App\Policies\SettingsPolicy;
use App\Policies\TaskPolicy;
use App\Policies\VisitPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lead::class => LeadPolicy::class,
        Task::class => TaskPolicy::class,
        Customer::class => CustomerPolicy::class,
        Visit::class => VisitPolicy::class,
        Document::class => DocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

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
