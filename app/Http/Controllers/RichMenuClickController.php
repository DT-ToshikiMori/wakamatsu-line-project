<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RichMenuClickController extends Controller
{
    /**
     * リッチメニューのURLクリックを計測してリダイレクト
     * LINE Messaging APIはURIアクションのWebhookを送らないため、
     * この独自リダイレクトを経由することでクリック数を計測する。
     *
     * URL例: /rm/click/{areaId}?uid={lineUserId}
     * ※ uid はリッチメニュー画像生成時やLIFFから付与（任意）
     */
    public function redirect(Request $request, int $areaId)
    {
        $area = DB::table('rich_menu_areas')->where('id', $areaId)->first();

        if (!$area || $area->action_type !== 'uri') {
            abort(404);
        }

        $destinationUrl = $area->action_data;
        if (empty($destinationUrl)) {
            abort(404);
        }

        // クリックを記録
        try {
            $lineUserId = $request->query('uid');
            $userId = null;

            if ($lineUserId) {
                $user = DB::table('users')->where('line_user_id', $lineUserId)->first();
                $userId = $user?->id;
            }

            DB::table('rich_menu_clicks')->insert([
                'rich_menu_area_id' => $areaId,
                'user_id'           => $userId,
                'line_user_id'      => $lineUserId,
                'clicked_at'        => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('RichMenuClick: record failed', ['area_id' => $areaId, 'error' => $e->getMessage()]);
        }

        return redirect()->away($destinationUrl);
    }
}
