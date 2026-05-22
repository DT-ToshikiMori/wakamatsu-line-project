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
        $query = DB::table('users')->whereNotNull('line_user_id');

        // 複数店舗フィルター（store_ids JSON配列）
        $storeIds = $broadcast->store_ids ? json_decode($broadcast->store_ids, true) : null;
        if (!empty($storeIds)) {
            $query->whereExists(function ($q) use ($storeIds) {
                $q->from('visits')
                    ->whereColumn('visits.user_id', 'users.id')
                    ->whereIn('visits.store_id', $storeIds);
            });
        }

        // ランク（複数選択対応）
        $rankIds = $broadcast->filter_rank_card_id ? json_decode($broadcast->filter_rank_card_id, true) : null;
        if (!empty($rankIds)) {
            $query->whereIn('current_card_id', $rankIds);
        }

        // 性別（複数選択対応）
        $genders = $broadcast->filter_gender ? json_decode($broadcast->filter_gender, true) : null;
        if (!empty($genders)) {
            $query->whereIn('gender', $genders);
        }

        // 誕生月（複数選択対応）
        $birthMonths = $broadcast->filter_birth_month ? json_decode($broadcast->filter_birth_month, true) : null;
        if (!empty($birthMonths)) {
            $query->whereIn('birth_month', array_map('intval', $birthMonths));
        }

        // 来店回数
        if (!empty($broadcast->filter_min_visits)) {
            $query->where('visit_count', '>=', $broadcast->filter_min_visits);
        }
        if (!empty($broadcast->filter_max_visits)) {
            $query->where('visit_count', '<=', $broadcast->filter_max_visits);
        }

        // 最終来店からX日以上（ダッシュボードと同一基準: first_visit_at IS NOT NULL + startOfDay）
        if (!empty($broadcast->filter_days_since_visit)) {
            $cutoff = now()->subDays($broadcast->filter_days_since_visit)->startOfDay();
            $query->whereNotNull('first_visit_at')
                  ->whereNotNull('last_visit_at')
                  ->where('last_visit_at', '<', $cutoff);
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
