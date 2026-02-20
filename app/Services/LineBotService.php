<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineBotService
{
    protected string $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.line.bot_channel_access_token', '');
    }

    /**
     * テキストメッセージをプッシュ送信
     */
    public function pushText(string $userId, string $text): bool
    {
        return $this->push($userId, [
            ['type' => 'text', 'text' => $text],
        ]);
    }

    /**
     * Flexメッセージをプッシュ送信
     */
    public function pushFlexMessage(string $userId, string $altText, array $contents): bool
    {
        return $this->push($userId, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $contents,
            ],
        ]);
    }

    /**
     * テキストメッセージをリプライ送信（replyToken使用）
     */
    public function replyText(string $replyToken, string $text): bool
    {
        if (empty($this->accessToken)) {
            Log::warning('LineBotService: LINE_BOT_CHANNEL_ACCESS_TOKEN is not configured');
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post('https://api.line.me/v2/bot/message/reply', [
                    'replyToken' => $replyToken,
                    'messages' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('LineBotService: reply failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('LineBotService: exception during reply', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * メッセージをプッシュ送信（共通）
     */
    protected function push(string $userId, array $messages): bool
    {
        if (empty($this->accessToken)) {
            Log::warning('LineBotService: LINE_BOT_CHANNEL_ACCESS_TOKEN is not configured');
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $userId,
                    'messages' => $messages,
                ]);

            if (!$response->successful()) {
                Log::warning('LineBotService: push failed', [
                    'userId' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('LineBotService: exception during push', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
