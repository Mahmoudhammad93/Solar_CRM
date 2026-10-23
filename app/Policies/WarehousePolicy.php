<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view') || $user->hasPermission('settings.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.create') || $user->hasPermission('inventory.settings') || $user->isSuperAdmin();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('inventory.edit') || $user->hasPermission('inventory.settings') || $user->isSuperAdmin();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('inventory.delete');
    }
}
