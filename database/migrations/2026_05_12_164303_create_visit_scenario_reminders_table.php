<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_scenario_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_scenario_id')->constrained('visit_scenarios')->cascadeOnDelete();
            $table->unsignedInteger('before_days')->comment('期限N日前に送信');
            $table->unsignedTinyInteger('send_hour')->default(10)->comment('送信時刻(0-23)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_scenario_reminders');
    }
};
