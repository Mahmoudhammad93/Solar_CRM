<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily-command-ready AMC renewal list (automation Partial — requires scheduler process).
Schedule::command('amc:renewal-reminders')->dailyAt('08:00')->withoutOverlapping();
