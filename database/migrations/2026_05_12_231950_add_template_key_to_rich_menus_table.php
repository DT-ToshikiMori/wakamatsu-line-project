<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rich_menus', function (Blueprint $table) {
            $table->string('template_key')->nullable()->after('size_type');
        });
    }

    public function down(): void
    {
        Schema::table('rich_menus', function (Blueprint $table) {
            $table->dropColumn('template_key');
        });
    }
};
