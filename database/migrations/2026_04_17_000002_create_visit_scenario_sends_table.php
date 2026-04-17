<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_scenario_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained('visit_scenarios')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('coupon_issued_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_scenario_sends');
    }
};
