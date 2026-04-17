<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('expires_at');
            $table->index(['expires_at', 'reminder_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->dropIndex(['expires_at', 'reminder_sent_at']);
            $table->dropColumn('reminder_sent_at');
        });
    }
};
