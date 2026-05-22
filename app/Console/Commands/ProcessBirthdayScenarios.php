<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBirthdayScenarios extends Command
{
    protected $signature = 'visit-scenario:process-birthday';
    protected $description = '誕生月配信シナリオ（birthday_month）を処理（毎月1日に実行）';

    public function handle(): int
    {
        $currentHour  = (int) now()->format('H');
        $currentMonth = (int) now()->format('n'); // 1〜12

        $scenarios = DB::table('visit_scenarios')
            ->where('is_active', true)
            ->where('trigger_type', 'birthday_month')
            ->where('send_hour', $currentHour)
            ->get();

        if ($scenarios->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($scenarios as $scenario) {
            $this->processScenario($scenario, $currentMonth);
        }

        return self::SUCCESS;
    }

    private function processScenario(object $scenario, int $currentMonth): void
    {
        // 今月が誕生月のユーザーを抽出
        $users = DB::table('users')
            ->where('current_card_id', $scenario->stamp_card_definition_id)
            ->whereNotNull('line_user_id')
            ->where('birth_month', $currentMonth)
            ->get();

        if ($users->isEmpty()) {
            $this->info("Birthday scenario #{$scenario->id}: no target users for month {$currentMonth}");
            return;
        }

        $queued = 0;

        foreach ($users as $user) {
            // 今月すでに送信済みならスキップ（同一シナリオ×同一月の重複防止）
            $alreadySent = DB::table('visit_scenario_sends')
                ->where('user_id', $user->id)
                ->where('scenario_id', $scenario->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            DB::table('visit_scenario_sends')->insert([
                'user_id'              => $user->id,
                'scenario_id'          => $scenario->id,
                'scheduled_at'         => now(),
                'sent_at'              => null,
                'coupon_issued_at'     => null,
                'user_coupon_id'       => null,
                'reminder_scheduled_at' => null,
                'reminder_sent_at'     => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $queued++;
        }

        $this->info("Birthday scenario #{$scenario->id}: queued {$queued} / {$users->count()} users (month={$currentMonth})");
    }
}
