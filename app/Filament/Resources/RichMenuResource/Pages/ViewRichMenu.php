<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Filament\Resources\RichMenuResource;
use App\Models\RichMenu;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewRichMenu extends Page
{
    protected static string $resource = RichMenuResource::class;

    protected static string $view = 'filament.resources.rich-menu-resource.pages.view-rich-menu';

    protected static ?string $title = 'リッチメニュー分析';

    public RichMenu $record;

    public function mount(int | string $record): void
    {
        $this->record = RichMenu::with('areas')->findOrFail($record);
    }

    protected function getViewData(): array
    {
        $richMenu = $this->record;
        $now = now();

        $areaStats = $richMenu->areas->map(function ($area) use ($now) {
            $totalClicks = DB::table('rich_menu_clicks')
                ->where('rich_menu_area_id', $area->id)
                ->count();

            $uniqueUsers = DB::table('rich_menu_clicks')
                ->where('rich_menu_area_id', $area->id)
                ->distinct()
                ->count('line_user_id');

            $todayClicks = DB::table('rich_menu_clicks')
                ->where('rich_menu_area_id', $area->id)
                ->whereDate('clicked_at', $now->toDateString())
                ->count();

            $weekClicks = DB::table('rich_menu_clicks')
                ->where('rich_menu_area_id', $area->id)
                ->where('clicked_at', '>=', $now->copy()->subDays(7))
                ->count();

            return (object) [
                'id' => $area->id,
                'label' => $area->label,
                'actionType' => $area->action_type,
                'totalClicks' => $totalClicks,
                'uniqueUsers' => $uniqueUsers,
                'todayClicks' => $todayClicks,
                'weekClicks' => $weekClicks,
            ];
        });

        // 14日間のクリックトレンド（日別・エリア別）
        $trendData = DB::table('rich_menu_clicks as rc')
            ->join('rich_menu_areas as ra', 'ra.id', '=', 'rc.rich_menu_area_id')
            ->where('ra.rich_menu_id', $richMenu->id)
            ->where('rc.clicked_at', '>=', $now->copy()->subDays(14))
            ->select([
                DB::raw('CAST(rc.clicked_at AS date) as click_date'),
                'ra.label',
                DB::raw('COUNT(*) as clicks'),
            ])
            ->groupBy(DB::raw('CAST(rc.clicked_at AS date)'), 'ra.label')
            ->orderBy('click_date')
            ->get();

        // 日付リスト（14日分）
        $dates = collect();
        for ($i = 13; $i >= 0; $i--) {
            $dates->push($now->copy()->subDays($i)->format('Y-m-d'));
        }

        // エリアラベル一覧
        $labels = $richMenu->areas->pluck('label')->toArray();

        // チャート用データ構築
        $chartDatasets = [];
        $colors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
        foreach ($labels as $idx => $label) {
            $data = [];
            foreach ($dates as $date) {
                $match = $trendData->first(fn ($r) => $r->click_date === $date && $r->label === $label);
                $data[] = $match ? $match->clicks : 0;
            }
            $chartDatasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $colors[$idx % count($colors)],
            ];
        }

        return [
            'richMenu' => $richMenu,
            'areaStats' => $areaStats,
            'chartDates' => $dates->map(fn ($d) => \Carbon\Carbon::parse($d)->format('m/d'))->toArray(),
            'chartDatasets' => $chartDatasets,
        ];
    }
}
