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
        Schema::create('coupon_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['birthday', 'inactive', 'rank_up', 'stamp']);

            $table->string('title');
            $table->text('note')->nullable();
            $table->string('image_url')->nullable(); // 300x900

            // 誕生日
            $table->integer('birthday_offset_days')->nullable();

            // 離脱防止
            $table->unsignedInteger('inactive_days')->nullable();
            $table->unsignedInteger('inactive_hour')->nullable();
            $table->unsignedInteger('inactive_minute')->nullable();

            // スタンプ到達
            $table->unsignedInteger('required_stamps')->nullable();

            // ランクアップ
            $table->foreignId('rank_card_id')
                ->nullable()
                ->constrained('stamp_card_definitions')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_templates');
    }
};
