<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // coupon_templates に mode 追加
        Schema::table('coupon_templates', function (Blueprint $table) {
            $table->enum('mode', ['normal', 'lottery'])->default('normal')->after('type');
        });

        // lottery_prizes（抽選賞品）
        Schema::create('lottery_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank');                 // 1〜5、0=ハズレ
            $table->string('title');                         // 「ドリンク1杯無料」等
            $table->string('image_url', 2000)->nullable();   // 各等の画像
            $table->unsignedInteger('probability');           // 当選確率（%）
            $table->boolean('is_miss')->default(false);      // ハズレフラグ
            $table->timestamps();

            $table->index('coupon_template_id');
        });

        // lottery_results（抽選履歴）
        Schema::create('lottery_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lottery_prize_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('trigger_type', ['checkin', 'rank_up', 'inactive', 'manual']);
            $table->boolean('is_win');
            $table->timestamp('drawn_at');
            $table->timestamps();

            $table->index(['store_id', 'user_id']);
            $table->index('coupon_template_id');
        });

        // stamp_card_definitions にクーポン紐付け追加
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->foreignId('rankup_coupon_id')
                ->nullable()
                ->after('is_active')
                ->constrained('coupon_templates')
                ->nullOnDelete();
            $table->foreignId('checkin_coupon_id')
                ->nullable()
                ->after('rankup_coupon_id')
                ->constrained('coupon_templates')
                ->nullOnDelete();
        });

        // churn_scenarios（離脱防止シナリオ）
        Schema::create('churn_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('days_after_last_stamp');
            $table->unsignedInteger('send_hour');         // 0-23
            $table->unsignedInteger('send_minute');       // 0-59
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        // broadcasts（自由配信メッセージ）
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('filter_type', ['all', 'filtered'])->default('all');
            $table->foreignId('filter_rank_card_id')
                ->nullable()
                ->constrained('stamp_card_definitions')
                ->nullOnDelete();
            $table->unsignedInteger('filter_days_since_visit')->nullable();
            $table->unsignedInteger('filter_min_visits')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sent'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        // broadcast_logs（配信履歴）
        Schema::create('broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');

            $table->index(['broadcast_id', 'user_id']);
        });

        // message_bubbles（メッセージバブル - ポリモーフィック）
        Schema::create('message_bubbles', function (Blueprint $table) {
            $table->id();
            $table->string('parent_type');   // 'churn_scenario' or 'broadcast'
            $table->unsignedBigInteger('parent_id');
            $table->unsignedInteger('position');  // 1-3
            $table->enum('bubble_type', ['text', 'coupon']);
            $table->text('text_content')->nullable();
            $table->foreignId('coupon_template_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['parent_type', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_bubbles');
        Schema::dropIfExists('broadcast_logs');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('churn_scenarios');

        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkin_coupon_id');
            $table->dropConstrainedForeignId('rankup_coupon_id');
        });

        Schema::dropIfExists('lottery_results');
        Schema::dropIfExists('lottery_prizes');

        Schema::table('coupon_templates', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
