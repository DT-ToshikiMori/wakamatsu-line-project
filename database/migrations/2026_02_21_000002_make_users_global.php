<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ① UNIQUE制約 (store_id, line_user_id) を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'line_user_id']);
        });

        // ② 重複 line_user_id の統合
        $duplicates = DB::table('users')
            ->select('line_user_id')
            ->groupBy('line_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('line_user_id');

        foreach ($duplicates as $lineUserId) {
            $rows = DB::table('users')
                ->where('line_user_id', $lineUserId)
                ->orderByDesc('stamp_total')
                ->orderBy('id')
                ->get();

            $keep = $rows->first();
            $others = $rows->slice(1);

            // 合算値を計算
            $totalStampTotal = $rows->sum('stamp_total');
            $totalVisitCount = $rows->sum('visit_count');
            $earliestFirstVisit = $rows->whereNotNull('first_visit_at')->min('first_visit_at');
            $latestLastVisit = $rows->whereNotNull('last_visit_at')->max('last_visit_at');

            // 残す行を更新
            DB::table('users')->where('id', $keep->id)->update([
                'stamp_total' => $totalStampTotal,
                'visit_count' => $totalVisitCount,
                'first_visit_at' => $earliestFirstVisit,
                'last_visit_at' => $latestLastVisit,
                'updated_at' => now(),
            ]);

            // 関連テーブルの user_id を付け替え
            $otherIds = $others->pluck('id')->all();

            DB::table('user_coupons')
                ->whereIn('user_id', $otherIds)
                ->update(['user_id' => $keep->id]);

            DB::table('visits')
                ->whereIn('user_id', $otherIds)
                ->update(['user_id' => $keep->id]);

            if (Schema::hasTable('lottery_results')) {
                DB::table('lottery_results')
                    ->whereIn('user_id', $otherIds)
                    ->update(['user_id' => $keep->id]);
            }

            if (Schema::hasTable('broadcast_logs')) {
                DB::table('broadcast_logs')
                    ->whereIn('user_id', $otherIds)
                    ->update(['user_id' => $keep->id]);
            }

            if (Schema::hasTable('message_schedules')) {
                DB::table('message_schedules')
                    ->whereIn('user_id', $otherIds)
                    ->update(['user_id' => $keep->id]);
            }

            // 重複行を削除
            DB::table('users')->whereIn('id', $otherIds)->delete();
        }

        // ③ store_id を nullable に変更
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->change();
        });

        // ④ 新しい UNIQUE 制約 (line_user_id) を追加
        Schema::table('users', function (Blueprint $table) {
            $table->unique('line_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['line_user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['store_id', 'line_user_id']);
        });
    }
};
