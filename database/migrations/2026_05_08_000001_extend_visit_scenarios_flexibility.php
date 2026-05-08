<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("visit_scenarios", function (Blueprint $table) {
            $table->string("name")->nullable()->after("id");
            $table->string("trigger_type")->default("checkin")->after("is_active"); // checkin | migration
            $table->integer("visit_count_min")->nullable()->after("from_visit_count");
            $table->integer("visit_count_max")->nullable()->after("visit_count_min");
            $table->boolean("repeat")->default(false)->after("visit_count_max");
        });
    }

    public function down(): void
    {
        Schema::table("visit_scenarios", function (Blueprint $table) {
            $table->dropColumn(["name", "trigger_type", "visit_count_min", "visit_count_max", "repeat"]);
        });
    }
};
