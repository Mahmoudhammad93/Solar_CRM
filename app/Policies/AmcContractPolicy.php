<?php

namespace App\Policies;

use App\Models\AmcContract;
use App\Models\User;
use App\Support\RecordScoping;

class AmcContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('amc.view');
    }

    public function view(User $user, AmcContract $contract): bool
    {
        return $user->hasPermission('amc.view')
            && RecordScoping::canAccessOwnedRecord($user, $contract->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('amc.create');
    }

    public function update(User $user, AmcContract $contract): bool
    {
        return $user->hasPermission('amc.edit')
            && RecordScoping::canAccessOwnedRecord($user, $contract->owner_id);
    }
}
