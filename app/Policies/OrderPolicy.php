<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\RecordScoping;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view')
            && RecordScoping::canAccessOwnedRecord($user, $order->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.edit')
            && RecordScoping::canAccessOwnedRecord($user, $order->owner_id);
    }
}
