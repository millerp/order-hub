<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('kafka:publish-outbox --once')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('outbox:prune --hours=72')
    ->dailyAt('03:00')
    ->withoutOverlapping();
