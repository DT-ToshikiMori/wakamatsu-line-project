<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->enum('filter_gender', ['male', 'female', 'other'])->nullable()->after('filter_min_visits');
            $table->unsignedTinyInteger('filter_birth_month')->nullable()->after('filter_gender');
            $table->unsignedInteger('filter_max_visits')->nullable()->after('filter_birth_month');
        });
    }

    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->dropColumn(['filter_gender', 'filter_birth_month', 'filter_max_visits']);
        });
    }
};
