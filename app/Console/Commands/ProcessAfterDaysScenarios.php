<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAfterDaysScenarios extends Command
{
    protected $signature = 'visit-scenario:process-after-days';
    protected $description = '離脱防止シナリオ（after_days）を処理（毎分実行）';

    public function handle(): int
    {
        $currentHour = (int) now()->format('H');

        $scenarios = DB::table('visit_scenarios')
            ->where('is_active', true)
            ->where('trigger_type', 'after_days')
            ->where('send_hour', $currentHour)
            ->get();

        foreach ($scenarios as $scenario) {
            $this->processScenario($scenario);
        }

        return self::SUCCESS;
    }

    private function processScenario(object $scenario): void
    {
        $targetDate = now()->subDays((int) $scenario->trigger_days)->toDateString();

        $users = DB::table('users')
            ->where('current_card_id', $scenario->stamp_card_definition_id)
            ->whereNotNull('line_user_id')
            ->whereNotNull('last_visit_at')
            ->whereDate('last_visit_at', $targetDate)
            ->get();

        foreach ($users as $user) {
            $repeat = (bool) ($scenario->repeat ?? false);
            if (!$repeat) {
                $exists = DB::table('visit_scenario_sends')
                    ->where('user_id', $user->id)
                    ->where('scenario_id', $scenario->id)
                    ->exists();
                if ($exists) {
                    continue;
                }
            }

            DB::table('visit_scenario_sends')->insert([
                'user_id' => $user->id,
                'scenario_id' => $scenario->id,
                'scheduled_at' => now()->addHours((int) ($scenario->delay_hours ?? 0)),
                'sent_at' => null,
                'coupon_issued_at' => null,
                'user_coupon_id' => null,
                'reminder_scheduled_at' => null,
                'reminder_sent_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("after_days scenario #{$scenario->id}: queued for {$users->count()} users");
    }
}
