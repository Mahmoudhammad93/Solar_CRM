<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\RecordScoping;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.view')
            && RecordScoping::canAccessOwnedRecord($user, $lead->assigned_to);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.edit')
            && RecordScoping::canAccessOwnedRecord($user, $lead->assigned_to);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.delete')
            && RecordScoping::canAccessOwnedRecord($user, $lead->assigned_to);
    }

    public function assign(User $user, Lead $lead): bool
    {
        if (! $user->hasPermission('leads.edit') && ! $user->hasPermission('leads.approve')) {
            return false;
        }

        return $user->isSuperAdmin() || RecordScoping::isSalesManager($user);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('leads.export') || $user->hasPermission('leads.view');
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }
}
