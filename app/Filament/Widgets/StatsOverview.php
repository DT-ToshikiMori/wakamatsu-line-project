<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;


class StatsOverview extends StatsOverviewWidget
{

    // protected ?string $heading = 'ダッシュボード';

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $todayVisits = DB::table('visits')
            ->whereBetween('visited_at', [$todayStart, $todayEnd])
            ->count();

        $monthVisits = DB::table('visits')
            ->whereBetween('visited_at', [$monthStart, $monthEnd])
            ->count();

        $totalVisits = DB::table('visits')->count();

        $inactive120Users = DB::table('users')
            ->whereNotNull('last_visit_at')
            ->where('last_visit_at', '<=', now()->subDays(120))
            ->count();

        return [
            Stat::make('今日の来店数', $todayVisits),
            Stat::make('今月の来店数', $monthVisits),
            Stat::make('累計来店数', $totalVisits),
            Stat::make('120日未来店ユーザー', $inactive120Users),
        ];
    }
}