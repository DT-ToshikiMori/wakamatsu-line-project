<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VisitsTrend extends ChartWidget
{
    protected static ?string $heading = '来店トレンド（直近14日）';

    protected int | string | array $columnSpan = 'full';

    // protected static ?string $maxHeight = '400px';

    protected function getType(): string
    {
        return 'line';
    }

        protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
        ];
    }

    protected function getData(): array
    {
        $start = now()->subDays(13)->startOfDay();
        $end   = now()->endOfDay();

        // ① 日別：総来店（visits件数）
        $totalRows = DB::table('visits')
            ->select(DB::raw('DATE(visited_at) as d'), DB::raw('COUNT(*) as c'))
            ->whereBetween('visited_at', [$start, $end])
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->orderBy('d')
            ->get();

        $totalMap = [];
        foreach ($totalRows as $r) $totalMap[$r->d] = (int) $r->c;

        // ② 日別：新規（その日が「そのユーザー×店舗」の初来店日）
        // firsts: user_id×store_id の初来店日を作る
        $firsts = DB::table('visits')
            ->select(
                'user_id',
                'store_id',
                DB::raw('MIN(DATE(visited_at)) as first_d')
            )
            ->groupBy('user_id', 'store_id');

        // first_dごとにカウント
        $newRows = DB::query()
            ->fromSub($firsts, 'f')
            ->select('f.first_d as d', DB::raw('COUNT(*) as c'))
            ->whereBetween('f.first_d', [$start->toDateString(), $end->toDateString()])
            ->groupBy('f.first_d')
            ->orderBy('d')
            ->get();

        $newMap = [];
        foreach ($newRows as $r) $newMap[$r->d] = (int) $r->c;

        // ③ ラベルと3系列（総 / 新規 / リピート）
        $labels = [];
        $total = [];
        $new = [];
        $repeat = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels[] = $d;

            $t = $totalMap[$d] ?? 0;
            $n = $newMap[$d] ?? 0;
            $r = max(0, $t - $n);

            $total[] = $t;
            $new[] = $n;
            $repeat[] = $r;
        }

        return [
            'datasets' => [
                [
                    'label' => '総来店',
                    'data' => $total,
                    'borderColor' => '#F5C451',
                    'backgroundColor' => 'rgba(245, 196, 81, 0.15)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                ],
                [
                    'label' => '新規（初来店）',
                    'data' => $new,
                    'borderColor' => '#D1D5DB',
                    'backgroundColor' => 'rgba(209, 213, 219, 0.12)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'リピート',
                    'data' => $repeat,
                    'borderColor' => '#F5C451',
                    'backgroundColor' => 'rgba(201, 162, 39, 0.12)',
                    'borderWidth' => 2,
                    'borderDash' => [6, 4], // 点線
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}