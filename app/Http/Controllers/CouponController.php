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
        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        // 来店シナリオ経由: scenario_id があればクーポン自動発行
        $scenarioId = (int) $req->query('scenario_id', 0);
        if ($scenarioId) {
            $this->claimScenarioCoupon($user, $scenarioId);
        }

        $coupons = DB::table('user_coupons as uc')
            ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
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

        $storeId = (int) $req->query('store', 0) ?: (int) ($user->store_id ?? 0);

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
        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $coupon = DB::table('user_coupons as uc')
            ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
            ->where('uc.id', $userCouponId)
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

        $storeId = (int) $req->query('store', 0) ?: (int) ($user->store_id ?? 0);

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
        $bubbleId = (int) $req->query('bubble_id', 0);
        $tplId = (int) $req->query('tpl_id', 0);
        $sentAt = (int) $req->query('sent_at', 0);

        abort_if(!$bubbleId || !$tplId, 400, 'パラメータが不正です');

        $lineUserId = $req->attributes->get('line_user_id');
        if (!$lineUserId) {
            return response()->view('liff-auth');
        }

        $user = DB::table('users')
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

        $storeId = (int) $req->query('store', 0) ?: (int) ($user->store_id ?? 0);

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
        $bubbleId = (int) $req->input('bubble_id', 0);
        $tplId = (int) $req->input('tpl_id', 0);
        $sentAt = (int) $req->input('sent_at', 0);

        abort_if(!$bubbleId || !$tplId, 400, 'パラメータが不正です');

        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
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
            $result = app(LotteryService::class)->draw(null, $user->id, $tpl->id, 'manual', $expiresAt);

            if ($result['is_win'] && $result['user_coupon_id']) {
                DB::table('user_coupons')
                    ->where('id', $result['user_coupon_id'])
                    ->update(['message_bubble_id' => $bubbleId, 'expires_at' => $expiresAt]);
            } else {
                DB::table('user_coupons')->insert([
                    'store_id' => null,
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
                'store_id' => null,
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

    /**
     * 来店シナリオ経由のクーポン発行（LIFF遷移時に自動発行）
     */
    private function claimScenarioCoupon(object $user, int $scenarioId): void
    {
        DB::transaction(function () use ($user, $scenarioId): void {
            $this->claimScenarioCouponInTransaction($user, $scenarioId);
        });
    }

    private function claimScenarioCouponInTransaction(object $user, int $scenarioId): void
    {
        // 未発行の送信レコードを確認（二重発行防止: coupon_issued_at IS NULL）
        $send = DB::table('visit_scenario_sends')
            ->where('user_id', $user->id)
            ->where('scenario_id', $scenarioId)
            ->whereNotNull('sent_at')
            ->whereNull('coupon_issued_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$send) {
            return;
        }

        $scenario = DB::table('visit_scenarios')
            ->where('id', $scenarioId)
            ->first();

        if (!$scenario) {
            return;
        }

        // 新方式: message_bubbles のクーポン設定を優先。旧方式は scenario の coupon_template_id を使う。
        $bubble = DB::table('message_bubbles as mb')
            ->join('coupon_templates as ct', 'ct.id', '=', 'mb.coupon_template_id')
            ->where('mb.parent_type', 'visit_scenario')
            ->where('mb.parent_id', $scenarioId)
            ->where('mb.bubble_type', 'coupon')
            ->where('ct.is_active', true)
            ->orderBy('mb.position')
            ->orderBy('mb.id')
            ->select('mb.*')
            ->first();

        $tplId = $bubble?->coupon_template_id ?? $scenario->coupon_template_id;
        if (!$tplId) {
            return;
        }

        $tpl = DB::table('coupon_templates')
            ->where('id', $tplId)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            return;
        }

        $now = now();
        $expiresAt = $this->calculateScenarioCouponExpiresAt($scenario, $bubble, $now);

        // 来店シナリオは同じバブルを繰り返し送る可能性があるため、
        // user_coupons.message_bubble_id の一意制約ではなく visit_scenario_sends.user_coupon_id で送信単位に紐づける。
        $userCouponId = DB::table('user_coupons')->insertGetId([
            'store_id' => null,
            'user_id' => $user->id,
            'coupon_template_id' => $tpl->id,
            'message_bubble_id' => null,
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

        $this->markScenarioCouponIssued($send->id, $scenario, $userCouponId, $now, $expiresAt, $now);
    }

    private function markScenarioCouponIssued(
        int $sendId,
        object $scenario,
        int $userCouponId,
        Carbon $couponIssuedAt,
        ?Carbon $expiresAt,
        Carbon $updatedAt
    ): void {
        // リマインドスケジュール計算
        $reminderScheduledAt = null;
        if ($scenario->reminder_enabled && $expiresAt) {
            $reminderHour = (int) ($scenario->reminder_hour ?? 10);
            $reminderBeforeDays = (int) ($scenario->reminder_before_days ?? 3);
            $reminderScheduledAt = $expiresAt->copy()
                ->subDays($reminderBeforeDays)
                ->startOfDay()
                ->addHours($reminderHour);
            if ($reminderScheduledAt->isPast()) {
                $reminderScheduledAt = null;
            }
        }

        DB::table('visit_scenario_sends')
            ->where('id', $sendId)
            ->whereNull('coupon_issued_at')
            ->update([
                'coupon_issued_at' => $couponIssuedAt,
                'user_coupon_id' => $userCouponId,
                'reminder_scheduled_at' => $reminderScheduledAt,
                'updated_at' => $updatedAt,
            ]);
    }

    private function calculateScenarioCouponExpiresAt(object $scenario, ?object $bubble, Carbon $issuedAt): ?Carbon
    {
        if ($bubble) {
            if (!empty($bubble->coupon_expires_at)) {
                return Carbon::parse($bubble->coupon_expires_at);
            }
            if (!empty($bubble->coupon_expires_days)) {
                return $issuedAt->copy()->addDays((int) $bubble->coupon_expires_days);
            }
        }

        return $scenario->expires_days
            ? $issuedAt->copy()->addDays((int) $scenario->expires_days)
            : null;
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
        $lineUserId = $req->attributes->get('line_user_id');
        abort_if(!$lineUserId, 401, 'LIFF認証が必要です');

        $user = DB::table('users')
            ->where('line_user_id', $lineUserId)
            ->first();
        abort_if(!$user, 404, 'user not found');

        $now = now();

        // 期限切れチェック
        $coupon = DB::table('user_coupons')
            ->where('id', $userCouponId)
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
            ->where('user_id', $user->id)
            ->where('status', 'issued')
            ->whereNull('used_at')
            ->update([
                'status' => 'used',
                'used_at' => $now,
                'updated_at' => $now,
            ]);

        $storeId = (int) $req->input('store', 0);

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
