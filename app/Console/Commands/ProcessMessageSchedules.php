<?php

namespace App\Console\Commands;

use App\Services\LineBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMessageSchedules extends Command
{
    protected $signature = 'messages:process-schedules';
    protected $description = 'message_schedulesテーブルの未送信レコードを処理してLINEプッシュ通知を送信';

    public function handle(LineBotService $lineBotService): int
    {
        $schedules = DB::table('message_schedules as ms')
            ->join('users as u', 'u.id', '=', 'ms.user_id')
            ->leftJoin('coupon_templates as ct', 'ct.id', '=', 'ms.coupon_template_id')
            ->where('ms.status', 'pending')
            ->where('ms.run_at', '<=', now())
            ->select([
                'ms.id',
                'ms.store_id',
                'ms.user_id',
                'ms.schedule_type',
                'ms.coupon_template_id',
                'u.line_user_id',
                'u.display_name',
                'ct.title as coupon_title',
            ])
            ->orderBy('ms.run_at')
            ->limit(100)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No pending schedules found.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            $message = $this->buildMessage($schedule);

            $ok = $lineBotService->pushText($schedule->line_user_id, $message);

            if ($ok) {
                DB::table('message_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'updated_at' => now(),
                    ]);
                $sent++;
            } else {
                $failed++;
                Log::warning('ProcessMessageSchedules: failed to send', [
                    'schedule_id' => $schedule->id,
                    'user_id' => $schedule->user_id,
                ]);
            }
        }

        $this->info("Processed: {$sent} sent, {$failed} failed.");

        return self::SUCCESS;
    }

    protected function buildMessage(object $schedule): string
    {
        $name = $schedule->display_name ?? 'お客様';

        return match ($schedule->schedule_type) {
            'birthday' => "{$name}さん、お誕生日おめでとうございます！\nバースデークーポンをプレゼントします。",
            'inactive' => "{$name}さん、お久しぶりです！\nまたのご来店をお待ちしております。",
            'rank_up' => "{$name}さん、ランクアップおめでとうございます！"
                . ($schedule->coupon_title ? "\nクーポン「{$schedule->coupon_title}」が発行されました。" : ''),
            'stamp' => "{$name}さん、スタンプが貯まりました！"
                . ($schedule->coupon_title ? "\nクーポン「{$schedule->coupon_title}」をご確認ください。" : ''),
            default => "{$name}さんへのお知らせがあります。",
        };
    }
}
