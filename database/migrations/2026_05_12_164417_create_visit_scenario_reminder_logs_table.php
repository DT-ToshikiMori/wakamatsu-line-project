<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_scenario_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained('visit_scenario_reminders')->cascadeOnDelete();
            $table->foreignId('user_coupon_id')->constrained('user_coupons')->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['reminder_id', 'user_coupon_id']); // 二重送信防止
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_scenario_reminder_logs');
    }
};
