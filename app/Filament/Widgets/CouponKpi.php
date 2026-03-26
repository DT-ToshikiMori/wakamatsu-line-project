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

        // 配信数：今月のbroadcast_logsのうちクーポンバブルが含まれる配信のログ数
        $broadcastCount = DB::table('broadcast_logs as bl')
            ->whereBetween('bl.sent_at', [$monthStart, $monthEnd])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('message_bubbles as mb')
                    ->whereColumn('mb.parent_id', 'bl.broadcast_id')
                    ->where('mb.parent_type', 'broadcast')
                    ->where('mb.bubble_type', 'coupon');
            })
            ->count();

        // 取得数（発行数）
        $issued = DB::table('user_coupons')
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->count();

        // 取得率
        $acquisitionRate = $broadcastCount > 0
            ? round(($issued / $broadcastCount) * 100, 1)
            : 0.0;

        // 使用数
        $used = DB::table('user_coupons')
            ->whereNotNull('used_at')
            ->whereBetween('used_at', [$monthStart, $monthEnd])
            ->count();

        // 使用率
        $usageRate = $issued > 0 ? round(($used / $issued) * 100, 1) : 0.0;

        return [
            Stat::make('配信数', $broadcastCount)
                ->description('クーポン配信ユーザー数'),
            Stat::make('取得数', $issued),
            Stat::make('取得率', $acquisitionRate . '%')
                ->description('配信→取得'),
            Stat::make('使用数', $used),
            Stat::make('使用率', $usageRate . '%')
                ->description('取得→使用'),
        ];
    }
}
