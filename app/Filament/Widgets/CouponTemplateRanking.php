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
        // テンプレ別：発行/使用を集計
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
            ->orderByDesc('used')      // まずは「使用枚数」順
            ->orderByDesc('issued')    // 同数なら発行枚数
            ->limit(20)
            ->get();

        // 使用率はPHP側で計算（DB差異を避ける）
        $items = $rows->map(function ($r) {
            $issued = (int) $r->issued;
            $used = (int) $r->used;
            $rate = $issued > 0 ? round(($used / $issued) * 100, 1) : 0.0;

            return (object) [
                'id' => $r->id,
                'type' => $r->type,
                'title' => $r->title,
                'issued' => $issued,
                'used' => $used,
                'rate' => $rate,
            ];
        });

        return ['items' => $items];
    }
}