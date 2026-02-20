<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('message_bubbles')
            ->where('parent_type', 'App\\Models\\Broadcast')
            ->update(['parent_type' => 'broadcast']);

        DB::table('message_bubbles')
            ->where('parent_type', 'App\\Models\\ChurnScenario')
            ->update(['parent_type' => 'churn_scenario']);
    }

    public function down(): void
    {
        // no-op
    }
};
