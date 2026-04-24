<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('type');  // scenario, campaign, birthday
            $table->string('name');
            $table->unsignedBigInteger('coupon_template_id')->nullable();
            $table->integer('coupon_expires_days')->nullable();
            $table->text('text_content')->nullable();
            $table->boolean('is_active')->default(true);

            // シナリオ用
            $table->integer('offset_days')->nullable();
            $table->tinyInteger('send_hour')->nullable();
            $table->tinyInteger('send_minute')->nullable();
            $table->unsignedBigInteger('seg_rank_id')->nullable();
            $table->integer('seg_stamp_min')->nullable();
            $table->integer('seg_stamp_max')->nullable();
            $table->integer('seg_visit_min')->nullable();

            // キャンペーン用
            $table->dateTime('send_at')->nullable();
            $table->boolean('is_full_broadcast')->nullable();
            $table->string('seg_gender')->nullable();
            $table->integer('seg_last_visit_within_days')->nullable();
            $table->integer('seg_last_visit_over_days')->nullable();

            // 誕生月用
            $table->tinyInteger('birthday_send_day')->nullable();

            $table->timestamps();

            $table->foreign('coupon_template_id')->references('id')->on('coupon_templates')->nullOnDelete();
            $table->foreign('seg_rank_id')->references('id')->on('stamp_card_definitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_campaigns');
    }
};
