<?php

namespace App\Filament\Pages;

use App\Models\Store;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = '店舗別ダッシュボード';
    protected static ?string $title = '店舗別ダッシュボード';
    protected static ?string $navigationGroup = '分析';
    protected static ?int $navigationSort = 9;
    protected static string $view = 'filament.pages.store-dashboard';

    public ?string $storeId = null;
    public string $startDate = '';
    public string $endDate = '';

    public array $summary = [];
    public array $storeRows = [];
    public array $genderRows = [];
    public array $ageRows = [];
    public array $frequencyRows = [];
    public array $postalRows = [];

    public function mount(): void
    {
        $this->startDate = now()->subDays(29)->toDateString();
        $this->endDate = now()->toDateString();
        $this->computeDashboard();
    }

    public function updatedStoreId(): void
    {
        $this->computeDashboard();
    }

    public function updatedStartDate(): void
    {
        $this->computeDashboard();
    }

    public function updatedEndDate(): void
    {
        $this->computeDashboard();
    }

    public function getStoreOptions(): array
    {
        return Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function computeDashboard(): void
    {
        [$start, $end] = $this->period();

        $this->summary = $this->buildSummary($start, $end);
        $this->storeRows = $this->buildStoreRows($start, $end);

        $visitorUserIds = $this->visitorUserIds($start, $end);

        $this->genderRows = $this->buildGenderRows($visitorUserIds);
        $this->ageRows = $this->buildAgeRows($visitorUserIds);
        $this->frequencyRows = $this->buildFrequencyRows($visitorUserIds);
        $this->postalRows = $this->buildPostalRows($visitorUserIds);
    }

    protected function period(): array
    {
        $start = Carbon::parse($this->startDate ?: now()->subDays(29)->toDateString())->startOfDay();
        $end = Carbon::parse($this->endDate ?: now()->toDateString())->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function visitsQuery(Carbon $start, Carbon $end)
    {
        return DB::table('visits as v')
            ->whereBetween('v.visited_at', [$start, $end])
            ->when($this->storeId, fn ($query) => $query->where('v.store_id', $this->storeId));
    }

    protected function visitorUserIds(Carbon $start, Carbon $end): Collection
    {
        return $this->visitsQuery($start, $end)
            ->distinct()
            ->pluck('v.user_id');
    }

    protected function buildSummary(Carbon $start, Carbon $end): array
    {
        $visitsQuery = $this->visitsQuery($start, $end);

        $visitCount = (clone $visitsQuery)->count();
        $visitorCount = (clone $visitsQuery)->distinct('v.user_id')->count('v.user_id');
        $repeatVisitorCount = $this->repeatVisitorCount($start, $end);
        $newVisitorCount = $this->newVisitorCount($start, $end);

        return [
            'visit_count' => $visitCount,
            'visitor_count' => $visitorCount,
            'repeat_visitor_count' => $repeatVisitorCount,
            'new_visitor_count' => $newVisitorCount,
            'avg_visits_per_user' => $visitorCount > 0 ? round($visitCount / $visitorCount, 2) : 0,
            'repeat_rate' => $visitorCount > 0 ? round(($repeatVisitorCount / $visitorCount) * 100, 1) : 0,
        ];
    }

    protected function repeatVisitorCount(Carbon $start, Carbon $end): int
    {
        return DB::table('visits as v')
            ->whereBetween('v.visited_at', [$start, $end])
            ->when($this->storeId, fn ($query) => $query->where('v.store_id', $this->storeId))
            ->groupBy('v.user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->select('v.user_id')
            ->get()
            ->count();
    }

    protected function newVisitorCount(Carbon $start, Carbon $end): int
    {
        $firstVisits = DB::table('visits as v')
            ->selectRaw('v.user_id, MIN(v.visited_at) as first_visited_at')
            ->when($this->storeId, fn ($query) => $query->where('v.store_id', $this->storeId))
            ->groupBy('v.user_id');

        return DB::query()
            ->fromSub($firstVisits, 'fv')
            ->whereBetween('fv.first_visited_at', [$start, $end])
            ->count();
    }

    protected function buildStoreRows(Carbon $start, Carbon $end): array
    {
        $storeRows = DB::table('stores as s')
            ->leftJoin('visits as v', function ($join) use ($start, $end) {
                $join->on('v.store_id', '=', 's.id')
                    ->whereBetween('v.visited_at', [$start, $end]);
            })
            ->when($this->storeId, fn ($query) => $query->where('s.id', $this->storeId))
            ->where('s.is_active', true)
            ->groupBy('s.id', 's.name')
            ->selectRaw('s.id, s.name, COUNT(v.id) as visit_count, COUNT(DISTINCT v.user_id) as visitor_count')
            ->orderByDesc('visit_count')
            ->orderBy('s.name')
            ->get()
            ->keyBy('id');

        if ($storeRows->isEmpty()) {
            return [];
        }

        $repeatRows = DB::table('visits as v')
            ->whereBetween('v.visited_at', [$start, $end])
            ->when($this->storeId, fn ($query) => $query->where('v.store_id', $this->storeId))
            ->groupBy('v.store_id', 'v.user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->selectRaw('v.store_id, v.user_id')
            ->get()
            ->groupBy('store_id')
            ->map(fn ($rows) => $rows->count());

        $firstVisits = DB::table('visits as v')
            ->selectRaw('v.store_id, v.user_id, MIN(v.visited_at) as first_visited_at')
            ->when($this->storeId, fn ($query) => $query->where('v.store_id', $this->storeId))
            ->groupBy('v.store_id', 'v.user_id');

        $newRows = DB::query()
            ->fromSub($firstVisits, 'fv')
            ->whereBetween('fv.first_visited_at', [$start, $end])
            ->selectRaw('fv.store_id, COUNT(*) as new_visitor_count')
            ->groupBy('fv.store_id')
            ->pluck('new_visitor_count', 'store_id');

        return $storeRows
            ->map(fn ($row) => [
                'store_id' => (int) $row->id,
                'store_name' => $row->name,
                'visit_count' => (int) $row->visit_count,
                'visitor_count' => (int) $row->visitor_count,
                'new_visitor_count' => (int) ($newRows[$row->id] ?? 0),
                'repeat_visitor_count' => (int) ($repeatRows[$row->id] ?? 0),
            ])
            ->values()
            ->toArray();
    }

    protected function buildGenderRows(Collection $userIds): array
    {
        if ($userIds->isEmpty()) {
            return $this->emptyBreakdown([
                '男性',
                '女性',
                'その他',
                '未登録',
            ]);
        }

        $rows = $this->usersFor($userIds)
            ->selectRaw("COALESCE(gender, 'unknown') as label, COUNT(*) as count")
            ->groupBy('label')
            ->pluck('count', 'label');

        return $this->formatBreakdown([
            'male' => '男性',
            'female' => '女性',
            'other' => 'その他',
            'unknown' => '未登録',
        ], $rows);
    }

    protected function buildAgeRows(Collection $userIds): array
    {
        if ($userIds->isEmpty()) {
            return $this->emptyBreakdown([
                '20歳未満',
                '20代',
                '30代',
                '40代',
                '50代',
                '60代以上',
                '未登録',
            ]);
        }

        $rows = $this->usersFor($userIds)
            ->selectRaw("
                CASE
                    WHEN birth_year IS NULL THEN 'unknown'
                    WHEN (? - birth_year) < 20 THEN 'under_20'
                    WHEN (? - birth_year) BETWEEN 20 AND 29 THEN '20s'
                    WHEN (? - birth_year) BETWEEN 30 AND 39 THEN '30s'
                    WHEN (? - birth_year) BETWEEN 40 AND 49 THEN '40s'
                    WHEN (? - birth_year) BETWEEN 50 AND 59 THEN '50s'
                    WHEN (? - birth_year) >= 60 THEN '60plus'
                    ELSE 'unknown'
                END as label,
                COUNT(*) as count
            ", array_fill(0, 6, now()->year))
            ->groupBy('label')
            ->pluck('count', 'label');

        return $this->formatBreakdown([
            'under_20' => '20歳未満',
            '20s' => '20代',
            '30s' => '30代',
            '40s' => '40代',
            '50s' => '50代',
            '60plus' => '60代以上',
            'unknown' => '未登録',
        ], $rows);
    }

    protected function buildFrequencyRows(Collection $userIds): array
    {
        if ($userIds->isEmpty()) {
            return $this->emptyBreakdown([
                '初めて',
                '2〜3回目',
                '4回以上',
                '未登録',
            ]);
        }

        $rows = $this->usersFor($userIds)
            ->selectRaw("COALESCE(visit_frequency, 'unknown') as label, COUNT(*) as count")
            ->groupBy('label')
            ->pluck('count', 'label');

        return $this->formatBreakdown([
            'new' => '初めて',
            '2_3' => '2〜3回目',
            '4plus' => '4回以上',
            'unknown' => '未登録',
        ], $rows);
    }

    protected function buildPostalRows(Collection $userIds): array
    {
        if ($userIds->isEmpty()) {
            return [];
        }

        $total = $userIds->count();

        return $this->usersFor($userIds)
            ->whereNotNull('postal_code')
            ->where('postal_code', '<>', '')
            ->selectRaw('postal_code as label, COUNT(*) as count')
            ->groupBy('postal_code')
            ->orderByDesc('count')
            ->orderBy('postal_code')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'count' => (int) $row->count,
                'percent' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0,
            ])
            ->toArray();
    }

    protected function usersFor(Collection $userIds)
    {
        return DB::table('users')
            ->whereIn('id', $userIds->values()->all());
    }

    protected function formatBreakdown(array $labels, Collection $rows): array
    {
        $total = $rows->sum(fn ($count) => (int) $count);

        return collect($labels)
            ->map(fn ($label, $key) => [
                'label' => $label,
                'count' => (int) ($rows[$key] ?? 0),
                'percent' => $total > 0 ? round(((int) ($rows[$key] ?? 0) / $total) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();
    }

    protected function emptyBreakdown(array $labels): array
    {
        return collect($labels)
            ->map(fn ($label) => [
                'label' => $label,
                'count' => 0,
                'percent' => 0,
            ])
            ->toArray();
    }
}
