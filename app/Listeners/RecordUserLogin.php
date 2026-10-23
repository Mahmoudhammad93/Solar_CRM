<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (method_exists($user, 'forceFill')) {
            $user->forceFill(['last_login_at' => now()])->save();
        }
    }
}
