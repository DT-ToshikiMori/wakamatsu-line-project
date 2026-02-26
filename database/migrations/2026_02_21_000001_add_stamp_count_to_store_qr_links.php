<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_qr_links', function (Blueprint $table) {
            $table->unsignedInteger('stamp_count')->default(1)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('store_qr_links', function (Blueprint $table) {
            $table->dropColumn('stamp_count');
        });
    }
};
