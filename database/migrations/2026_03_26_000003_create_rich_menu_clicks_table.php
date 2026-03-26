<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_menu_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rich_menu_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('line_user_id')->nullable();
            $table->timestamp('clicked_at');

            $table->index(['rich_menu_area_id', 'clicked_at']);
            $table->index('line_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_menu_clicks');
    }
};
