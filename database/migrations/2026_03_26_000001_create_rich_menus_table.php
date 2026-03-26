<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('line_rich_menu_id')->nullable();
            $table->string('chat_bar_text', 50);
            $table->enum('size_type', ['full', 'half'])->default('full');
            $table->boolean('selected')->default(false);
            $table->string('image_path')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['draft', 'synced', 'active'])->default('draft');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_menus');
    }
};
