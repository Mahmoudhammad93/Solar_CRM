<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Support\RecordScoping;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.view')
            && RecordScoping::canAccessOwnedRecord($user, $ticket->assignee_id ?? $ticket->created_by);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.edit')
            && RecordScoping::canAccessOwnedRecord($user, $ticket->assignee_id ?? $ticket->created_by);
    }
}
