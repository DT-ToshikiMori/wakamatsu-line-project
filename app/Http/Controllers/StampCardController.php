<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCardController extends Controller
{

    public function card(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $displayName = $req->attributes->get('line_display_name');
        $picture = $req->attributes->get('line_picture');

        $storeRow = DB::table('stores')->where('id', $store)->first();
        abort_if(!$storeRow, 404, 'store not found');

        // users upsert（店舗×LINEユーザー）
        $user = DB::table('users')->where('store_id', $store)->where('line_user_id', $lineUserId)->first();
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

        // ① 店舗のランク定義（priority順）
        $cards = DB::table('stamp_card_definitions')
            ->where('store_id', $store)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
        abort_if($cards->isEmpty(), 500, 'stamp_card_definitions is empty for this store');

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

    public function checkin(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('store_id', $store)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $visitedAt = now();
        $requestId = 'card_' . bin2hex(random_bytes(8));

        $upgraded = false;
        $upgradedToCardId = null;
        $upgradedToDisplayName = null;
        $issuedCoupon = null;

        DB::transaction(function () use ($store, $user, $visitedAt, $requestId, &$upgraded, &$upgradedToCardId, &$upgradedToDisplayName) {

            // ① visit log
            DB::table('visits')->insert([
                'store_id' => $store,
                'user_id' => $user->id,
                'qr_link_id' => null,
                'visited_at' => $visitedAt,
                'request_id' => $requestId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ② ランク定義を取得（priority順）
            $cards = DB::table('stamp_card_definitions')
                ->where('store_id', $store)
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

            $nextStampTotal = ($user->stamp_total ?? 0) + 1;
            $nextProgress = ($user->card_progress ?? 0) + 1;

            // ④ ランクアップ判定
            if ($nextProgress >= $currentCard->required_stamps) {

                $nextCard = $cards->firstWhere(
                    'priority',
                    $currentCard->priority + 1
                );

                if ($nextCard) {
                    DB::table('users')->where('id', $user->id)->update([
                        'stamp_total' => $nextStampTotal,
                        'current_card_id' => $nextCard->id,
                        'card_progress' => 0,
                        'card_updated_at' => $visitedAt,
                    ]);
                    $upgraded = true;
                    $upgradedToCardId = $nextCard->id;
                    $upgradedToDisplayName = $nextCard->display_name ?? $nextCard->name ?? 'RANK UP';
                } else {
                    DB::table('users')->where('id', $user->id)->update([
                        'stamp_total' => $nextStampTotal,
                        'card_progress' => 0,
                    ]);
                }

            } else {
                DB::table('users')->where('id', $user->id)->update([
                    'stamp_total' => $nextStampTotal,
                    'card_progress' => $nextProgress,
                ]);
            }

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

        // Issue rank-up coupon if upgraded
        if ($upgraded && $upgradedToCardId) {
            $tpl = DB::table('coupon_templates')
                ->where('store_id', $store)
                ->where('type', 'rank_up')
                ->where('rank_card_id', $upgradedToCardId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($tpl) {
                $userCouponId = DB::table('user_coupons')->insertGetId([
                    'store_id' => $store,
                    'user_id' => $user->id,
                    'coupon_template_id' => $tpl->id,
                    'status' => 'issued',
                    'issued_at' => now(),
                    'used_at' => null,
                    'expires_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (DB::getSchemaBuilder()->hasTable('coupon_events')) {
                    DB::table('coupon_events')->insert([
                        'user_coupon_id' => $userCouponId,
                        'event' => 'issued',
                        'actor' => 'system',
                        'created_at' => now(),
                    ]);
                }

                $issuedCoupon = [
                    'user_coupon_id' => $userCouponId,
                    'title' => $tpl->title,
                    'note' => $tpl->note,
                    'image_url' => $tpl->image_url,
                ];

                // プッシュ通知：クーポン発行
                try {
                    app(LineBotService::class)->pushText(
                        $lineUserId,
                        "🎉 {$upgradedToDisplayName}にランクアップしました！クーポン「{$tpl->title}」が発行されました。"
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Push notification failed', ['error' => $e->getMessage()]);
                }
            }

            // プッシュ通知：ランクアップ（クーポンがない場合でも）
            if (!$tpl) {
                try {
                    app(LineBotService::class)->pushText(
                        $lineUserId,
                        "🎉 {$upgradedToDisplayName}にランクアップしました！おめでとうございます！"
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Push notification failed', ['error' => $e->getMessage()]);
                }
            }
        }

        if ($req->expectsJson()) {
            return response()->json([
                'ok' => true,
                'stamp_total' => (int) $newUser->stamp_total,
                'card_progress' => (int) $newUser->card_progress,
                'current_card_id' => $newUser->current_card_id,
                'visit_count' => (int) $newUser->visit_count,
                'last_visit_at' => (string) $newUser->last_visit_at,
                'upgraded_to_gold' => $upgraded,
                'upgraded_to' => $upgradedToDisplayName,
                'issued_coupon' => $issuedCoupon,
            ]);
        }

        return redirect("/s/{$store}/card");
    }

    public function clear(Request $req, int $store)
    {
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('store_id', $store)
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
