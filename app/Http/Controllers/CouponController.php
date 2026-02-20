<?php

namespace App\Http\Controllers;

use App\Services\LotteryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    /**
     * クーポン一覧（DB駆動）
     */
    public function index(Request $req)
    {
        $storeId = (int) $req->query('store', 0);
        abort_if($storeId <= 0, 400, 'store is required');

        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $coupons = DB::table('user_coupons as uc')
            ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
            ->where('uc.store_id', $storeId)
            ->where('uc.user_id', $user->id)
            ->select([
                'uc.id as user_coupon_id',
                'uc.status',
                'uc.issued_at',
                'uc.used_at',
                'uc.expires_at',
                'ct.type',
                'ct.title',
                'ct.note',
                'ct.image_url',
            ])
            ->orderByRaw("CASE uc.status WHEN 'issued' THEN 0 WHEN 'used' THEN 1 ELSE 2 END")
            ->orderByDesc('uc.issued_at')
            ->get();

        return view('coupons.index', [
            'storeId' => $storeId,
            'coupons' => $coupons,
        ]);
    }

    /**
     * クーポン詳細
     */
    public function show(Request $req, int $userCouponId)
    {
        $storeId = (int) $req->query('store', 0);
        abort_if($storeId <= 0, 400, 'store is required');

        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $coupon = DB::table('user_coupons as uc')
            ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
            ->where('uc.id', $userCouponId)
            ->where('uc.store_id', $storeId)
            ->where('uc.user_id', $user->id)
            ->select([
                'uc.id as user_coupon_id',
                'uc.status',
                'uc.issued_at',
                'uc.used_at',
                'uc.expires_at',
                'ct.type',
                'ct.title',
                'ct.note',
                'ct.image_url',
            ])
            ->first();
        abort_if(!$coupon, 404, 'coupon not found');

        return view('coupons.show', [
            'storeId' => $storeId,
            'coupon' => $coupon,
            'isUsed' => !empty($coupon->used_at) || $coupon->status === 'used',
            'usedAt' => $coupon->used_at,
        ]);
    }

    /**
     * クーポン取得ページ（LIFF内で開く）
     */
    public function claimPage(Request $req)
    {
        $storeId = (int) $req->query('store', 0);
        $bubbleId = (int) $req->query('bubble_id', 0);
        $tplId = (int) $req->query('tpl_id', 0);
        $sentAt = (int) $req->query('sent_at', 0);

        abort_if($storeId <= 0 || !$bubbleId || !$tplId, 400, 'パラメータが不正です');

        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $bubble = DB::table('message_bubbles')->where('id', $bubbleId)->first();
        abort_if(!$bubble, 404, 'coupon not found');

        $tpl = DB::table('coupon_templates')
            ->where('id', $tplId)
            ->where('is_active', true)
            ->first();
        abort_if(!$tpl, 404, 'coupon template not found');

        // 既に取得済みか確認
        $existing = DB::table('user_coupons')
            ->where('user_id', $user->id)
            ->where('message_bubble_id', $bubbleId)
            ->first();

        // 有効期限計算
        $expiresAt = $this->calculateExpiresAt($bubble, $sentAt);
        $isExpired = $expiresAt && $expiresAt->isPast();

        return view('coupons.claim', [
            'storeId' => $storeId,
            'bubbleId' => $bubbleId,
            'tplId' => $tplId,
            'sentAt' => $sentAt,
            'tpl' => $tpl,
            'existing' => $existing,
            'isExpired' => $isExpired,
            'expiresAt' => $expiresAt,
            'isLottery' => ($tpl->mode ?? 'normal') === 'lottery',
        ]);
    }

    /**
     * クーポン取得処理（LIFF内からPOST）
     */
    public function claim(Request $req)
    {
        $storeId = (int) $req->input('store', 0);
        $bubbleId = (int) $req->input('bubble_id', 0);
        $tplId = (int) $req->input('tpl_id', 0);
        $sentAt = (int) $req->input('sent_at', 0);

        abort_if($storeId <= 0 || !$bubbleId || !$tplId, 400, 'パラメータが不正です');

        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $bubble = DB::table('message_bubbles')->where('id', $bubbleId)->first();
        abort_if(!$bubble, 404, 'coupon not found');

        $tpl = DB::table('coupon_templates')
            ->where('id', $tplId)
            ->where('is_active', true)
            ->first();
        abort_if(!$tpl, 404, 'coupon template not found');

        // 重複チェック
        $existing = DB::table('user_coupons')
            ->where('user_id', $user->id)
            ->where('message_bubble_id', $bubbleId)
            ->exists();

        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'already_claimed', 'message' => 'このクーポンは既に取得済みです'], 409);
        }

        // 有効期限計算・チェック
        $expiresAt = $this->calculateExpiresAt($bubble, $sentAt);
        if ($expiresAt && $expiresAt->isPast()) {
            return response()->json(['ok' => false, 'error' => 'expired', 'message' => 'このクーポンの有効期限が過ぎています'], 422);
        }

        $now = now();

        // 抽選モード
        if (($tpl->mode ?? 'normal') === 'lottery') {
            $result = app(LotteryService::class)->draw($user->store_id, $user->id, $tpl->id, 'manual', $expiresAt);

            if ($result['is_win'] && $result['user_coupon_id']) {
                DB::table('user_coupons')
                    ->where('id', $result['user_coupon_id'])
                    ->update(['message_bubble_id' => $bubbleId, 'expires_at' => $expiresAt]);
            } else {
                DB::table('user_coupons')->insert([
                    'store_id' => $user->store_id,
                    'user_id' => $user->id,
                    'coupon_template_id' => $tpl->id,
                    'message_bubble_id' => $bubbleId,
                    'status' => 'used',
                    'issued_at' => $now,
                    'used_at' => $now,
                    'expires_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return response()->json([
                'ok' => true,
                'is_lottery' => true,
                'is_win' => $result['is_win'],
                'prize' => $result['prize'],
                'user_coupon_id' => $result['user_coupon_id'],
            ]);
        }

        // 通常クーポン取得
        try {
            $userCouponId = DB::table('user_coupons')->insertGetId([
                'store_id' => $user->store_id,
                'user_id' => $user->id,
                'coupon_template_id' => $tpl->id,
                'message_bubble_id' => $bubbleId,
                'status' => 'issued',
                'issued_at' => $now,
                'used_at' => null,
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'user_coupons_user_bubble_unique')) {
                return response()->json(['ok' => false, 'error' => 'already_claimed', 'message' => 'このクーポンは既に取得済みです'], 409);
            }
            throw $e;
        }

        DB::table('coupon_events')->insert([
            'user_coupon_id' => $userCouponId,
            'event' => 'issued',
            'actor' => 'user',
            'created_at' => $now,
        ]);

        return response()->json([
            'ok' => true,
            'user_coupon_id' => $userCouponId,
            'expires_at' => $expiresAt?->format('Y/m/d H:i'),
        ]);
    }

    private function calculateExpiresAt(object $bubble, int $sentAt): ?Carbon
    {
        if (!empty($bubble->coupon_expires_at)) {
            return Carbon::parse($bubble->coupon_expires_at);
        }
        if (!empty($bubble->coupon_expires_days) && $sentAt > 0) {
            return Carbon::createFromTimestamp($sentAt)->addDays((int) $bubble->coupon_expires_days);
        }
        return null;
    }

    /**
     * クーポン使用確定（スタッフ）
     */
    public function use(Request $req, int $userCouponId)
    {
        $storeId = (int) $req->input('store', 0);
        abort_if($storeId <= 0, 400, 'store is required');

        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $now = now();

        // 期限切れチェック
        $coupon = DB::table('user_coupons')
            ->where('id', $userCouponId)
            ->where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->first();
        abort_if(!$coupon, 404, 'coupon not found');

        if ($coupon->expires_at && $now->greaterThan($coupon->expires_at)) {
            DB::table('user_coupons')
                ->where('id', $userCouponId)
                ->update(['status' => 'expired', 'updated_at' => $now]);

            if ($req->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'このクーポンは有効期限切れです'], 422);
            }
            abort(422, 'このクーポンは有効期限切れです');
        }

        $updated = DB::table('user_coupons')
            ->where('id', $userCouponId)
            ->where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->where('status', 'issued')
            ->whereNull('used_at')
            ->update([
                'status' => 'used',
                'used_at' => $now,
                'updated_at' => $now,
            ]);

        if ($req->expectsJson()) {
            return response()->json([
                'ok' => true,
                'updated' => $updated > 0,
                'used_at' => $now->format('Y/m/d H:i'),
            ]);
        }

        return redirect()->route('coupons.show', [
            'userCouponId' => $userCouponId,
            'store' => $storeId,
        ]);
    }
}
