<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Lead;
use App\Models\User;
use App\Models\Visit;
use App\Support\RecordScoping;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if (! $user->hasPermission('documents.view') && ! $user->hasPermission('visits.view') && ! $user->hasPermission('leads.view')) {
            return false;
        }

        if ($document->is_sensitive && ! $user->isSuperAdmin() && ! $user->hasPermission('documents.settings')) {
            // Sensitive docs: admin or users with documents.settings / approve
            if (! $user->hasPermission('documents.approve') && $user->role?->slug !== 'sales-manager') {
                return false;
            }
        }

        return $this->canAccessOwner($user, $document);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.create')
            || $user->hasPermission('visits.edit')
            || $user->hasPermission('leads.edit');
    }

    public function delete(User $user, Document $document): bool
    {
        if (! $user->hasPermission('documents.delete') && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->canAccessOwner($user, $document);
    }

    private function canAccessOwner(User $user, Document $document): bool
    {
        $owner = $document->documentable;
        if (! $owner) {
            return $user->isSuperAdmin();
        }

        if ($owner instanceof Visit) {
            return RecordScoping::canAccessOwnedRecord($user, $owner->assigned_surveyor_id);
        }

        if ($owner instanceof Lead) {
            return RecordScoping::canAccessOwnedRecord($user, $owner->assigned_to);
        }

        return $user->isSuperAdmin() || $user->hasPermission('documents.view');
    }
}
