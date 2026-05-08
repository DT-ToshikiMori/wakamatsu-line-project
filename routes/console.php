<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// メッセージ配信スケジュール
// Schedule::command('messages:process-churn')->everyMinute(); // 統合済み → visit-scenario:process-after-days へ
Schedule::command('visit-scenario:process-after-days')->everyMinute();
Schedule::command('messages:process-broadcasts')->everyMinute();
Schedule::command('messages:process-schedules')->everyMinute();

// クーポン有効期限チェック（5分ごと）
Schedule::command('coupons:expire')->everyFiveMinutes();

// クーポン期限前リマインド（1時間ごと）
Schedule::command('coupon:remind')->hourly();

// 来店シナリオ通知バッチ（5分ごと）
Schedule::command('visit-scenario:send')->everyFiveMinutes();
