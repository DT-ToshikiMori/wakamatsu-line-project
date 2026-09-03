<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rich_menu_areas', function (Blueprint $table) {
            $table->boolean('open_external_browser')->default(false)->after('action_data');
        });
    }

    public function down(): void
    {
        Schema::table('rich_menu_areas', function (Blueprint $table) {
            $table->dropColumn('open_external_browser');
        });
    }
};
