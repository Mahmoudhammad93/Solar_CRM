<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use App\Support\RecordScoping;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view')
            && RecordScoping::canAccessOwnedRecord($user, $quotation->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.edit')
            && RecordScoping::canAccessOwnedRecord($user, $quotation->owner_id);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.delete')
            && RecordScoping::canAccessOwnedRecord($user, $quotation->owner_id);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.approve') || $user->isSuperAdmin();
    }
}
