<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Store;

class RepeatRateReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'リピート率分析表';
    protected static ?string $title = 'リピート率分析表';
    protected static ?string $navigationGroup = '分析';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.repeat-rate-report';

    public ?string $storeId = null;
    public ?string $gender = null;
    public string $baseMonth = '';

    public array $reportData = [];
    public array $monthLabels = [];

    public function mount(): void
    {
        $this->baseMonth = now()->subMonth()->format('Y-m');
        $this->computeReport();
    }

    public function updatedStoreId(): void
    {
        $this->computeReport();
    }

    public function updatedGender(): void
    {
        $this->computeReport();
    }

    public function updatedBaseMonth(): void
    {
        $this->computeReport();
    }

    public function getStoreOptions(): array
    {
        return Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function computeReport(): void
    {
        if (empty($this->baseMonth)) {
            $this->reportData = $this->buildEmptyReport();
            return;
        }

        $baseStart = Carbon::createFromFormat('Y-m', $this->baseMonth)->startOfMonth();
        $baseEnd = (clone $baseStart)->endOfMonth();
        $now = now();

        // Generate month labels
        $this->monthLabels = [];
        for ($i = 1; $i <= 6; $i++) {
            $m = (clone $baseStart)->addMonths($i);
            $this->monthLabels[$i] = $m->format('Y年n月');
        }

        // Step 1: Get base-month visitor user_ids (with store/gender filters)
        $baseVisitorsQuery = DB::table('visits as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->whereBetween('v.visited_at', [$baseStart, $baseEnd]);

        if ($this->storeId) {
            $baseVisitorsQuery->where('v.store_id', $this->storeId);
        }
        if ($this->gender) {
            $baseVisitorsQuery->where('u.gender', $this->gender);
        }

        $baseUserIds = (clone $baseVisitorsQuery)
            ->distinct()
            ->pluck('v.user_id')
            ->toArray();

        if (empty($baseUserIds)) {
            $this->reportData = $this->buildEmptyReport();
            return;
        }

        // Step 2: Count visits per user in base month (for same-month return)
        $baseMonthVisitCounts = DB::table('visits')
            ->whereIn('user_id', $baseUserIds)
            ->whereBetween('visited_at', [$baseStart, $baseEnd])
            ->when($this->storeId, fn ($q) => $q->where('store_id', $this->storeId))
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->pluck('cnt', 'user_id');

        // Step 3: Count cumulative visits per user up to end of base month (for classification)
        $cumulativeVisits = DB::table('visits')
            ->whereIn('user_id', $baseUserIds)
            ->where('visited_at', '<=', $baseEnd)
            ->when($this->storeId, fn ($q) => $q->where('store_id', $this->storeId))
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'user_id');

        // Step 4: Classify users into categories
        $categories = [
            '合計'   => [],
            '新規'   => [],
            '再来'   => [],
            '準固定' => [],
            '固定'   => [],
        ];

        foreach ($baseUserIds as $uid) {
            $total = $cumulativeVisits[$uid] ?? 1;
            $categories['合計'][] = $uid;

            if ($total == 1) {
                $categories['新規'][] = $uid;
            } elseif ($total <= 4) {
                $categories['再来'][] = $uid;
            } elseif ($total <= 9) {
                $categories['準固定'][] = $uid;
            } else {
                $categories['固定'][] = $uid;
            }
        }

        // Step 5: Get all visits in tracking period (months +1 to +6)
        $trackingEnd = (clone $baseStart)->addMonths(7)->startOfMonth();

        $trackingVisits = DB::table('visits')
            ->whereIn('user_id', $baseUserIds)
            ->where('visited_at', '>', $baseEnd)
            ->where('visited_at', '<', $trackingEnd)
            ->when($this->storeId, fn ($q) => $q->where('store_id', $this->storeId))
            ->select('user_id', 'visited_at')
            ->get();

        // Build user -> month offset map
        $userMonthMap = [];
        foreach ($trackingVisits as $tv) {
            $visitDate = Carbon::parse($tv->visited_at);
            $monthOffset = ($visitDate->year - $baseStart->year) * 12
                         + ($visitDate->month - $baseStart->month);
            if ($monthOffset >= 1 && $monthOffset <= 6) {
                $userMonthMap[$tv->user_id][$monthOffset] = true;
            }
        }

        // Step 6: Build report rows
        $this->reportData = [];
        foreach ($categories as $label => $userIds) {
            $this->reportData[$label] = $this->buildCategoryRow(
                $label,
                $userIds,
                $baseMonthVisitCounts,
                $userMonthMap,
                $baseStart,
                $now,
            );
        }
    }

    protected function buildCategoryRow(
        string $label,
        array $userIds,
        $baseMonthVisitCounts,
        array $userMonthMap,
        Carbon $baseStart,
        Carbon $now,
    ): array {
        $total = count($userIds);

        if ($total === 0) {
            return [
                'label'      => $label,
                'total'      => 0,
                'same_month' => ['count' => 0, 'pct' => 0],
                'months'     => array_fill(1, 6, [
                    'net_new' => 0, 'net_new_pct' => 0,
                    'cumulative' => 0, 'cumulative_pct' => 0,
                    'is_future' => false,
                ]),
                'lost' => ['count' => 0, 'pct' => 0],
            ];
        }

        // Same-month return: users with 2+ visits in base month
        $sameMonthCount = 0;
        $returnedUserIds = [];
        foreach ($userIds as $uid) {
            if (($baseMonthVisitCounts[$uid] ?? 1) >= 2) {
                $sameMonthCount++;
                $returnedUserIds[$uid] = true;
            }
        }

        $cumulativeCount = $sameMonthCount;
        $months = [];

        for ($m = 1; $m <= 6; $m++) {
            $monthStart = (clone $baseStart)->addMonths($m);
            $isFuture = $monthStart->startOfMonth()->isAfter($now);

            $netNew = 0;
            if (!$isFuture) {
                foreach ($userIds as $uid) {
                    if (isset($userMonthMap[$uid][$m]) && !isset($returnedUserIds[$uid])) {
                        $returnedUserIds[$uid] = true;
                        $netNew++;
                    }
                }
                $cumulativeCount += $netNew;
            }

            $months[$m] = [
                'net_new'        => $netNew,
                'net_new_pct'    => $total > 0 ? round(($netNew / $total) * 100, 1) : 0,
                'cumulative'     => $cumulativeCount,
                'cumulative_pct' => $total > 0 ? round(($cumulativeCount / $total) * 100, 1) : 0,
                'is_future'      => $isFuture,
            ];
        }

        $lostCount = $total - $cumulativeCount;

        return [
            'label'      => $label,
            'total'      => $total,
            'same_month' => [
                'count' => $sameMonthCount,
                'pct'   => $total > 0 ? round(($sameMonthCount / $total) * 100, 1) : 0,
            ],
            'months' => $months,
            'lost'   => [
                'count' => $lostCount,
                'pct'   => $total > 0 ? round(($lostCount / $total) * 100, 1) : 0,
            ],
        ];
    }

    protected function buildEmptyReport(): array
    {
        $result = [];
        foreach (['合計', '新規', '再来', '準固定', '固定'] as $label) {
            $result[$label] = [
                'label'      => $label,
                'total'      => 0,
                'same_month' => ['count' => 0, 'pct' => 0],
                'months'     => array_fill(1, 6, [
                    'net_new' => 0, 'net_new_pct' => 0,
                    'cumulative' => 0, 'cumulative_pct' => 0,
                    'is_future' => false,
                ]),
                'lost' => ['count' => 0, 'pct' => 0],
            ];
        }
        return $result;
    }

    public function exportCsv()
    {
        if (empty($this->reportData)) {
            $this->computeReport();
        }

        $filename = 'repeat_rate_' . str_replace('-', '', $this->baseMonth) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Header row
            $headers = ['カテゴリ', '来店数', '同月再来(人)', '同月再来(%)'];
            for ($m = 1; $m <= 6; $m++) {
                $label = $this->monthLabels[$m] ?? "+{$m}ヶ月";
                $headers[] = $label . ' 新規再来(人)';
                $headers[] = $label . ' 新規再来(%)';
                $headers[] = $label . ' 累計再来(人)';
                $headers[] = $label . ' 累計再来(%)';
            }
            $headers[] = '失客(人)';
            $headers[] = '失客(%)';
            fputcsv($handle, $headers);

            // Data rows
            foreach ($this->reportData as $row) {
                $csvRow = [
                    $row['label'],
                    $row['total'],
                    $row['same_month']['count'],
                    $row['same_month']['pct'] . '%',
                ];
                for ($m = 1; $m <= 6; $m++) {
                    $md = $row['months'][$m];
                    $csvRow[] = $md['is_future'] ? '-' : $md['net_new'];
                    $csvRow[] = $md['is_future'] ? '-' : $md['net_new_pct'] . '%';
                    $csvRow[] = $md['is_future'] ? '-' : $md['cumulative'];
                    $csvRow[] = $md['is_future'] ? '-' : $md['cumulative_pct'] . '%';
                }
                $csvRow[] = $row['lost']['count'];
                $csvRow[] = $row['lost']['pct'] . '%';
                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
