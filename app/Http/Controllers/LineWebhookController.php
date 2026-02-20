<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
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

        $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
        $storeId = $user->store_id ?? 0;

        $liffId = config('services.line.liff_id');
        $claimUrl = "https://liff.line.me/{$liffId}/coupons/claim?" . http_build_query([
            'bubble_id' => $bubbleId,
            'tpl_id' => $tplId,
            'sent_at' => $sentAt,
            'store' => $storeId,
        ]);

        $this->lineBotService->replyText(
            $replyToken,
            "下記リンクからクーポンを取得してください。\n{$claimUrl}"
        );
    }
}
