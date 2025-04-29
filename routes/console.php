<?php

use App\Console\Commands\DailyTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DailyTask::class)->dailyAt('18:00');
// Schedule::command(DailyTask::class)->everyFiveSeconds();
