<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->unsignedInteger('rankup_coupon_expires_days')->nullable()->after('rankup_coupon_id');
            $table->unsignedInteger('checkin_coupon_expires_days')->nullable()->after('checkin_coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropColumn(['rankup_coupon_expires_days', 'checkin_coupon_expires_days']);
        });
    }
};
