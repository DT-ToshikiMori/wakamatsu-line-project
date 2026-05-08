<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_scenarios', function (Blueprint $table) {
            $table->integer('trigger_days')->nullable()->after('trigger_type');
            $table->integer('send_hour')->nullable()->after('trigger_days');
            $table->boolean('reminder_enabled')->default(false)->after('repeat');
            $table->integer('reminder_before_days')->nullable()->after('reminder_enabled');
            $table->integer('reminder_hour')->default(10)->after('reminder_before_days');
        });

        Schema::table('visit_scenario_sends', function (Blueprint $table) {
            $table->unsignedBigInteger('user_coupon_id')->nullable()->after('coupon_issued_at');
            $table->datetime('reminder_scheduled_at')->nullable()->after('user_coupon_id');
            $table->datetime('reminder_sent_at')->nullable()->after('reminder_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('visit_scenarios', function (Blueprint $table) {
            $table->dropColumn([
                'trigger_days',
                'send_hour',
                'reminder_enabled',
                'reminder_before_days',
                'reminder_hour',
            ]);
        });

        Schema::table('visit_scenario_sends', function (Blueprint $table) {
            $table->dropColumn([
                'user_coupon_id',
                'reminder_scheduled_at',
                'reminder_sent_at',
            ]);
        });
    }
};
