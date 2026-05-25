<?php

namespace App\Services;

use App\Models\RichMenu;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RichMenuService
{
    protected string $accessToken;

    public function __construct()
    {
        $this->accessToken = (string) config('services.line.bot_channel_access_token', '');
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

        $prepared = $this->prepareImageForLineUpload($fullPath);
        if (!$prepared) {
            return false;
        }

        [$uploadPath, $contentType, $temporary] = $prepared;

        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Content-Type' => $contentType])
                ->withBody(file_get_contents($uploadPath), $contentType)
                ->post("https://api-data.line.me/v2/bot/richmenu/{$lineRichMenuId}/content");
        } finally {
            if ($temporary && file_exists($uploadPath)) {
                @unlink($uploadPath);
            }
        }

        if (!$response->successful()) {
            Log::warning('RichMenuService: uploadImage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'bytes' => file_exists($fullPath) ? filesize($fullPath) : null,
                'content_type' => $contentType,
            ]);
            return false;
        }

        return true;
    }

    /**
     * LINEリッチメニュー画像は1MB以下が必要。
     * 1MBを超えるPNG/JPEGは、寸法を維持したままJPEGへ圧縮してアップロードする。
     *
     * @return array{0:string,1:string,2:bool}|null [path, content-type, is-temporary]
     */
    private function prepareImageForLineUpload(string $fullPath): ?array
    {
        $maxBytes = 1024 * 1024;
        $imageInfo = @getimagesize($fullPath);
        $mime = $imageInfo['mime'] ?? 'image/png';

        if (filesize($fullPath) <= $maxBytes) {
            return [$fullPath, in_array($mime, ['image/png', 'image/jpeg'], true) ? $mime : 'image/png', false];
        }

        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            Log::warning('RichMenuService: image too large and GD jpeg encoder unavailable', [
                'path' => $fullPath,
                'bytes' => filesize($fullPath),
            ]);
            return null;
        }

        $source = @imagecreatefromstring(file_get_contents($fullPath));
        if (!$source) {
            Log::warning('RichMenuService: failed to decode image for compression', [
                'path' => $fullPath,
                'bytes' => filesize($fullPath),
            ]);
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'line-rich-menu-');
        if (!$tmpPath) {
            imagedestroy($source);
            return null;
        }

        foreach ([90, 85, 80, 75, 70, 65, 60] as $quality) {
            imagejpeg($source, $tmpPath, $quality);
            clearstatcache(true, $tmpPath);

            if (filesize($tmpPath) <= $maxBytes) {
                imagedestroy($source);
                return [$tmpPath, 'image/jpeg', true];
            }
        }

        imagedestroy($source);
        @unlink($tmpPath);

        Log::warning('RichMenuService: image remains too large after compression', [
            'path' => $fullPath,
            'bytes' => filesize($fullPath),
        ]);

        return null;
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
     * ユーザー個別のリッチメニューを紐付ける
     */
    public function linkToUser(string $lineUserId, string $lineRichMenuId): bool
    {
        $lineUserId = trim($lineUserId);
        $lineRichMenuId = trim($lineRichMenuId);

        if ($lineUserId === '' || $lineRichMenuId === '') {
            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->post('https://api.line.me/v2/bot/user/' . rawurlencode($lineUserId) . '/richmenu/' . rawurlencode($lineRichMenuId));

        if (!$response->successful()) {
            Log::warning('RichMenuService: linkToUser failed', [
                'line_user_id' => $lineUserId,
                'line_rich_menu_id' => $lineRichMenuId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * ユーザー個別のリッチメニューを解除し、デフォルト/共通メニューを適用させる
     */
    public function unlinkFromUser(string $lineUserId): bool
    {
        $lineUserId = trim($lineUserId);

        if ($lineUserId === '') {
            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->delete('https://api.line.me/v2/bot/user/' . rawurlencode($lineUserId) . '/richmenu');

        if (!$response->successful()) {
            // すでに個別メニューが無い場合も、期待する最終状態は「デフォルト適用」なので成功扱い。
            if ($response->status() === 404) {
                return true;
            }

            Log::warning('RichMenuService: unlinkFromUser failed', [
                'line_user_id' => $lineUserId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * ユーザーの現在ランクに対応する有効な個別リッチメニューを取得
     *
     * @param  object|User  $user
     */
    public function findTargetForUser(object $user): ?RichMenu
    {
        $currentCardId = $user->current_card_id ?? null;
        if (!$currentCardId) {
            return null;
        }

        return RichMenu::query()
            ->where('target_stamp_card_definition_id', $currentCardId)
            ->whereNotNull('line_rich_menu_id')
            ->whereIn('status', ['active', 'synced'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();
    }

    /**
     * ユーザーの現在ランクに応じて個別リッチメニューを同期する
     *
     * @param  object|User  $user
     */
    public function syncForUser(object $user): bool
    {
        $lineUserId = trim((string) ($user->line_user_id ?? ''));
        if ($lineUserId === '') {
            Log::warning('RichMenuService: syncForUser skipped missing line_user_id', [
                'user_id' => $user->id ?? null,
            ]);
            return false;
        }

        $richMenu = $this->findTargetForUser($user);
        if ($richMenu) {
            return $this->linkToUser($lineUserId, $richMenu->line_rich_menu_id);
        }

        return $this->unlinkFromUser($lineUserId);
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
     *
     * LINEのリッチメニューは更新APIが無いため、同期時は新規作成する。
     * ただし既存のLINEリッチメニューを先に削除すると、表示中/個別リンク中の
     * メニューが消えてしまうため、自動削除はしない。
     */
    public function syncToLine(RichMenu $richMenu): bool
    {
        $oldLineRichMenuId = $richMenu->line_rich_menu_id;
        $wasDefault = (bool) $richMenu->is_default || $richMenu->status === 'active';

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

        // 3. デフォルトだったメニューは新しいLINE IDをデフォルトに付け替える
        if ($wasDefault && !$this->setDefault($lineId)) {
            $this->deleteFromLine($lineId);
            return false;
        }

        // 4. ステータス更新
        $richMenu->update([
            'line_rich_menu_id' => $lineId,
            'status' => $wasDefault ? 'active' : 'synced',
            'synced_at' => now(),
        ]);

        if ($oldLineRichMenuId) {
            Log::info('RichMenuService: old LINE rich menu retained after sync', [
                'rich_menu_id' => $richMenu->id,
                'old_line_rich_menu_id' => $oldLineRichMenuId,
                'new_line_rich_menu_id' => $lineId,
            ]);
        }

        return true;
    }
}
