<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\RecordScoping;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.view')
            && RecordScoping::canAccessOwnedRecord($user, $task->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit')
            && RecordScoping::canAccessOwnedRecord($user, $task->owner_id);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.delete')
            && RecordScoping::canAccessOwnedRecord($user, $task->owner_id);
    }
}
