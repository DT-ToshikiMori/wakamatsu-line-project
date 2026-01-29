<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

Route::get('/s/{store}/card', function (Request $req, int $store) {
    $lineUserId = (string) $req->query('u', '');
    abort_if($lineUserId === '', 400, 'u is required. e.g. ?u=demo_user_1');

    $storeRow = DB::table('stores')->where('id', $store)->first();
    abort_if(!$storeRow, 404, 'store not found');
    

    // users upsert（店舗×LINEユーザー）
    $user = DB::table('users')->where('store_id', $store)->where('line_user_id', $lineUserId)->first();
    if (!$user) {
        $userId = DB::table('users')->insertGetId([
            'store_id' => $store,
            'line_user_id' => $lineUserId,
            'first_visit_at' => null,
            'last_visit_at' => null,
            'visit_count' => 0,
            'stamp_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = DB::table('users')->where('id', $userId)->first();
    }

    // ルール（暫定：後でDB化）
    // Beginner: 0-2（3個でGoldへ）
    // Gold: 3以上（3個単位でループ表示）
    $goal = 3;
    $stampTotal = (int) ($user->stamp_count ?? 0);
    $isGold = $stampTotal >= 3;

    // 表示用の進捗（Goldは0からループ）
    $progress = $isGold ? (($stampTotal - 3) % 3) : $stampTotal;

    if (!$isGold) {
        $remaining = max(0, 3 - $stampTotal);
        $nextReward = ['at' => 3, 'text' => 'ゴールド昇格クーポン（付与）'];
    } else {
        $remaining = 0;
        $nextReward = ['at' => null, 'text' => 'いつでも100円OFF'];
    }

    $flash = $req->query('ok'); // /checkin後に ?ok=1 で戻す

    return view('stamp.card', [
        'store' => $storeRow,
        'user' => $user,
        'lineUserId' => $lineUserId,
        'goal' => $goal,
        'stampTotal' => $stampTotal,
        'isGold' => $isGold,
        'progress' => $progress,
        'nextReward' => $nextReward,
        'remaining' => $remaining,
        'flash' => $flash,
    ]);
});

Route::post('/s/{store}/checkin', function (Request $req, int $store) {
    $lineUserId = (string) $req->input('u', '');
    abort_if($lineUserId === '', 400, 'u is required.');

    $user = DB::table('users')->where('store_id', $store)->where('line_user_id', $lineUserId)->first();
    abort_if(!$user, 404, 'user not found');

    $requestId = 'card_' . bin2hex(random_bytes(8));
    $visitedAt = now();

    DB::transaction(function () use ($store, $user, $requestId, $visitedAt) {
        // visits
        DB::table('visits')->insert([
            'store_id' => $store,
            'user_id' => $user->id,
            'qr_link_id' => null,
            'visited_at' => $visitedAt,
            'request_id' => $requestId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // users キャッシュ更新
        DB::table('users')->where('id', $user->id)->update([
            'last_visit_at' => $visitedAt,
            'first_visit_at' => DB::raw("COALESCE(first_visit_at, '{$visitedAt->format('Y-m-d H:i:s')}')"),
            'visit_count' => DB::raw('visit_count + 1'),
            'stamp_count' => DB::raw('stamp_count + 1'),
            'updated_at' => now(),
        ]);
    });

    $newUser = DB::table('users')->where('id', $user->id)->first();
    $newStamp = (int) ($newUser->stamp_count ?? 0);

    if ($req->expectsJson()) {
        return response()->json([
            'ok' => true,
            'stamp' => $newStamp,
            'visit_count' => (int) ($newUser->visit_count ?? 0),
            'last_visit_at' => (string) ($newUser->last_visit_at ?? ''),
            'upgraded_to_gold' => ($newStamp === 3),
        ]);
    }

    // 画面へ戻す（デモはGETパラメータでOK表示）
    return redirect("/s/{$store}/card?u=" . urlencode($lineUserId) . "&ok=1");
});

Route::post('/s/{store}/clear', function (Request $req, int $store) {
    $lineUserId = (string) $req->input('u', '');
    abort_if($lineUserId === '', 400, 'u is required.');

    $user = DB::table('users')
        ->where('store_id', $store)
        ->where('line_user_id', $lineUserId)
        ->first();

    abort_if(!$user, 404, 'user not found');

    DB::transaction(function () use ($store, $user) {
        // テスト用：来店ログを削除
        DB::table('visits')
            ->where('store_id', $store)
            ->where('user_id', $user->id)
            ->delete();

        // キャッシュカウンタをリセット
        DB::table('users')->where('id', $user->id)->update([
            'first_visit_at' => null,
            'last_visit_at'  => null,
            'visit_count'    => 0,
            'stamp_count'    => 0,
            'updated_at'     => now(),
        ]);
    });

    if ($req->expectsJson()) {
        return response()->json([
            'ok'           => true,
            'stamp'        => 0,
            'visit_count'  => 0,
            'last_visit_at'=> '',
            'cleared'      => true,
        ]);
    }

    return redirect("/s/{$store}/card?u=" . urlencode($lineUserId));
});

Route::get('/coupons', function (Request $req) {
    $storeId = (int) $req->query('store', 0);
    $lineUserId = (string) $req->query('u', '');

    abort_if($storeId <= 0, 400, 'store is required. e.g. /coupons?store=1&u=demo_user_1');
    abort_if($lineUserId === '', 400, 'u is required. e.g. /coupons?store=1&u=demo_user_1');

    $store = DB::table('stores')->where('id', $storeId)->first();
    abort_if(!$store, 404, 'store not found');

    // ---- いまはUX優先：モックデータ（後で user_coupons に置換） ----
    $coupons = [
        [
            'id' => 101,
            'type' => 'stamp',
            'title' => '昇格クーポン',
            'note' => 'ゴールド昇格おめでとうございます',
            'image_url' => 'https://placehold.co/900x300/png?text=STAMP+COUPON',
            'status' => 'unused', // unused / used / expired
            'expires_at' => date('Y-m-d', strtotime('+14 days')),
        ],
        [
            'id' => 102,
            'type' => 'inactive',
            'title' => 'おかえりなさいクーポン',
            'note' => '次回のご来店でご利用ください',
            'image_url' => 'https://placehold.co/900x300/png?text=COME+BACK',
            'status' => 'unused',
            'expires_at' => date('Y-m-d', strtotime('+7 days')),
        ],
        [
            'id' => 103,
            'type' => 'birthday',
            'title' => 'バースデークーポン',
            'note' => 'お誕生日おめでとうございます！',
            'image_url' => 'https://placehold.co/900x300/png?text=HAPPY+BIRTHDAY',
            'status' => 'used',
            'expires_at' => date('Y-m-d', strtotime('+30 days')),
        ],
    ];

    // 表示の並び：未使用→使用済→期限切れ
    $order = ['unused' => 0, 'used' => 1, 'expired' => 2];
    usort($coupons, fn($a, $b) => ($order[$a['status']] ?? 9) <=> ($order[$b['status']] ?? 9));

    return view('coupons.index', [
        'store' => $store,
        'storeId' => $storeId,
        'lineUserId' => $lineUserId,
        'coupons' => $coupons,
    ]);
});

// クーポン詳細（モック）
Route::get('/coupons/{id}', function (Request $req, int $id) {
    $storeId = (int) $req->query('store', 0);
    $lineUserId = (string) $req->query('u', '');

    abort_if($storeId <= 0, 400, 'store is required. e.g. /coupons/101?store=1&u=demo_user_1');
    abort_if($lineUserId === '', 400, 'u is required. e.g. /coupons/101?store=1&u=demo_user_1');

    // 一覧と同じモック（後でDBへ置換）
    $all = [
        101 => [
            'id' => 101,
            'type' => 'stamp',
            'title' => '昇格クーポン',
            'note' => 'ゴールド昇格おめでとうございます',
            'image_url' => 'https://placehold.co/900x300/png?text=STAMP+COUPON',
            'expires_at' => date('Y-m-d', strtotime('+14 days')),
        ],
        102 => [
            'id' => 102,
            'type' => 'inactive',
            'title' => 'おかえりなさいクーポン',
            'note' => '次回のご来店でご利用ください',
            'image_url' => 'https://placehold.co/900x300/png?text=COME+BACK',
            'expires_at' => date('Y-m-d', strtotime('+7 days')),
        ],
        103 => [
            'id' => 103,
            'type' => 'birthday',
            'title' => 'バースデークーポン',
            'note' => 'お誕生日おめでとうございます！',
            'image_url' => 'https://placehold.co/900x300/png?text=HAPPY+BIRTHDAY',
            'expires_at' => date('Y-m-d', strtotime('+30 days')),
        ],
    ];

    abort_if(!isset($all[$id]), 404, 'coupon not found');

    $coupon = $all[$id];

    // セッションで使用済み管理（後でDBに置換）
    $usedKey = "coupon_used:{$storeId}:{$lineUserId}:{$id}";
    $isUsed = (bool) $req->session()->get($usedKey, false);

    return view('coupons.show', [
        'storeId' => $storeId,
        'lineUserId' => $lineUserId,
        'coupon' => $coupon,
        'isUsed' => $isUsed,
    ]);
});

// スタッフ確認 → 使用確定（モック：セッションに保存）
Route::post('/coupons/{id}/use', function (Request $req, int $id) {
    $storeId = (int) $req->input('store', 0);
    $lineUserId = (string) $req->input('u', '');

    abort_if($storeId <= 0, 400, 'store is required');
    abort_if($lineUserId === '', 400, 'u is required');

    $usedKey = "coupon_used:{$storeId}:{$lineUserId}:{$id}";
    $req->session()->put($usedKey, true);

    if ($req->expectsJson()) {
        return response()->json(['ok' => true, 'used' => true, 'used_at' => now()->toDateTimeString()]);
    }

    return redirect("/coupons/{$id}?store={$storeId}&u=" . urlencode($lineUserId));
});

