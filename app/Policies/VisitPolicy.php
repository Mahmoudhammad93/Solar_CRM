<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
use App\Support\RecordScoping;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $user->hasPermission('visits.view')
            && RecordScoping::canAccessOwnedRecord($user, $visit->assigned_surveyor_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('visits.create');
    }

    public function update(User $user, Visit $visit): bool
    {
        return $user->hasPermission('visits.edit')
            && RecordScoping::canAccessOwnedRecord($user, $visit->assigned_surveyor_id);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->hasPermission('visits.delete')
            && RecordScoping::canAccessOwnedRecord($user, $visit->assigned_surveyor_id);
    }

    public function downloadReport(User $user, Visit $visit): bool
    {
        return $this->view($user, $visit);
    }
}
