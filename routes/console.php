<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publish scheduled posts every minute (requires `php artisan schedule:run` via system cron in production — see README).
Schedule::command('posts:publish-scheduled')->everyMinute();
