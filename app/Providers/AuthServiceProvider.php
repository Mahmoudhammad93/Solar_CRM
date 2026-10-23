<?php

namespace App\Providers;

use App\Models\AmcContract;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Policies\AmcContractPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\SettingsPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TicketPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VisitPolicy;
use App\Policies\WarehousePolicy;
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
        Product::class => ProductPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Vendor::class => VendorPolicy::class,
        Quotation::class => QuotationPolicy::class,
        Order::class => OrderPolicy::class,
        Project::class => ProjectPolicy::class,
        Ticket::class => TicketPolicy::class,
        AmcContract::class => AmcContractPolicy::class,
        Invoice::class => InvoicePolicy::class,
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
