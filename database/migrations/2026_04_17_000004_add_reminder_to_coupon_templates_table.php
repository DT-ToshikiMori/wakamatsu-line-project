<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_templates', function (Blueprint $table) {
            $table->integer('reminder_hours_before_expiry')->nullable()->after('rank_card_id');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_templates', function (Blueprint $table) {
            $table->dropColumn('reminder_hours_before_expiry');
        });
    }
};
