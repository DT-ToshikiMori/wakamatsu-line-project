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
        $lineUserId = (string) $req->query('u', '');
        abort_if($storeId <= 0, 400, 'store is required');
        abort_if($lineUserId === '', 400, 'u is required');

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
            'lineUserId' => $lineUserId,
            'coupons' => $coupons,
        ]);
    }

    /**
     * クーポン詳細
     */
    public function show(Request $req, int $userCouponId)
    {
        $storeId = (int) $req->query('store', 0);
        $lineUserId = (string) $req->query('u', '');
        abort_if($storeId <= 0, 400, 'store is required');
        abort_if($lineUserId === '', 400, 'u is required');

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
            'lineUserId' => $lineUserId,
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
        $lineUserId = (string) $req->input('u', '');
        abort_if($storeId <= 0, 400, 'store is required');
        abort_if($lineUserId === '', 400, 'u is required');

        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $now = now();

        $updated = DB::table('user_coupons')
            ->where('id', $userCouponId)
            ->where('store_id', $storeId)
            ->where('user_id', $user->id)
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
            'u' => $lineUserId,
        ]);
    }
}