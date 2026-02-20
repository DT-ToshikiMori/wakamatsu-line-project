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
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->foreignId('message_bubble_id')
                ->nullable()
                ->after('coupon_template_id')
                ->constrained('message_bubbles')
                ->nullOnDelete();
            $table->unique(['user_id', 'message_bubble_id'], 'user_coupons_user_bubble_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->dropUnique('user_coupons_user_bubble_unique');
            $table->dropConstrainedForeignId('message_bubble_id');
        });
    }
};
