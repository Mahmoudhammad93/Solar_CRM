<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view') || $user->hasPermission('inventory.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create') || $user->hasPermission('inventory.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.edit') || $user->hasPermission('inventory.edit');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete') || $user->isSuperAdmin();
    }
}
