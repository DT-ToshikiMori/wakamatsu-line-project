<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StampCardController;
use App\Http\Controllers\CouponController;

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

Route::get('/r/{slug}', function (Request $req, string $slug) {
    $link = DB::table('store_qr_links')
        ->where('slug', $slug)
        ->where('is_active', true)
        ->first();

    abort_if(!$link, 404, 'QR link not found');

    // 店舗の確定（デモは固定 store_id を qr_link に入れておくのが最強）
    $storeId = $link->store_id ?? $req->integer('store_id');
    abort_if(!$storeId, 400, 'store_id is required');

    // デモ用：ユーザー特定は仮（本番はLIFFログインに差し替え）
    $lineUserId = $req->query('u') ?: ('anon_' . substr(sha1($req->ip().'|'.$req->userAgent()), 0, 10));

    // 二重押下防止（URLにrid付けるとデモが安定）
    $requestId = $req->query('rid') ?: ('r_' . bin2hex(random_bytes(8)));
    $visitedAt = now();

    DB::transaction(function () use ($storeId, $lineUserId, $requestId, $visitedAt, $link) {
        $user = DB::table('users')
            ->where('store_id', $storeId)
            ->where('line_user_id', $lineUserId)
            ->first();

        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'store_id'       => $storeId,
                'line_user_id'   => $lineUserId,
                'first_visit_at' => $visitedAt,
                'last_visit_at'  => $visitedAt,
                'visit_count'    => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } else {
            $userId = $user->id;
        }

        $exists = DB::table('visits')
            ->where('store_id', $storeId)
            ->where('request_id', $requestId)
            ->exists();

        if (!$exists) {
            DB::table('visits')->insert([
                'store_id'   => $storeId,
                'user_id'    => $userId,
                'qr_link_id' => $link->id,
                'visited_at' => $visitedAt,
                'request_id' => $requestId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $userId)->update([
                'last_visit_at' => $visitedAt,
                'visit_count'   => DB::raw('visit_count + 1'),
                'updated_at'    => now(),
            ]);
        }
    });

    // redirect_url未設定ならデモ用メッセージ
    return view('redirect', [
        'redirectUrl' => $link->redirect_url,
    ]);
});

Route::get('/s/{store}/card', [StampCardController::class, 'card']);
Route::post('/s/{store}/checkin', [StampCardController::class, 'checkin']);
Route::post('/s/{store}/clear', [StampCardController::class, 'clear']);

Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
Route::get('/coupons/{userCouponId}', [CouponController::class, 'show'])->name('coupons.show');
Route::post('/coupons/{userCouponId}/use', [CouponController::class, 'use'])->name('coupons.use');
