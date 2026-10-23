<?php

namespace App\Policies;

use App\Models\User;

class SettingsPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermission('settings.view') || $user->hasPermission('settings.edit') || $user->hasPermission('settings.settings');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('settings.edit') || $user->hasPermission('settings.settings');
    }
}
