<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetProductionLaunchData extends Command
{
    protected $signature = 'production:reset-launch-data {--force : Execute the reset}';

    protected $description = 'Reset test users, QR links, stores except ID 1, and analytics data for production launch';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $deleteAllTables = [
            'sessions',
            'rich_menu_clicks',
            'visit_scenario_reminder_logs',
            'coupon_events',
            'lottery_results',
            'broadcast_logs',
            'message_sends',
            'message_schedules',
            'visit_scenario_sends',
            'user_coupons',
            'visits',
            'users',
            'store_qr_links',
        ];

        $summary = [];
        foreach ($deleteAllTables as $table) {
            if (Schema::hasTable($table)) {
                $summary[$table] = DB::table($table)->count();
            }
        }

        $summary['stores_id_not_1'] = Schema::hasTable('stores')
            ? DB::table('stores')->where('id', '!=', 1)->count()
            : 0;

        $summary['broadcasts_reset_counters'] = Schema::hasTable('broadcasts')
            ? DB::table('broadcasts')->where(function ($query) {
                $query->where('sent_count', '!=', 0)
                    ->orWhereNotNull('sent_at');
            })->count()
            : 0;

        $this->info(($force ? 'Executing reset:' : 'Dry run. Pass --force to execute.'));
        foreach ($summary as $table => $count) {
            $this->line(sprintf('%s: %d', $table, $count));
        }

        if (!$force) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($deleteAllTables): void {
            foreach ($deleteAllTables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('broadcasts')) {
                DB::table('broadcasts')->update([
                    'sent_count' => 0,
                    'sent_at' => null,
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('stores')) {
                DB::table('stores')->where('id', '!=', 1)->delete();
            }
        });

        $this->info('Production launch data reset completed.');

        return self::SUCCESS;
    }
}
