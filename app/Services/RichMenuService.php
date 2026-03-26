<?php

namespace App\Services;

use App\Models\RichMenu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RichMenuService
{
    protected string $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.line.bot_channel_access_token', '');
    }

    /**
     * LINE APIにリッチメニューを作成
     */
    public function createOnLine(RichMenu $richMenu): ?string
    {
        $response = Http::withToken($this->accessToken)
            ->post('https://api.line.me/v2/bot/richmenu', $richMenu->toLineApiPayload());

        if (!$response->successful()) {
            Log::warning('RichMenuService: create failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json('richMenuId');
    }

    /**
     * リッチメニュー画像をアップロード
     */
    public function uploadImage(string $lineRichMenuId, string $imagePath): bool
    {
        $fullPath = Storage::disk('public')->path($imagePath);

        if (!file_exists($fullPath)) {
            Log::warning('RichMenuService: image not found', ['path' => $fullPath]);
            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->withHeaders(['Content-Type' => 'image/png'])
            ->withBody(file_get_contents($fullPath), 'image/png')
            ->post("https://api-data.line.me/v2/bot/richmenu/{$lineRichMenuId}/content");

        if (!$response->successful()) {
            Log::warning('RichMenuService: uploadImage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * デフォルトリッチメニューに設定
     */
    public function setDefault(string $lineRichMenuId): bool
    {
        $response = Http::withToken($this->accessToken)
            ->post("https://api.line.me/v2/bot/user/all/richmenu/{$lineRichMenuId}");

        if (!$response->successful()) {
            Log::warning('RichMenuService: setDefault failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * デフォルトリッチメニューを解除
     */
    public function deleteDefault(): bool
    {
        $response = Http::withToken($this->accessToken)
            ->delete('https://api.line.me/v2/bot/user/all/richmenu');

        if (!$response->successful()) {
            Log::warning('RichMenuService: deleteDefault failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * LINE APIからリッチメニューを削除
     */
    public function deleteFromLine(string $lineRichMenuId): bool
    {
        $response = Http::withToken($this->accessToken)
            ->delete("https://api.line.me/v2/bot/richmenu/{$lineRichMenuId}");

        if (!$response->successful()) {
            Log::warning('RichMenuService: deleteFromLine failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * リッチメニューをLINEに同期（作成 → 画像アップロード → ステータス更新）
     */
    public function syncToLine(RichMenu $richMenu): bool
    {
        // 既存のLINEリッチメニューがあれば削除
        if ($richMenu->line_rich_menu_id) {
            $this->deleteFromLine($richMenu->line_rich_menu_id);
        }

        // 1. リッチメニュー作成
        $lineId = $this->createOnLine($richMenu);
        if (!$lineId) {
            return false;
        }

        // 2. 画像アップロード
        if ($richMenu->image_path) {
            if (!$this->uploadImage($lineId, $richMenu->image_path)) {
                $this->deleteFromLine($lineId);
                return false;
            }
        }

        // 3. ステータス更新
        $richMenu->update([
            'line_rich_menu_id' => $lineId,
            'status' => 'synced',
            'synced_at' => now(),
        ]);

        return true;
    }
}
