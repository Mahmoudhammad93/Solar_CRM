<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view') || $user->hasPermission('products.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.create') || $user->hasPermission('products.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('inventory.edit') || $user->hasPermission('products.edit');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('inventory.delete');
    }
}
