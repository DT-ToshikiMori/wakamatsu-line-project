<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RichMenuClicksChart extends ChartWidget
{
    protected static ?string $heading = 'リッチメニュー クリック推移（14日間）';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $now = now();
        $dates = collect();
        for ($i = 13; $i >= 0; $i--) {
            $dates->push($now->copy()->subDays($i)->format('Y-m-d'));
        }

        // エリア別の14日間クリックデータ
        $rows = DB::table('rich_menu_clicks as rc')
            ->join('rich_menu_areas as ra', 'ra.id', '=', 'rc.rich_menu_area_id')
            ->where('rc.clicked_at', '>=', $now->copy()->subDays(14))
            ->select([
                DB::raw('DATE(rc.clicked_at) as date'),
                'ra.label',
                DB::raw('COUNT(*) as clicks'),
            ])
            ->groupBy('date', 'ra.label')
            ->orderBy('date')
            ->get();

        $labels = $rows->pluck('label')->unique()->values()->toArray();
        $colors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

        $datasets = [];
        foreach ($labels as $idx => $label) {
            $data = [];
            foreach ($dates as $date) {
                $match = $rows->first(fn ($r) => $r->date === $date && $r->label === $label);
                $data[] = $match ? $match->clicks : 0;
            }
            $datasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $colors[$idx % count($colors)],
            ];
        }

        return [
            'labels' => $dates->map(fn ($d) => \Carbon\Carbon::parse($d)->format('m/d'))->toArray(),
            'datasets' => $datasets,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
