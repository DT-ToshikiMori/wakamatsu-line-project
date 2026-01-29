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
        Schema::create('stamp_card_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // BEGINNER / GOLD / BLACK
            $table->string('display_name');
            $table->unsignedInteger('required_stamps'); // 3 / 5 / 10
            $table->unsignedInteger('priority'); // 1,2,3...

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_card_definitions');
    }
};
