<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class KpiOverview extends StatsOverviewWidget
{
    protected function getHeading(): ?string
    {
        return '来店サマリー';
    }

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();
        $activeSince = now()->subDays(30);

        $todayVisits = DB::table('visits')
            ->whereBetween('visited_at', [$todayStart, $todayEnd])
            ->count();

        $monthVisits = DB::table('visits')
            ->whereBetween('visited_at', [$monthStart, $monthEnd])
            ->count();

        $activeMembers = DB::table('users')
            ->whereNotNull('last_visit_at')
            ->where('last_visit_at', '>=', $activeSince)
            ->count();

        // GOLD会員数（name='GOLD' のカードにいるユーザー）
        $goldMembers = DB::table('users as u')
            ->join('stamp_card_definitions as scd', 'scd.id', '=', 'u.current_card_id')
            ->where('scd.name', 'GOLD')
            ->count();

        return [
            Stat::make('今日の来店数', $todayVisits),
            Stat::make('今月の来店数', $monthVisits),
            Stat::make('アクティブ会員(30日)', $activeMembers),
            Stat::make('GOLD会員', $goldMembers),
        ];
    }
}