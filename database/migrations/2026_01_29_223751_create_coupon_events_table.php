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
        Schema::create('coupon_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_coupon_id')->constrained('user_coupons')->cascadeOnDelete();

            $table->enum('event', ['issued', 'used', 'expired', 'revoked']);
            $table->enum('actor', ['user', 'staff', 'system'])->default('system');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_coupon_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_events');
    }
};
