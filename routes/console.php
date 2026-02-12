<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// メッセージ配信スケジュール
Schedule::command('messages:process-churn')->everyMinute();
Schedule::command('messages:process-broadcasts')->everyMinute();
Schedule::command('messages:process-schedules')->everyMinute();
