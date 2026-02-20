<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LotteryService
{
    /**
     * 抽選を実行する
     *
     * @return array{is_win: bool, prize: object, user_coupon_id: int|null, prizes: array}
     */
    public function draw(int $storeId, int $userId, int $couponTemplateId, string $triggerType, ?\Carbon\Carbon $expiresAt = null): array
    {
        $prizes = DB::table('lottery_prizes')
            ->where('coupon_template_id', $couponTemplateId)
            ->orderBy('rank')
            ->get();

        if ($prizes->isEmpty()) {
            throw new \RuntimeException("No lottery prizes configured for coupon_template_id={$couponTemplateId}");
        }

        // 重み付きランダム選択
        $selectedPrize = $this->weightedRandom($prizes);

        $isWin = !$selectedPrize->is_miss;
        $userCouponId = null;
        $now = now();

        if ($isWin) {
            // 当選：user_coupons + coupon_events にINSERT
            $userCouponId = DB::table('user_coupons')->insertGetId([
                'store_id' => $storeId,
                'user_id' => $userId,
                'coupon_template_id' => $couponTemplateId,
                'status' => 'issued',
                'issued_at' => $now,
                'used_at' => null,
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('coupon_events')->insert([
                'user_coupon_id' => $userCouponId,
                'event' => 'issued',
                'actor' => 'system',
                'created_at' => $now,
            ]);
        }

        // lottery_results に履歴INSERT
        DB::table('lottery_results')->insert([
            'store_id' => $storeId,
            'user_id' => $userId,
            'coupon_template_id' => $couponTemplateId,
            'lottery_prize_id' => $selectedPrize->id,
            'user_coupon_id' => $userCouponId,
            'trigger_type' => $triggerType,
            'is_win' => $isWin,
            'drawn_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // スロットUI用のデータ
        $allPrizes = $prizes->map(fn ($p) => [
            'id' => $p->id,
            'rank' => $p->rank,
            'title' => $p->title,
            'image_url' => $p->image_url,
            'is_miss' => (bool) $p->is_miss,
        ])->values()->toArray();

        return [
            'is_win' => $isWin,
            'prize' => [
                'id' => $selectedPrize->id,
                'rank' => $selectedPrize->rank,
                'title' => $selectedPrize->title,
                'image_url' => $selectedPrize->image_url,
                'is_miss' => (bool) $selectedPrize->is_miss,
            ],
            'user_coupon_id' => $userCouponId,
            'prizes' => $allPrizes,
        ];
    }

    /**
     * 重み付きランダム選択（probability をウェイトとして使用）
     */
    private function weightedRandom($prizes): object
    {
        $totalWeight = $prizes->sum('probability');
        $random = mt_rand(1, $totalWeight);

        $cumulative = 0;
        foreach ($prizes as $prize) {
            $cumulative += $prize->probability;
            if ($random <= $cumulative) {
                return $prize;
            }
        }

        // フォールバック（到達しないはず）
        return $prizes->last();
    }
}
