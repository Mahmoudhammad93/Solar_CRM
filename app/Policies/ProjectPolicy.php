<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view') || $user->hasPermission('orders.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit') || $user->isSuperAdmin();
    }
}
