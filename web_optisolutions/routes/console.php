<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('issues:analyze')->hourly()->withoutOverlapping();

// Emails patients ~30 minutes before their visit asking them to confirm they are coming.
Schedule::command('visits:send-reminders')->everyMinute()->withoutOverlapping();