<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class AttributeAnalysis extends Widget
{
    protected static ?string $heading = '属性分析';
    protected static string $view = 'filament.widgets.attribute-analysis';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $baseQuery = DB::table('users')->whereNotNull('line_user_id');

        // 性別分布
        $genderRows = (clone $baseQuery)
            ->selectRaw("
                SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female,
                SUM(CASE WHEN gender = 'other' THEN 1 ELSE 0 END) as other_g,
                SUM(CASE WHEN gender IS NULL THEN 1 ELSE 0 END) as unknown
            ")
            ->first();

        $genderData = [
            'labels' => ['男性', '女性', 'その他', '未登録'],
            'values' => [
                (int) ($genderRows->male ?? 0),
                (int) ($genderRows->female ?? 0),
                (int) ($genderRows->other_g ?? 0),
                (int) ($genderRows->unknown ?? 0),
            ],
            'colors' => ['#3b82f6', '#ec4899', '#8b5cf6', '#6b7280'],
        ];

        // ランク分布
        $rankRows = DB::table('users as u')
            ->whereNotNull('u.line_user_id')
            ->leftJoin('stamp_card_definitions as scd', 'scd.id', '=', 'u.current_card_id')
            ->selectRaw("COALESCE(scd.display_name, '未設定') as rank_name, COUNT(*) as cnt")
            ->groupBy(DB::raw("COALESCE(scd.display_name, '未設定')"))
            ->orderByDesc('cnt')
            ->get();

        $rankColors = ['#f59e0b', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7', '#d97706', '#92400e'];
        $rankData = [
            'labels' => $rankRows->pluck('rank_name')->toArray(),
            'values' => $rankRows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
            'colors' => array_slice(array_merge($rankColors, $rankColors), 0, $rankRows->count()),
        ];

        // 誕生月分布
        $birthMonthValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $birthMonthValues[$m] = 0;
        }
        $birthRows = (clone $baseQuery)
            ->whereNotNull('birth_month')
            ->selectRaw('birth_month, COUNT(*) as cnt')
            ->groupBy('birth_month')
            ->get();
        foreach ($birthRows as $r) {
            $birthMonthValues[(int) $r->birth_month] = (int) $r->cnt;
        }

        $birthMonthData = [
            'labels' => array_map(fn ($m) => $m . '月', range(1, 12)),
            'values' => array_values($birthMonthValues),
        ];

        // 来店回数分布
        $visitDist = (clone $baseQuery)
            ->whereNotNull('first_visit_at')
            ->selectRaw("
                SUM(CASE WHEN visit_count = 1 THEN 1 ELSE 0 END) as v1,
                SUM(CASE WHEN visit_count = 2 THEN 1 ELSE 0 END) as v2,
                SUM(CASE WHEN visit_count = 3 THEN 1 ELSE 0 END) as v3,
                SUM(CASE WHEN visit_count = 4 THEN 1 ELSE 0 END) as v4,
                SUM(CASE WHEN visit_count = 5 THEN 1 ELSE 0 END) as v5,
                SUM(CASE WHEN visit_count >= 6 THEN 1 ELSE 0 END) as v6plus
            ")
            ->first();

        $visitData = [
            'labels' => ['1回', '2回', '3回', '4回', '5回', '6回以上'],
            'values' => [
                (int) ($visitDist->v1 ?? 0),
                (int) ($visitDist->v2 ?? 0),
                (int) ($visitDist->v3 ?? 0),
                (int) ($visitDist->v4 ?? 0),
                (int) ($visitDist->v5 ?? 0),
                (int) ($visitDist->v6plus ?? 0),
            ],
        ];

        // 最終来店経過日数分布
        $now = now();
        $lastVisitDist = (clone $baseQuery)
            ->whereNotNull('first_visit_at')
            ->selectRaw("
                SUM(CASE WHEN last_visit_at >= ? THEN 1 ELSE 0 END) as d_today,
                SUM(CASE WHEN last_visit_at >= ? AND last_visit_at < ? THEN 1 ELSE 0 END) as d_1_7,
                SUM(CASE WHEN last_visit_at >= ? AND last_visit_at < ? THEN 1 ELSE 0 END) as d_8_30,
                SUM(CASE WHEN last_visit_at >= ? AND last_visit_at < ? THEN 1 ELSE 0 END) as d_31_90,
                SUM(CASE WHEN last_visit_at >= ? AND last_visit_at < ? THEN 1 ELSE 0 END) as d_91_180,
                SUM(CASE WHEN last_visit_at < ? OR last_visit_at IS NULL THEN 1 ELSE 0 END) as d_180plus
            ", [
                $now->copy()->startOfDay(),
                $now->copy()->subDays(7)->startOfDay(), $now->copy()->startOfDay(),
                $now->copy()->subDays(30)->startOfDay(), $now->copy()->subDays(7)->startOfDay(),
                $now->copy()->subDays(90)->startOfDay(), $now->copy()->subDays(30)->startOfDay(),
                $now->copy()->subDays(180)->startOfDay(), $now->copy()->subDays(90)->startOfDay(),
                $now->copy()->subDays(180)->startOfDay(),
            ])
            ->first();

        $lastVisitData = [
            'labels' => ['今日', '1-7日', '8-30日', '31-90日', '91-180日', '180日+'],
            'values' => [
                (int) ($lastVisitDist->d_today ?? 0),
                (int) ($lastVisitDist->d_1_7 ?? 0),
                (int) ($lastVisitDist->d_8_30 ?? 0),
                (int) ($lastVisitDist->d_31_90 ?? 0),
                (int) ($lastVisitDist->d_91_180 ?? 0),
                (int) ($lastVisitDist->d_180plus ?? 0),
            ],
            'colors' => ['#22c55e', '#86efac', '#fbbf24', '#f59e0b', '#f97316', '#ef4444'],
        ];

        return [
            'genderData' => $genderData,
            'rankData' => $rankData,
            'birthMonthData' => $birthMonthData,
            'visitData' => $visitData,
            'lastVisitData' => $lastVisitData,
        ];
    }
}
