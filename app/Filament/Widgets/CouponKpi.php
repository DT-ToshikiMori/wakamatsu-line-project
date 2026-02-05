<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CouponKpi extends StatsOverviewWidget
{
    protected function getHeading(): ?string
    {
        return 'クーポン状況（今月）';
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $issued = DB::table('user_coupons')
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->count();

        $used = DB::table('user_coupons')
            ->whereNotNull('used_at')
            ->whereBetween('used_at', [$monthStart, $monthEnd])
            ->count();

        $rate = $issued > 0 ? round(($used / $issued) * 100, 1) : 0.0;

        return [
            Stat::make('発行数', $issued),
            Stat::make('使用数', $used),
            Stat::make('使用率', $rate . '%'),
        ];
    }
}