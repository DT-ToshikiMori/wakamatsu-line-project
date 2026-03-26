<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RichMenuClicksKpi extends StatsOverviewWidget
{
    protected function getHeading(): ?string
    {
        return 'リッチメニュー（クリック状況）';
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // 今日のクリック数
        $todayClicks = DB::table('rich_menu_clicks')
            ->whereDate('clicked_at', $today)
            ->count();

        // 今日のユニークユーザー数
        $todayUnique = DB::table('rich_menu_clicks')
            ->whereDate('clicked_at', $today)
            ->distinct('line_user_id')
            ->count('line_user_id');

        // 今月のクリック数
        $monthClicks = DB::table('rich_menu_clicks')
            ->whereBetween('clicked_at', [$monthStart, $monthEnd])
            ->count();

        // 今月のユニーク数
        $monthUnique = DB::table('rich_menu_clicks')
            ->whereBetween('clicked_at', [$monthStart, $monthEnd])
            ->distinct('line_user_id')
            ->count('line_user_id');

        // 人気エリア（今月）
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
