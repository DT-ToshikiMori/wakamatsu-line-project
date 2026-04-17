<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // visit_scenario_sends を一旦削除（visit_scenarios に依存するため先に）
        Schema::dropIfExists('visit_scenario_sends');
        Schema::dropIfExists('visit_scenarios');

        // visit_scenarios を新スキーマで再作成
        Schema::create('visit_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_card_definition_id')->constrained('stamp_card_definitions')->cascadeOnDelete();
            $table->integer('stamp_number')->nullable();       // カードの何スタンプ目（null = from_visit_count 方式）
            $table->integer('from_visit_count')->nullable();   // N回目以降ずっと
            $table->string('segment_filter')->nullable();      // null=全員 / new / 2_3 / 4plus
            $table->foreignId('coupon_template_id')->constrained()->cascadeOnDelete();
            $table->integer('delay_hours')->default(0);
            $table->integer('expires_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'stamp_card_definition_id']);
        });

        // visit_scenario_sends を再作成
        Schema::create('visit_scenario_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained('visit_scenarios')->cascadeOnDelete();
            $table->datetime('scheduled_at');
            $table->datetime('sent_at')->nullable();
            $table->datetime('coupon_issued_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_scenario_sends');
        Schema::dropIfExists('visit_scenarios');
    }
};
