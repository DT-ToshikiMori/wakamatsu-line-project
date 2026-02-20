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
        Schema::table('message_bubbles', function (Blueprint $table) {
            $table->timestamp('coupon_expires_at')->nullable()->after('coupon_expires_text');
            $table->unsignedInteger('coupon_expires_days')->nullable()->after('coupon_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_bubbles', function (Blueprint $table) {
            $table->dropColumn(['coupon_expires_at', 'coupon_expires_days']);
        });
    }
};
