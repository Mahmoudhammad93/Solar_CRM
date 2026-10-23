<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Support\RecordScoping;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view') || $user->hasPermission('reports.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $ownerId = $invoice->order?->owner_id;

        return RecordScoping::canAccessOwnedRecord($user, $ownerId);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create') || $user->hasPermission('orders.edit');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->create($user) && $this->view($user, $invoice);
    }
}
