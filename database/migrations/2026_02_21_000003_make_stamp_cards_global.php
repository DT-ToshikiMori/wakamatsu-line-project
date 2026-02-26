<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ① UNIQUE制約 (store_id, priority) を削除
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'priority']);
        });

        // ② 重複 priority 値の解消（最も ID が小さい定義を残す）
        $duplicates = DB::table('stamp_card_definitions')
            ->select('priority')
            ->groupBy('priority')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('priority');

        foreach ($duplicates as $priority) {
            $rows = DB::table('stamp_card_definitions')
                ->where('priority', $priority)
                ->orderBy('id')
                ->get();

            $keep = $rows->first();
            $otherIds = $rows->slice(1)->pluck('id')->all();

            // 削除される定義を参照しているレコードを残す定義に付け替え
            DB::table('users')
                ->whereIn('current_card_id', $otherIds)
                ->update(['current_card_id' => $keep->id]);

            DB::table('coupon_templates')
                ->whereIn('rank_card_id', $otherIds)
                ->update(['rank_card_id' => $keep->id]);

            DB::table('broadcasts')
                ->whereIn('filter_rank_card_id', $otherIds)
                ->update(['filter_rank_card_id' => $keep->id]);

            DB::table('stamp_card_definitions')->whereIn('id', $otherIds)->delete();
        }

        // ③ store_id を nullable に変更
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->change();
        });

        // ④ 新しい UNIQUE 制約 (priority) を追加
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->unique('priority');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropUnique(['priority']);
        });

        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable(false)->change();
        });

        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->unique(['store_id', 'priority']);
        });
    }
};
