<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('profile_image_url');
            $table->unsignedSmallInteger('birth_year')->nullable()->after('gender');
            $table->unsignedTinyInteger('birth_month')->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_year', 'birth_month']);
        });
    }
};
