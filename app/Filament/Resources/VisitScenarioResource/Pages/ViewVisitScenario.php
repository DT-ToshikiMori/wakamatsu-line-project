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
        if ($this->record->coupon_template_id !== null) {
            return true;
        }

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

        return DB::table('visit_scenario_sends')
            ->where('scenario_id', $this->record->id)
            ->whereNotNull('user_coupon_id')
            ->count();
    }

    public function getUsedCountProperty(): int
    {
        if (!$this->hasCoupon) return 0;

        return DB::table('visit_scenario_sends as vss')
            ->join('user_coupons as uc', 'uc.id', '=', 'vss.user_coupon_id')
            ->where('vss.scenario_id', $this->record->id)
            ->where('uc.status', 'used')
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
            ->selectRaw($this->monthKeyExpression('sent_at') . ' as ym, count(*) as cnt')
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        $claimedRaw = collect();
        if ($this->hasCoupon) {
            $claimedRaw = DB::table('visit_scenario_sends')
                ->where('scenario_id', $scenarioId)
                ->whereNotNull('coupon_issued_at')
                ->where('coupon_issued_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw($this->monthKeyExpression('coupon_issued_at') . ' as ym, count(*) as cnt')
                ->groupBy('ym')
                ->pluck('cnt', 'ym');
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

    private function monthKeyExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'mysql', 'mariadb' => "date_format({$column}, '%Y-%m')",
            'sqlsrv' => "format({$column}, 'yyyy-MM')",
            default => "strftime('%Y-%m', {$column})",
        };
    }
}
