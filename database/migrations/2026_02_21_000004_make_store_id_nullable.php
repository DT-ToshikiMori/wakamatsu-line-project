<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'coupon_templates',
            'user_coupons',
            'broadcasts',
            'churn_scenarios',
            'message_schedules',
            'lottery_results',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'store_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('store_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'coupon_templates',
            'user_coupons',
            'broadcasts',
            'churn_scenarios',
            'message_schedules',
            'lottery_results',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'store_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('store_id')->nullable(false)->change();
                });
            }
        }
    }
};
