<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use Illuminate\Http\Request;
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
}
