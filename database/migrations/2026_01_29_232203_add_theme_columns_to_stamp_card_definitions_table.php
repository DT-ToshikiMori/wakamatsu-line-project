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
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->string('theme_bg', 20)->nullable()->after('priority');        // 例: #0b0b0f
            $table->string('theme_accent', 20)->nullable()->after('theme_bg');    // 例: #ffd54a
            $table->decimal('theme_logo_opacity', 3, 2)->nullable()->after('theme_accent'); // 例: 0.10
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropColumn(['theme_bg', 'theme_accent', 'theme_logo_opacity']);
        });
    }
};
