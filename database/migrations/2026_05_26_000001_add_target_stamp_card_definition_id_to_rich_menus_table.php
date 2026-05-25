<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('rich_menus', 'target_stamp_card_definition_id')) {
            Schema::table('rich_menus', function (Blueprint $table) {
                $table->unsignedBigInteger('target_stamp_card_definition_id')
                    ->nullable()
                    ->after('template_key');
            });
        }

        // SQLiteのローカル環境では既存テーブル再作成が走るため、外部キーは本番DBでのみ付与する。
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('rich_menus', function (Blueprint $table) {
                $table->foreign('target_stamp_card_definition_id', 'rich_menus_target_card_fk')
                    ->references('id')
                    ->on('stamp_card_definitions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('rich_menus', 'target_stamp_card_definition_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('rich_menus', function (Blueprint $table) {
                $table->dropForeign('rich_menus_target_card_fk');
            });
        }

        Schema::table('rich_menus', function (Blueprint $table) {
            $table->dropColumn('target_stamp_card_definition_id');
        });
    }
};
