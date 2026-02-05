<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class StoreRanking extends Widget
{
    
    protected static string $view = 'filament.widgets.store-ranking';

    protected function getHeading(): ?string
    {
        return '店舗サマリー';
    }

    protected function getViewData(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $rows = DB::table('visits as v')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereBetween('v.visited_at', [$monthStart, $monthEnd])
            ->groupBy('s.id', 's.name')
            ->select('s.name as store_name', DB::raw('COUNT(*) as visits'))
            ->orderByDesc('visits')
            ->limit(5)
            ->get();

        return ['rows' => $rows];
    }
}