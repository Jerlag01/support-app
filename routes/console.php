<?php

use App\Support\Updater;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update from git: the admin page "System update" queues a request, cron runs it.
// Needs: * * * * * cd /path/to/app && php8.4 artisan schedule:run  (crontab of www-data)
Schedule::call(fn () => app(Updater::class)->heartbeat())->everyMinute()->name('updater-heartbeat');
Schedule::command('app:update --if-requested')->everyMinute()->withoutOverlapping(30)->runInBackground();
