<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiffTokenService
{
    /**
     * LINE API でIDトークンを検証し、ユーザー情報を返す
     *
     * @return array{sub: string, name: string|null, picture: string|null}|null
     */
    public function verify(string $idToken): ?array
    {
        $channelId = config('services.line.login_channel_id');

        if (empty($channelId)) {
            Log::error('LiffTokenService: LINE_LOGIN_CHANNEL_ID is not configured');
            return null;
        }

        try {
            $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => $channelId,
            ]);

            if (!$response->successful()) {
                Log::warning('LiffTokenService: token verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return [
                'sub' => $data['sub'],
                'name' => $data['name'] ?? null,
                'picture' => $data['picture'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('LiffTokenService: exception during verification', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
