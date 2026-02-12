<?php

namespace App\Http\Controllers;

use App\Services\LiffTokenService;
use Illuminate\Http\Request;

class LiffController extends Controller
{
    public function __construct(
        protected LiffTokenService $liffTokenService,
    ) {}

    /**
     * POST /api/liff/init
     * フロントからIDトークンを受け取り、検証してセッションに保存
     */
    public function init(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $profile = $this->liffTokenService->verify($request->input('id_token'));

        if (!$profile) {
            return response()->json(['error' => 'IDトークンの検証に失敗しました。'], 401);
        }

        // セッションに保存
        $request->session()->put('line_user_id', $profile['sub']);
        $request->session()->put('line_display_name', $profile['name']);
        $request->session()->put('line_picture', $profile['picture']);

        return response()->json([
            'ok' => true,
            'user_id' => $profile['sub'],
            'display_name' => $profile['name'],
            'picture' => $profile['picture'],
        ]);
    }
}
