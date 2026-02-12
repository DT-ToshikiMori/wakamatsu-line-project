<?php

namespace App\Http\Middleware;

use App\Services\LiffTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLiffToken
{
    public function __construct(
        protected LiffTokenService $liffTokenService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // セッションに既に検証済み情報がある場合はスキップ
        if ($request->session()->has('line_user_id')) {
            $request->attributes->set('line_user_id', $request->session()->get('line_user_id'));
            $request->attributes->set('line_display_name', $request->session()->get('line_display_name'));
            $request->attributes->set('line_picture', $request->session()->get('line_picture'));
            return $next($request);
        }

        // Authorization: Bearer ヘッダーからIDトークンを取得
        $idToken = $request->bearerToken();

        if (!$idToken) {
            // POST/JSONリクエストは401で拒否
            if ($request->expectsJson() || $request->isMethod('POST')) {
                return response()->json(['error' => 'LIFF認証が必要です。'], 401);
            }

            // ブラウザGETリクエスト → LIFF認証ページを表示
            // LIFF SDKが初期化→認証→セッション確立→リロードの流れを処理
            return response()->view('liff-auth');
        }

        $profile = $this->liffTokenService->verify($idToken);

        if (!$profile) {
            if ($request->expectsJson() || $request->isMethod('POST')) {
                return response()->json(['error' => 'IDトークンの検証に失敗しました。'], 401);
            }
            return response()->view('liff-auth');
        }

        // セッションに保存
        $request->session()->put('line_user_id', $profile['sub']);
        $request->session()->put('line_display_name', $profile['name']);
        $request->session()->put('line_picture', $profile['picture']);

        // リクエスト属性にセット
        $request->attributes->set('line_user_id', $profile['sub']);
        $request->attributes->set('line_display_name', $profile['name']);
        $request->attributes->set('line_picture', $profile['picture']);

        return $next($request);
    }
}
