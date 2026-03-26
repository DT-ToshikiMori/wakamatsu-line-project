<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CouponTemplateRanking extends Widget
{
    protected static ?string $heading = 'クーポン成果ランキング（テンプレ別）';
    protected static string $view = 'filament.widgets.coupon-template-ranking';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        // テンプレ別：配信/発行（取得）/使用を集計
        $rows = DB::table('coupon_templates as ct')
            ->leftJoin('user_coupons as uc', 'uc.coupon_template_id', '=', 'ct.id')
            ->select([
                'ct.id',
                'ct.type',
                'ct.title',
                DB::raw('COUNT(uc.id) as issued'),
                DB::raw("SUM(CASE WHEN uc.used_at IS NOT NULL OR uc.status = 'used' THEN 1 ELSE 0 END) as used"),
            ])
            ->groupBy('ct.id', 'ct.type', 'ct.title')
            ->orderByDesc('used')
            ->orderByDesc('issued')
            ->limit(20)
            ->get();

        // テンプレ別の配信数（broadcast_logs経由）を取得
        $templateIds = $rows->pluck('id')->toArray();

        $broadcastCounts = [];
        if (!empty($templateIds)) {
            $broadcastCounts = DB::table('message_bubbles as mb')
                ->join('broadcasts as b', function ($join) {
                    $join->on('mb.parent_id', '=', 'b.id')
                        ->where('mb.parent_type', '=', 'broadcast');
                })
                ->join('broadcast_logs as bl', 'bl.broadcast_id', '=', 'b.id')
                ->where('mb.bubble_type', 'coupon')
                ->whereIn('mb.coupon_template_id', $templateIds)
                ->groupBy('mb.coupon_template_id')
                ->select('mb.coupon_template_id', DB::raw('COUNT(DISTINCT bl.id) as cnt'))
                ->get()
                ->pluck('cnt', 'coupon_template_id')
                ->toArray();
        }

        $items = $rows->map(function ($r) use ($broadcastCounts) {
            $issued = (int) $r->issued;
            $used = (int) $r->used;
            $broadcast = (int) ($broadcastCounts[$r->id] ?? 0);
            $usageRate = $issued > 0 ? round(($used / $issued) * 100, 1) : 0.0;
            $acquisitionRate = $broadcast > 0 ? round(($issued / $broadcast) * 100, 1) : 0.0;

            return (object) [
                'id' => $r->id,
                'type' => $r->type,
                'title' => $r->title,
                'broadcast' => $broadcast,
                'issued' => $issued,
                'used' => $used,
                'acquisitionRate' => $acquisitionRate,
                'usageRate' => $usageRate,
            ];
        });

        return ['items' => $items];
    }
}
