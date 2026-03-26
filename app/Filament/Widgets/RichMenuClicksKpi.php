<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RichMenuClicksKpi extends StatsOverviewWidget
{
    protected function getHeading(): ?string
    {
        return 'リッチメニュー（クリック状況）';
    }

    protected function getStats(): array
    {
        if (!Schema::hasTable('rich_menu_clicks')) {
            return [
                Stat::make('今日のクリック', '-'),
                Stat::make('今月のクリック', '-'),
                Stat::make('人気エリア', '-'),
            ];
        }

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $todayClicks = DB::table('rich_menu_clicks')
            ->whereDate('clicked_at', $today)
            ->count();

        $todayUnique = DB::table('rich_menu_clicks')
            ->whereDate('clicked_at', $today)
            ->distinct()
            ->count('line_user_id');

        $monthClicks = DB::table('rich_menu_clicks')
            ->whereBetween('clicked_at', [$monthStart, $monthEnd])
            ->count();

        $monthUnique = DB::table('rich_menu_clicks')
            ->whereBetween('clicked_at', [$monthStart, $monthEnd])
            ->distinct()
            ->count('line_user_id');

        $popularArea = DB::table('rich_menu_clicks as rc')
            ->join('rich_menu_areas as ra', 'ra.id', '=', 'rc.rich_menu_area_id')
            ->whereBetween('rc.clicked_at', [$monthStart, $monthEnd])
            ->select('ra.label', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ra.label')
            ->orderByDesc('cnt')
            ->first();

        return [
            Stat::make('今日のクリック', $todayClicks)
                ->description("ユニーク: {$todayUnique}"),
            Stat::make('今月のクリック', $monthClicks)
                ->description("ユニーク: {$monthUnique}"),
            Stat::make('人気エリア', $popularArea?->label ?? '-')
                ->description($popularArea ? "{$popularArea->cnt}クリック" : ''),
        ];
    }
}
