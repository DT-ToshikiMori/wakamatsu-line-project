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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('stamp_total')->default(0)->after('line_user_id');

            $table->foreignId('current_card_id')
                ->nullable()
                ->constrained('stamp_card_definitions')
                ->nullOnDelete();

            $table->unsignedInteger('card_progress')->default(0);
            $table->timestamp('card_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_card_id');
            $table->dropColumn(['stamp_total', 'card_progress', 'card_updated_at']);
        });
    }
};
