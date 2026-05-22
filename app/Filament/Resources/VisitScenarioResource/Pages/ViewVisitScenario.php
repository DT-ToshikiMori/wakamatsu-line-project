<?php

namespace App\Filament\Resources\VisitScenarioResource\Pages;

use App\Filament\Resources\VisitScenarioResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewVisitScenario extends ViewRecord
{
    protected static string $resource = VisitScenarioResource::class;
    protected static string $view     = 'filament.pages.visit-scenario-analytics';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return '分析: ' . ($this->record->name ?? 'シナリオ');
    }

    // ─── computed properties ───────────────────────────────────

    public function getHasCouponProperty(): bool
    {
        return DB::table('message_bubbles')
            ->where('parent_type', 'visit_scenario')
            ->where('parent_id', $this->record->id)
            ->where('bubble_type', 'coupon')
            ->exists();
    }

    public function getSentCountProperty(): int
    {
        return DB::table('visit_scenario_sends')
            ->where('scenario_id', $this->record->id)
            ->whereNotNull('sent_at')
            ->count();
    }

    public function getClaimedCountProperty(): int
    {
        if (!$this->hasCoupon) return 0;

        $bubbleIds = $this->getBubbleIds();
        if ($bubbleIds->isNotEmpty()) {
            return DB::table('user_coupons')
                ->whereIn('message_bubble_id', $bubbleIds)
                ->count();
        }
        return DB::table('visit_scenario_sends')
            ->where('scenario_id', $this->record->id)
            ->whereNotNull('user_coupon_id')
            ->count();
    }

    public function getUsedCountProperty(): int
    {
        if (!$this->hasCoupon) return 0;

        $bubbleIds = $this->getBubbleIds();
        if ($bubbleIds->isNotEmpty()) {
            return DB::table('user_coupons')
                ->whereIn('message_bubble_id', $bubbleIds)
                ->where('status', 'used')
                ->count();
        }
        $couponIds = DB::table('visit_scenario_sends')
            ->where('scenario_id', $this->record->id)
            ->whereNotNull('user_coupon_id')
            ->pluck('user_coupon_id');
        return DB::table('user_coupons')
            ->whereIn('id', $couponIds)
            ->where('status', 'used')
            ->count();
    }

    public function getClaimRateProperty(): float
    {
        return $this->sentCount > 0
            ? round($this->claimedCount / $this->sentCount * 100, 1) : 0.0;
    }

    public function getUseRateProperty(): float
    {
        return $this->claimedCount > 0
            ? round($this->usedCount / $this->claimedCount * 100, 1) : 0.0;
    }

    public function getMonthlyDataProperty(): array
    {
        $scenarioId = $this->record->id;
        $months     = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $sentRaw = DB::table('visit_scenario_sends')
            ->where('scenario_id', $scenarioId)
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("strftime('%Y-%m', sent_at) as ym, count(*) as cnt")
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        $claimedRaw = collect();
        if ($this->hasCoupon) {
            $bubbleIds = $this->getBubbleIds();
            if ($bubbleIds->isNotEmpty()) {
                $claimedRaw = DB::table('user_coupons')
                    ->whereIn('message_bubble_id', $bubbleIds)
                    ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', created_at) as ym, count(*) as cnt")
                    ->groupBy('ym')
                    ->pluck('cnt', 'ym');
            }
        }

        $rows = [];
        foreach ($months as $m) {
            $ym   = $m->format('Y-m');
            $sent = (int) ($sentRaw[$ym] ?? 0);
            $claimed = (int) ($claimedRaw[$ym] ?? 0);
            $rows[] = [
                'label'   => $m->format('Y/m'),
                'sent'    => $sent,
                'claimed' => $claimed,
                'rate'    => $sent > 0 ? round($claimed / $sent * 100, 1) : 0,
            ];
        }
        return $rows;
    }

    private function getBubbleIds()
    {
        return DB::table('message_bubbles')
            ->where('parent_type', 'visit_scenario')
            ->where('parent_id', $this->record->id)
            ->where('bubble_type', 'coupon')
            ->pluck('id');
    }
}
