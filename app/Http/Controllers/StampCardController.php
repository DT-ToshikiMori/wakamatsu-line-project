<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use App\Services\LotteryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCardController extends Controller
{

    public function card(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');

        // 未認証 → LIFF認証ページを表示（LIFF SDK初期化→セッション確立→リロード）
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $displayName = $req->attributes->get('line_display_name');
        $picture = $req->attributes->get('line_picture');

        $storeRow = DB::table('stores')->where('id', $store)->first();
        abort_if(!$storeRow, 404, 'store not found');

        // users upsert（line_user_id でグローバル一意）
        $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'store_id' => $store,
                'line_user_id' => $lineUserId,
                'display_name' => $displayName,
                'profile_image_url' => $picture,
                'first_visit_at' => null,
                'last_visit_at' => null,
                'visit_count' => 0,
                'stamp_total' => 0,
                'current_card_id' => null,
                'card_progress' => 0,
                'card_updated_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = DB::table('users')->where('id', $userId)->first();
        } else {
            // プロフィール情報を更新
            $updates = [];
            if ($displayName && $displayName !== $user->display_name) {
                $updates['display_name'] = $displayName;
            }
            if ($picture && $picture !== $user->profile_image_url) {
                $updates['profile_image_url'] = $picture;
            }
            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('users')->where('id', $user->id)->update($updates);
                $user = DB::table('users')->where('id', $user->id)->first();
            }
        }

        // 未登録項目あり → 登録フォームへリダイレクト
        if ($user->gender === null || $user->visit_frequency === null) {
            return redirect("/s/{$store}/register");
        }

        // ① ランク定義（グローバル、priority順）
        $cards = DB::table('stamp_card_definitions')
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
        abort_if($cards->isEmpty(), 500, 'stamp_card_definitions is empty');

        // ② 初回ユーザーは最初のカードに入れる
        if (!$user->current_card_id) {
            $first = $cards->first();
            DB::table('users')->where('id', $user->id)->update([
                'current_card_id' => $first->id,
                'card_progress' => 0,
                'card_updated_at' => now(),
            ]);
            $user->current_card_id = $first->id;
            $user->card_progress = 0;
        }

        // ③ 現在カード
        $currentCard = $cards->firstWhere('id', $user->current_card_id);
        // 何かの理由で current_card_id が無効なら、最初のカードへ戻す
        if (!$currentCard) {
            $first = $cards->first();
            DB::table('users')->where('id', $user->id)->update([
                'current_card_id' => $first->id,
                'card_progress' => 0,
                'card_updated_at' => now(),
            ]);
            $user->current_card_id = $first->id;
            $user->card_progress = 0;
            $currentCard = $first;
        }

        $isBeginner = ($currentCard->name === 'BEGINNER');
        $isGold = !$isBeginner;

        $goal = (int) $currentCard->required_stamps;
        $progress = (int) ($user->card_progress ?? 0);
        $remaining = max(0, $goal - $progress);

        $nextCard = $cards->firstWhere('priority', $currentCard->priority + 1);
        if ($isBeginner) {
            $nextReward = [
                'at' => $goal,
                'text' => ($nextCard ? ($nextCard->display_name . ' 昇格') : 'ランクアップ')
            ];
        } else {
            $nextReward = ['at' => null, 'text' => '会員特典'];
            $remaining = 0;
        }

        $flash = $req->query('ok');

        return view('stamp.card', [
            'store' => $storeRow,
            'user' => $user,
            'lineUserId' => $lineUserId,

            'goal' => $goal,
            'isGold' => $isGold,
            'progress' => $progress,
            'nextReward' => $nextReward,
            'remaining' => $remaining,
            'flash' => $flash,

            'cards' => $cards,
            'currentCard' => $currentCard,
            'nextCard' => $nextCard,
            'isBeginner' => $isBeginner,
        ]);
    }

    public function registerForm(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $storeRow = DB::table('stores')->where('id', $store)->first();
        abort_if(!$storeRow, 404, 'store not found');

        $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
        abort_if(!$user, 404, 'user not found');

        // 既に登録済みならカードページへ
        if ($user->gender !== null && $user->visit_frequency !== null) {
            return redirect("/s/{$store}/card");
        }

        return view('stamp.register', [
            'store' => $storeRow,
            'user' => $user,
        ]);
    }

    public function registerSave(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
        abort_if(!$user, 404, 'user not found');

        $validated = $req->validate([
            'visit_frequency' => 'required|in:new,2_3,4plus',
            'gender' => 'required|in:male,female,other',
            'birth_year' => 'nullable|integer|min:1920|max:' . date('Y'),
            'birth_month' => 'nullable|integer|min:1|max:12',
        ]);

        DB::table('users')->where('id', $user->id)->update([
            'visit_frequency' => $validated['visit_frequency'],
            'gender' => $validated['gender'],
            'birth_year' => $validated['birth_year'] ?? null,
            'birth_month' => $validated['birth_month'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect("/s/{$store}/card");
    }

    public function checkin(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        // QRリンクから stamp_count を取得（デフォルト1）
        $stampCount = 1;
        $qrLinkId = $req->input('qr_link_id');
        $qrLink = null;
        if ($qrLinkId) {
            $qrLink = DB::table('store_qr_links')->where('id', $qrLinkId)->first();
            if ($qrLink) {
                $stampCount = (int) ($qrLink->stamp_count ?? 1);
            }
        }

        $visitedAt = now();
        $requestId = 'card_' . bin2hex(random_bytes(8));

        $rankUpResults = [];
        $issuedCoupon = null;
        $lotteryResult = null;
        $currentCardBeforeUpgrade = null;

        DB::transaction(function () use ($store, $user, $visitedAt, $requestId, $stampCount, $qrLinkId, &$rankUpResults, &$currentCardBeforeUpgrade) {

            // ① visit log（店舗スコープ維持）
            DB::table('visits')->insert([
                'store_id' => $store,
                'user_id' => $user->id,
                'qr_link_id' => $qrLinkId,
                'visited_at' => $visitedAt,
                'request_id' => $requestId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ② ランク定義を取得（グローバル、priority順）
            $cards = DB::table('stamp_card_definitions')
                ->where('is_active', true)
                ->orderBy('priority')
                ->get();

            // ③ 初回ユーザーは最初のランクへ
            if (!$user->current_card_id) {
                $first = $cards->first();
                DB::table('users')->where('id', $user->id)->update([
                    'current_card_id' => $first->id,
                    'card_progress' => 0,
                    'card_updated_at' => $visitedAt,
                ]);
                $user->current_card_id = $first->id;
                $user->card_progress = 0;
            }

            $currentCard = $cards->firstWhere('id', $user->current_card_id);
            $currentCardBeforeUpgrade = $currentCard;

            $nextStampTotal = ($user->stamp_total ?? 0) + $stampCount;
            $nextProgress = ($user->card_progress ?? 0) + $stampCount;

            // ④ マルチランクアップ対応: stamp_count が2以上の場合、複数ランクを一気に上がる可能性
            $activeCard = $currentCard;
            $remainingProgress = $nextProgress;

            while ($activeCard && $remainingProgress >= $activeCard->required_stamps) {
                $nextCard = $cards->firstWhere('priority', $activeCard->priority + 1);

                if ($nextCard) {
                    // ランクアップ記録
                    $rankUpResults[] = [
                        'from_card' => $activeCard,
                        'to_card' => $nextCard,
                    ];
                    $remainingProgress = $remainingProgress - $activeCard->required_stamps;
                    $activeCard = $nextCard;
                } else {
                    // 最高ランク: プログレスをリセット
                    $remainingProgress = 0;
                    break;
                }
            }

            // ユーザー更新
            DB::table('users')->where('id', $user->id)->update([
                'stamp_total' => $nextStampTotal,
                'current_card_id' => $activeCard->id,
                'card_progress' => $remainingProgress,
                'card_updated_at' => $visitedAt,
            ]);

            // ⑤ visit系
            DB::table('users')->where('id', $user->id)->update([
                'last_visit_at' => $visitedAt,
                'first_visit_at' => DB::raw("COALESCE(first_visit_at, '{$visitedAt->format('Y-m-d H:i:s')}')"),
                'visit_count' => DB::raw('visit_count + 1'),
                'updated_at' => now(),
            ]);
        });

        // ⑥ 再取得
        $newUser = DB::table('users')->where('id', $user->id)->first();

        // チェックイン時クーポン処理（currentCard の checkin_coupon_id）
        if ($currentCardBeforeUpgrade && !empty($currentCardBeforeUpgrade->checkin_coupon_id)) {
            $checkinCouponResult = $this->processCouponTrigger(
                null, $user->id, $lineUserId,
                $currentCardBeforeUpgrade->checkin_coupon_id,
                'checkin',
                $currentCardBeforeUpgrade->checkin_coupon_expires_days ?? null
            );
            if ($checkinCouponResult) {
                if (isset($checkinCouponResult['lottery'])) {
                    $lotteryResult = $checkinCouponResult['lottery'];
                } elseif (isset($checkinCouponResult['coupon'])) {
                    $issuedCoupon = $checkinCouponResult['coupon'];
                }
            }
        }

        // マルチランクアップ時クーポン処理: 各ランクアップごとにクーポン発行
        $upgradedToDisplayName = null;
        foreach ($rankUpResults as $rankUp) {
            $toCard = $rankUp['to_card'];
            $upgradedToDisplayName = $toCard->display_name ?? $toCard->name ?? 'RANK UP';

            if (!empty($toCard->rankup_coupon_id)) {
                $rankupResult = $this->processCouponTrigger(
                    null, $user->id, $lineUserId,
                    $toCard->rankup_coupon_id,
                    'rank_up',
                    $toCard->rankup_coupon_expires_days ?? null
                );
                if ($rankupResult) {
                    if (isset($rankupResult['lottery'])) {
                        $lotteryResult = $rankupResult['lottery'];
                    } elseif (isset($rankupResult['coupon'])) {
                        $issuedCoupon = $rankupResult['coupon'];
                    }
                }
            } else {
                // 旧方式: coupon_templates の type=rank_up & rank_card_id で検索
                $tpl = DB::table('coupon_templates')
                    ->where('type', 'rank_up')
                    ->where('rank_card_id', $toCard->id)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();

                if ($tpl) {
                    if (($tpl->mode ?? 'normal') === 'lottery') {
                        $lotteryResult = app(LotteryService::class)->draw(null, $user->id, $tpl->id, 'rank_up');
                    } else {
                        $userCouponId = DB::table('user_coupons')->insertGetId([
                            'store_id' => null,
                            'user_id' => $user->id,
                            'coupon_template_id' => $tpl->id,
                            'message_bubble_id' => null,
                            'status' => 'issued',
                            'issued_at' => now(),
                            'used_at' => null,
                            'expires_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('coupon_events')->insert([
                            'user_coupon_id' => $userCouponId,
                            'event' => 'issued',
                            'actor' => 'system',
                            'created_at' => now(),
                        ]);

                        $issuedCoupon = [
                            'user_coupon_id' => $userCouponId,
                            'title' => $tpl->title,
                            'note' => $tpl->note,
                            'image_url' => $tpl->image_url,
                        ];
                    }
                }
            }
        }

        // プッシュ通知（ランクアップがあった場合）
        if (!empty($rankUpResults)) {
            try {
                $msg = $issuedCoupon
                    ? "🎉 {$upgradedToDisplayName}にランクアップしました！クーポン「{$issuedCoupon['title']}」が発行されました。"
                    : "🎉 {$upgradedToDisplayName}にランクアップしました！おめでとうございます！";
                app(LineBotService::class)->pushText($lineUserId, $msg);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Push notification failed', ['error' => $e->getMessage()]);
            }
        }

        // 来店シナリオ: visit_scenario_sends に scheduled_at を積む
        $visitCount = (int) $newUser->visit_count;
        $currentCardId = $newUser->current_card_id;

        $scenarios = DB::table('visit_scenarios')
            ->where('is_active', true)
            ->where('stamp_card_definition_id', $currentCardId)
            ->where(function ($q) use ($visitCount, $newUser) {
                // カード×スタンプ目 方式
                $q->where(function ($q2) use ($newUser) {
                    $q2->whereNotNull('stamp_number')
                       ->where('stamp_number', (int)($newUser->card_progress));
                })
                // N回以降ずっと 方式
                ->orWhere(function ($q2) use ($visitCount) {
                    $q2->whereNull('stamp_number')
                       ->whereNotNull('from_visit_count')
                       ->whereRaw('? >= from_visit_count', [$visitCount]);
                });
            })
            ->get();

        // セグメントフィルター適用
        $userSegment = $newUser->visit_frequency ?? null;
        $scenarios = $scenarios->filter(function ($scenario) use ($userSegment) {
            if (!$scenario->segment_filter) return true; // null = 全員
            return $scenario->segment_filter === $userSegment;
        });

        foreach ($scenarios as $scenario) {
            $scheduledAt = now()->addHours((int) ($scenario->delay_hours ?? 0));
            DB::table('visit_scenario_sends')->insert([
                'user_id'      => $user->id,
                'scenario_id'  => $scenario->id,
                'scheduled_at' => $scheduledAt,
                'sent_at'      => null,
                'coupon_issued_at' => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        if ($req->expectsJson()) {
            $response = [
                'ok' => true,
                'stamp_total' => (int) $newUser->stamp_total,
                'card_progress' => (int) $newUser->card_progress,
                'current_card_id' => $newUser->current_card_id,
                'visit_count' => (int) $newUser->visit_count,
                'last_visit_at' => (string) $newUser->last_visit_at,
                'upgraded_to_gold' => !empty($rankUpResults),
                'upgraded_to' => $upgradedToDisplayName,
                'issued_coupon' => $issuedCoupon,
            ];

            if ($lotteryResult) {
                $response['lottery'] = $lotteryResult;
            }

            return response()->json($response);
        }

        return redirect("/s/{$store}/card");
    }

    /**
     * クーポンテンプレートに基づいてクーポン付与 or 抽選を実行
     */
    private function processCouponTrigger(?int $storeId, int $userId, string $lineUserId, int $couponTemplateId, string $triggerType, ?int $expiresDays = null): ?array
    {
        $tpl = DB::table('coupon_templates')
            ->where('id', $couponTemplateId)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            return null;
        }

        $expiresAt = $expiresDays ? now()->addDays($expiresDays) : null;

        if (($tpl->mode ?? 'normal') === 'lottery') {
            $result = app(LotteryService::class)->draw($storeId, $userId, $tpl->id, $triggerType, $expiresAt);
            return ['lottery' => $result];
        }

        // 通常クーポン付与
        $userCouponId = DB::table('user_coupons')->insertGetId([
            'store_id' => $storeId,
            'user_id' => $userId,
            'coupon_template_id' => $tpl->id,
            'message_bubble_id' => null,
            'status' => 'issued',
            'issued_at' => now(),
            'used_at' => null,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('coupon_events')->insert([
            'user_coupon_id' => $userCouponId,
            'event' => 'issued',
            'actor' => 'system',
            'created_at' => now(),
        ]);

        return [
            'coupon' => [
                'user_coupon_id' => $userCouponId,
                'title' => $tpl->title,
                'note' => $tpl->note,
                'image_url' => $tpl->image_url,
            ],
        ];
    }

    public function clear(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        DB::transaction(function () use ($store, $user) {
            DB::table('visits')
                ->where('store_id', $store)
                ->where('user_id', $user->id)
                ->delete();

            DB::table('users')->where('id', $user->id)->update([
                'first_visit_at' => null,
                'last_visit_at' => null,
                'visit_count' => 0,
                'stamp_total' => 0,
                'current_card_id' => null,
                'card_progress' => 0,
                'card_updated_at' => null,
                'updated_at' => now(),
            ]);
        });

        if ($req->expectsJson()) {
            return response()->json([
                'ok' => true,
                'stamp_total' => 0,
                'card_progress' => 0,
                'current_card_id' => null,
                'visit_count' => 0,
                'last_visit_at' => '',
                'stamp' => 0,
                'cleared' => true,
            ]);
        }

        return redirect("/s/{$store}/card");
    }
}
