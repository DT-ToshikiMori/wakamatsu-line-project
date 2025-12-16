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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('line_user_id');
            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'line_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
