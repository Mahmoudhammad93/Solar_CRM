<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view') || $user->hasPermission('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create') || $user->hasPermission('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermission('leads.edit') || $user->hasPermission('customers.edit');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.delete') || $user->isSuperAdmin();
    }
}
