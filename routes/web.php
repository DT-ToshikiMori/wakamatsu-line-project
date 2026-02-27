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

// QRコード画像ダウンロード（Filament管理画面から呼ばれる）
Route::get('/admin/qr-download/{storeQrLink}', function (\App\Models\StoreQrLink $storeQrLink) {
    $liffId = config('services.line.liff_id');
    abort_if(!$liffId, 500, 'LIFF_ID is not configured');

    $path = "/s/{$storeQrLink->store_id}/card?qr_link_id={$storeQrLink->id}";
    $url = "https://liff.line.me/{$liffId}?path=" . urlencode($path);

    $options = new \chillerlan\QRCode\QROptions([
        'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
        'scale' => 10,
        'outputBase64' => false,
    ]);
    $image = (new \chillerlan\QRCode\QRCode($options))->render($url);

    return response($image)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'attachment; filename="qr-' . $storeQrLink->slug . '.png"');
})->middleware(['web', 'auth'])->name('store-qr-link.download-qr');

// 管理画面（認証はFilamentが担当）
Route::get('/admin/users', function (Request $req) {
    $storeId = $req->integer('store_id');
    $daysGte = $req->integer('days_gte');

    $q = DB::table('users')
        ->leftJoin('stores', 'stores.id', '=', 'users.store_id')
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
        // QRリンクIDも渡して stamp_count を参照できるようにする
        $path = urlencode("/s/{$storeId}/card?qr_link_id={$link->id}");
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
    Route::get('/s/{store}/register', [StampCardController::class, 'registerForm']);
    Route::post('/s/{store}/register', [StampCardController::class, 'registerSave']);
    Route::post('/s/{store}/checkin', [StampCardController::class, 'checkin']);
    Route::post('/s/{store}/clear', [StampCardController::class, 'clear']);

    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/claim', [CouponController::class, 'claimPage'])->name('coupons.claim');
    Route::post('/coupons/claim', [CouponController::class, 'claim'])->name('coupons.claim.store');
    Route::get('/coupons/{userCouponId}', [CouponController::class, 'show'])->name('coupons.show');
    Route::post('/coupons/{userCouponId}/use', [CouponController::class, 'use'])->name('coupons.use');
});
