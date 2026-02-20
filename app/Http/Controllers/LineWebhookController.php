<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use App\Services\LotteryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public function __construct(
        protected LineBotService $lineBotService,
    ) {}

    public function handle(Request $request)
    {
        // 署名検証
        $signature = $request->header('X-Line-Signature');
        $channelSecret = config('services.line.bot_channel_secret');

        if ($channelSecret && $signature) {
            $hash = base64_encode(
                hash_hmac('sha256', $request->getContent(), $channelSecret, true)
            );

            if (!hash_equals($hash, $signature)) {
                Log::warning('LineWebhook: invalid signature');
                abort(403, 'Invalid signature');
            }
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleEvent(array $event): void
    {
        $type = $event['type'] ?? '';
        $userId = $event['source']['userId'] ?? null;

        switch ($type) {
            case 'follow':
                $this->handleFollow($userId);
                break;

            case 'unfollow':
                $this->handleUnfollow($userId);
                break;

            case 'message':
                $this->handleMessage($event);
                break;

            case 'postback':
                $this->handlePostback($event);
                break;

            default:
                Log::info('LineWebhook: unhandled event type', ['type' => $type]);
                break;
        }
    }

    protected function handleFollow(?string $userId): void
    {
        if (!$userId) return;

        Log::info('LineWebhook: follow', ['userId' => $userId]);

        $this->lineBotService->pushText(
            $userId,
            "WAKAMATSUへようこそ！\nスタンプカードやクーポンをLINEでご利用いただけます。\nQRコードをスキャンしてスタンプを貯めましょう！"
        );
    }

    protected function handleUnfollow(?string $userId): void
    {
        Log::info('LineWebhook: unfollow', ['userId' => $userId]);
    }

    protected function handleMessage(array $event): void
    {
        Log::info('LineWebhook: message received', [
            'userId' => $event['source']['userId'] ?? null,
            'messageType' => $event['message']['type'] ?? null,
        ]);
    }

    protected function handlePostback(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;
        $postbackData = $event['postback']['data'] ?? '';

        if (!$userId || !$replyToken) {
            return;
        }

        parse_str($postbackData, $params);
        $action = $params['action'] ?? '';

        if ($action === 'claim_coupon') {
            $this->handleCouponClaim($userId, $replyToken, $params);
        }
    }

    protected function handleCouponClaim(string $lineUserId, string $replyToken, array $params): void
    {
        $bubbleId = (int) ($params['bubble_id'] ?? 0);
        $tplId = (int) ($params['tpl_id'] ?? 0);
        $sentAt = (int) ($params['sent_at'] ?? 0);

        if (!$bubbleId || !$tplId) {
            $this->lineBotService->replyText($replyToken, 'クーポン情報が不正です。');
            return;
        }

        $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
        if (!$user) {
            $this->lineBotService->replyText($replyToken, 'ユーザー情報が見つかりません。');
            return;
        }

        $bubble = DB::table('message_bubbles')->where('id', $bubbleId)->first();
        if (!$bubble) {
            $this->lineBotService->replyText($replyToken, 'このクーポンは無効です。');
            return;
        }

        $tpl = DB::table('coupon_templates')
            ->where('id', $tplId)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            $this->lineBotService->replyText($replyToken, 'このクーポンは現在ご利用いただけません。');
            return;
        }

        // 重複チェック
        $existing = DB::table('user_coupons')
            ->where('user_id', $user->id)
            ->where('message_bubble_id', $bubbleId)
            ->exists();

        if ($existing) {
            $this->lineBotService->replyText($replyToken, 'このクーポンは既に取得済みです。');
            return;
        }

        // 有効期限計算
        $expiresAt = $this->calculateExpiresAt($bubble, $sentAt);

        // 期限切れチェック
        if ($expiresAt && $expiresAt->isPast()) {
            $this->lineBotService->replyText($replyToken, 'このクーポンの有効期限が過ぎています。');
            return;
        }

        $now = now();

        // 抽選モード
        if (($tpl->mode ?? 'normal') === 'lottery') {
            $result = app(LotteryService::class)->draw($user->store_id, $user->id, $tpl->id, 'manual', $expiresAt);

            if ($result['is_win'] && $result['user_coupon_id']) {
                DB::table('user_coupons')
                    ->where('id', $result['user_coupon_id'])
                    ->update([
                        'message_bubble_id' => $bubbleId,
                        'expires_at' => $expiresAt,
                    ]);

                $prize = $result['prize'];
                $this->lineBotService->replyText(
                    $replyToken,
                    "🎉 当選おめでとうございます！\n「{$prize['title']}」を獲得しました！"
                );
            } else {
                // ハズレでも重複防止のため記録
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

                $this->lineBotService->replyText(
                    $replyToken,
                    "残念...ハズレでした。また次回チャレンジしてね！"
                );
            }
            return;
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
                $this->lineBotService->replyText($replyToken, 'このクーポンは既に取得済みです。');
                return;
            }
            throw $e;
        }

        DB::table('coupon_events')->insert([
            'user_coupon_id' => $userCouponId,
            'event' => 'issued',
            'actor' => 'user',
            'created_at' => $now,
        ]);

        $expiresMsg = $expiresAt
            ? "\n有効期限: " . $expiresAt->format('Y/m/d H:i')
            : '';

        $this->lineBotService->replyText(
            $replyToken,
            "クーポン「{$tpl->title}」を取得しました！{$expiresMsg}\nクーポン一覧から確認できます。"
        );
    }

    protected function calculateExpiresAt(object $bubble, int $sentAt): ?Carbon
    {
        // 自由配信: 絶対日時
        if (!empty($bubble->coupon_expires_at)) {
            return Carbon::parse($bubble->coupon_expires_at);
        }

        // 離脱防止シナリオ: 配信日からX日後
        if (!empty($bubble->coupon_expires_days) && $sentAt > 0) {
            return Carbon::createFromTimestamp($sentAt)->addDays((int) $bubble->coupon_expires_days);
        }

        return null;
    }
}
