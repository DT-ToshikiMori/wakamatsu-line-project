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
        Schema::table('message_bubbles', function (Blueprint $table) {
            $table->string('coupon_expires_text')->nullable()->after('coupon_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_bubbles', function (Blueprint $table) {
            $table->dropColumn('coupon_expires_text');
        });
    }
};
