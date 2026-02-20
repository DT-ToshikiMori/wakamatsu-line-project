<?php

namespace App\Http\Controllers;

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
