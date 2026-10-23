<?php

namespace App\Providers;

use App\Listeners\RecordUserLogin;
use App\Services\AuditLogger;
use App\Services\BrandingService;
use App\Services\NumberingService;
use App\Services\SettingsService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(BrandingService::class);
        $this->app->singleton(NumberingService::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, RecordUserLogin::class);

        Password::defaults(function () {
            $min = 8;

            try {
                $min = (int) app(SettingsService::class)->get('security.password_min_length', 8);
            } catch (\Throwable) {
                // Settings table may not exist during early migrate
            }

            return Password::min(max(8, $min))->mixedCase()->numbers();
        });
    }
}
