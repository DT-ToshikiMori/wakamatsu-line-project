<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('coupon_issued_at')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('message_campaigns')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_sends');
    }
};
