<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StampCardController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\LiffController;
use App\Http\Controllers\LineWebhookController;

// ルート: LINEログイン後のOAuthコールバック先（liff.stateでパスに飛ぶ）
Route::get('/', function () {
    return response()->view('liff-auth');
});

// LIFF初期化API（認証前に呼ばれるのでミドルウェア不要）
Route::post('/api/liff/init', [LiffController::class, 'init']);

// LINE Webhook（CSRF除外 → VerifyCsrfToken で除外設定が必要）
Route::post('/webhook/line', [LineWebhookController::class, 'handle']);

// 管理画面（認証はFilamentが担当）
Route::get('/admin/users', function (Request $req) {
    $storeId = $req->integer('store_id');
    $daysGte = $req->integer('days_gte');

    $q = DB::table('users')
        ->join('stores', 'stores.id', '=', 'users.store_id')
        ->select('users.*', 'stores.name as store_name')
        ->orderByDesc('users.last_visit_at');

    if ($storeId) $q->where('users.store_id', $storeId);
    if ($daysGte) $q->where('users.last_visit_at', '<=', now()->subDays($daysGte));

    $users = $q->limit(200)->get();

    return view('admin.users', [
        'users' => $users,
        'storeId' => $storeId,
        'daysGte' => $daysGte
    ]);
});

// QRリダイレクト → LIFF URLへ
Route::get('/r/{slug}', function (Request $req, string $slug) {
    $link = DB::table('store_qr_links')
        ->where('slug', $slug)
        ->where('is_active', true)
        ->first();

    abort_if(!$link, 404, 'QR link not found');

    $storeId = $link->store_id ?? $req->integer('store_id');
    abort_if(!$storeId, 400, 'store_id is required');

    $liffId = config('services.line.liff_id');

    if ($liffId) {
        // LIFF URLへリダイレクト（LINEアプリ内で開く）
        $path = urlencode("/s/{$storeId}/card");
        return redirect("https://liff.line.me/{$liffId}?path={$path}");
    }

    // LIFF未設定時はリダイレクトURLへフォールバック
    return view('redirect', [
        'redirectUrl' => $link->redirect_url,
    ]);
});

// LIFF認証が必要なルート
Route::middleware('liff')->group(function () {
    Route::get('/s/{store}/card', [StampCardController::class, 'card']);
    Route::post('/s/{store}/checkin', [StampCardController::class, 'checkin']);
    Route::post('/s/{store}/clear', [StampCardController::class, 'clear']);

    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/{userCouponId}', [CouponController::class, 'show'])->name('coupons.show');
    Route::post('/coupons/{userCouponId}/use', [CouponController::class, 'use'])->name('coupons.use');
});
