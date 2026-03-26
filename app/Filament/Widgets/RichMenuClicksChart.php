<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $defaultLabels = $dates->map(fn ($d) => Carbon::parse($d)->format('m/d'))->toArray();

        if (!Schema::hasTable('rich_menu_clicks')) {
            return [
                'labels' => $defaultLabels,
                'datasets' => [],
            ];
        }

        $rows = DB::table('rich_menu_clicks as rc')
            ->join('rich_menu_areas as ra', 'ra.id', '=', 'rc.rich_menu_area_id')
            ->where('rc.clicked_at', '>=', $now->copy()->subDays(14))
            ->select([
                DB::raw('CAST(rc.clicked_at AS date) as click_date'),
                'ra.label',
                DB::raw('COUNT(*) as clicks'),
            ])
            ->groupBy(DB::raw('CAST(rc.clicked_at AS date)'), 'ra.label')
            ->orderBy('date')
            ->get();

        $labels = $rows->pluck('label')->unique()->values()->toArray();
        $colors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

        $datasets = [];
        foreach ($labels as $idx => $label) {
            $data = [];
            foreach ($dates as $date) {
                $match = $rows->first(fn ($r) => $r->click_date === $date && $r->label === $label);
                $data[] = $match ? $match->clicks : 0;
            }
            $datasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $colors[$idx % count($colors)],
            ];
        }

        return [
            'labels' => $defaultLabels,
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
