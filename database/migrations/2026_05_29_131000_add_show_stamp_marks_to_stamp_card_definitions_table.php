<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->boolean('show_stamp_marks')
                ->default(true)
                ->after('theme_logo_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_card_definitions', function (Blueprint $table) {
            $table->dropColumn('show_stamp_marks');
        });
    }
};
