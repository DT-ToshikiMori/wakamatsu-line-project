<?php

namespace App\Console\Commands;

use App\Services\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessChurnScenarios extends Command
{
    protected $signature = 'messages:process-churn';
    protected $description = '離脱防止シナリオを処理（毎分実行）';

    public function handle(MessageService $messageService): int
    {
        $now = now();
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

        // 現在の時刻にマッチするアクティブなシナリオを取得
        $scenarios = DB::table('churn_scenarios')
            ->where('is_active', true)
            ->where('send_hour', $currentHour)
            ->where('send_minute', $currentMinute)
            ->get();

        if ($scenarios->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($scenarios as $scenario) {
            $this->processScenario($scenario, $messageService);
        }

        return self::SUCCESS;
    }

    private function processScenario(object $scenario, MessageService $messageService): void
    {
        $targetDate = now()->subDays($scenario->days_after_last_stamp)->startOfDay();

        // 最終来店日がX日前のユーザーを取得（その日に来店して以降来ていない）
        $users = DB::table('users')
            ->where('store_id', $scenario->store_id)
            ->whereNotNull('line_user_id')
            ->whereNotNull('last_visit_at')
            ->whereDate('last_visit_at', $targetDate)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        // シナリオのバブルを取得
        $bubbles = DB::table('message_bubbles')
            ->where('parent_type', 'churn_scenario')
            ->where('parent_id', $scenario->id)
            ->orderBy('position')
            ->get()
            ->all();

        if (empty($bubbles)) {
            return;
        }

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                $messageService->sendToUser($user->id, $bubbles, 'inactive');
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error('ProcessChurnScenarios: send failed', [
                    'scenario_id' => $scenario->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Scenario #{$scenario->id} '{$scenario->name}': sent to {$sentCount} users");
    }
}
