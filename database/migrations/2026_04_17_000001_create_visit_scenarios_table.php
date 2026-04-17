<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_scenarios', function (Blueprint $table) {
            $table->id();
            $table->integer('visit_number');
            $table->foreignId('coupon_template_id')->constrained()->cascadeOnDelete();
            $table->integer('delay_hours')->default(0);
            $table->integer('expires_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'visit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_scenarios');
    }
};
