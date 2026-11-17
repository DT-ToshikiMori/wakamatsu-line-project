<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_scenario_sends', function (Blueprint $table) {
            $table->uuid('delivery_token')->nullable()->index();
        });
        // Only the two existing initial-visit scenarios. Never rewrite issued coupons.
        DB::table('message_bubbles')->where('parent_type', 'visit_scenario')
            ->whereIn('parent_id', [3, 4])->where('bubble_type', 'coupon')
            ->whereNull('coupon_expires_at')->where('coupon_expires_days', 40)
            ->update(['coupon_expires_days' => 60, 'updated_at' => now()]);
        DB::table('message_bubbles')->where('parent_type', 'visit_scenario')
            ->whereIn('parent_id', [3, 4])->where('bubble_type', 'text')
            ->where('text_content', 'like', '%有効期限40日以内%')
            ->update(['text_content' => DB::raw("REPLACE(text_content, '有効期限40日以内', '配信から60日以内')"), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Keep delivery tokens and updated policy data: deleting them breaks already-sent links.
        // Rollback requires an explicit compatibility plan, not a destructive schema rollback.
        throw new RuntimeException('Forward-only migration: preserve sent delivery links and coupon policy.');
    }
};
