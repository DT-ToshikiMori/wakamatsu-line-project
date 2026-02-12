<?php

namespace App\Console\Commands;

use App\Services\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBroadcasts extends Command
{
    protected $signature = 'messages:process-broadcasts';
    protected $description = '予定時刻を過ぎた自由配信メッセージを処理';

    public function handle(MessageService $messageService): int
    {
        $now = now();

        // scheduled_at を過ぎた draft/scheduled の broadcasts を処理
        $broadcasts = DB::table('broadcasts')
            ->whereIn('status', ['draft', 'scheduled'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        if ($broadcasts->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($broadcasts as $broadcast) {
            $this->processBroadcast($broadcast, $messageService);
        }

        return self::SUCCESS;
    }

    private function processBroadcast(object $broadcast, MessageService $messageService): void
    {
        // バブル取得
        $bubbles = DB::table('message_bubbles')
            ->where('parent_type', 'broadcast')
            ->where('parent_id', $broadcast->id)
            ->orderBy('position')
            ->get()
            ->all();

        if (empty($bubbles)) {
            $this->warn("Broadcast #{$broadcast->id} has no bubbles, skipping");
            DB::table('broadcasts')->where('id', $broadcast->id)->update([
                'status' => 'sent',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        // 対象ユーザー抽出
        $query = DB::table('users')
            ->where('store_id', $broadcast->store_id)
            ->whereNotNull('line_user_id');

        if ($broadcast->filter_type === 'filtered') {
            // ランク絞り込み
            if (!empty($broadcast->filter_rank_card_id)) {
                $query->where('current_card_id', $broadcast->filter_rank_card_id);
            }

            // 最終来店からX日以上
            if (!empty($broadcast->filter_days_since_visit)) {
                $cutoff = now()->subDays($broadcast->filter_days_since_visit);
                $query->where(function ($q) use ($cutoff) {
                    $q->where('last_visit_at', '<=', $cutoff)
                      ->orWhereNull('last_visit_at');
                });
            }

            // 来店回数X回以上
            if (!empty($broadcast->filter_min_visits)) {
                $query->where('visit_count', '>=', $broadcast->filter_min_visits);
            }
        }

        $users = $query->get();
        $sentCount = 0;

        foreach ($users as $user) {
            try {
                $messageService->sendToUser($user->id, $bubbles, 'manual');

                // broadcast_logs に記録
                DB::table('broadcast_logs')->insert([
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $user->id,
                    'sent_at' => now(),
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                Log::error('ProcessBroadcasts: send failed', [
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ステータス更新
        DB::table('broadcasts')->where('id', $broadcast->id)->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $sentCount,
            'updated_at' => now(),
        ]);

        $this->info("Broadcast #{$broadcast->id} '{$broadcast->name}': sent to {$sentCount} users");
    }
}
