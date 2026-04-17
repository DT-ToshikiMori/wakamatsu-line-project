<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ReturnRateAnalysis extends ChartWidget
{
    protected static ?string $heading = '再来率分析';

    protected int | string | array $columnSpan = 'full';

    protected ?float $churnRate = null;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        if ($this->churnRate === null) {
            $this->getCachedData();
        }
        return "再来率分析（失客率: {$this->churnRate}%）";
    }

    protected function getContentHeight(): ?int
    {
        return 300;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $base = DB::table('users')
            ->whereNotNull('line_user_id')
            ->whereNotNull('first_visit_at');

        $dist = $base->selectRaw("
            SUM(CASE WHEN visit_count = 1 THEN 1 ELSE 0 END) as v1,
            SUM(CASE WHEN visit_count = 2 THEN 1 ELSE 0 END) as v2,
            SUM(CASE WHEN visit_count = 3 THEN 1 ELSE 0 END) as v3,
            SUM(CASE WHEN visit_count = 4 THEN 1 ELSE 0 END) as v4,
            SUM(CASE WHEN visit_count = 5 THEN 1 ELSE 0 END) as v5,
            SUM(CASE WHEN visit_count >= 6 THEN 1 ELSE 0 END) as v6plus
        ")->first();

        $churnCount = DB::table('users')
            ->whereNotNull('line_user_id')
            ->whereNotNull('first_visit_at')
            ->where(function ($q) {
                $q->where('last_visit_at', '<', now()->subMonths(6))
                  ->orWhereNull('last_visit_at');
            })
            ->count();

        $totalUsers = DB::table('users')
            ->whereNotNull('line_user_id')
            ->whereNotNull('first_visit_at')
            ->count();

        $churnRate = $totalUsers > 0 ? round(($churnCount / $totalUsers) * 100, 1) : 0;

        $this->churnRate = $churnRate;

        $values = [
            (int) ($dist->v1 ?? 0),
            (int) ($dist->v2 ?? 0),
            (int) ($dist->v3 ?? 0),
            (int) ($dist->v4 ?? 0),
            (int) ($dist->v5 ?? 0),
            (int) ($dist->v6plus ?? 0),
            $churnCount,
        ];

        return [
            'datasets' => [
                [
                    'label' => '人数',
                    'data' => $values,
                    'backgroundColor' => [
                        '#d97706', // 新規(1回) — 濃いamber
                        '#f59e0b', // 2回目
                        '#fbbf24', // 3回目
                        '#fcd34d', // 4回目
                        '#fde68a', // 5回目
                        '#fef3c7', // 6回以上 — 薄いamber
                        '#ef4444', // 失客 — 赤
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => [
                '新規(1回)',
                '2回目再来',
                '3回目再来',
                '4回目',
                '5回目',
                '6回以上',
                '失客',
            ],
        ];
    }
}
