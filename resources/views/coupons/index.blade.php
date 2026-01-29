<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>クーポン一覧</title>
  <style>
    body{margin:0;background:#0b0b0f;color:#fff;font-family:system-ui,-apple-system,BlinkMacSystemFont}
    .wrap{max-width:720px;margin:0 auto;padding:18px 14px 28px}
    .top{display:flex;align-items:flex-end;justify-content:space-between;gap:12px}
    .h1{font-weight:900;letter-spacing:.05em;font-size:18px;margin:0}
    .sub{opacity:.75;font-size:12px;margin-top:6px}
    .pill{font-size:12px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:6px 10px;border-radius:999px}
    .grid{display:grid;gap:12px;margin-top:14px}
    .card{
      display:block;
      text-decoration:none;
      color:#fff;
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.12);
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 16px 40px rgba(0,0,0,.35);
    }
    .img{
      width:100%;
      aspect-ratio: 3 / 1; /* 900x300 */
      background:#111;
      display:block;
      object-fit:cover;
    }
    .body{padding:12px 14px}
    .row{display:flex;justify-content:space-between;gap:10px;align-items:center}
    .title{font-weight:900;font-size:16px;letter-spacing:.03em}
    .note{opacity:.8;font-size:12px;margin-top:6px;line-height:1.4}
    .meta{opacity:.7;font-size:12px;margin-top:10px;display:flex;gap:10px;flex-wrap:wrap}
    .badge{font-size:11px;border-radius:999px;padding:4px 8px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08)}
    .badge.unused{border-color:rgba(46,204,113,.35);background:rgba(46,204,113,.12)}
    .badge.used{border-color:rgba(180,180,180,.25);background:rgba(180,180,180,.10);opacity:.9}
    .badge.expired{border-color:rgba(231,76,60,.35);background:rgba(231,76,60,.12)}
    .footer{margin-top:14px;opacity:.6;font-size:11px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h1 class="h1">クーポン一覧</h1>
        <div class="sub">u={{ $lineUserId }} / store={{ $storeId }}</div>
      </div>
      <div class="pill">{{ count($coupons) }}件</div>
    </div>

    <div class="grid">
      @foreach($coupons as $c)
        <a class="card" href="{{ route('coupons.show', ['userCouponId' => $c->user_coupon_id, 'store' => $storeId, 'u' => $lineUserId]) }}">
          <img class="img" src="{{ $c->image_url ?? 'https://placehold.co/900x300/png?text=COUPON' }}" alt="coupon">
          <div class="body">
            <div class="row">
              <div class="title">{{ $c->title }}</div>
              @php
                $status = $c->status;
                $label = $status === 'issued' ? '未使用' : ($status === 'used' ? '使用済み' : '期限切れ');
                $cls = $status === 'issued' ? 'unused' : ($status === 'used' ? 'used' : 'expired');
              @endphp
              <span class="badge {{ $cls }}">{{ $label }}</span>
            </div>
            <div class="note">{{ $c->note ?? '' }}</div>
            <div class="meta">
              <span class="badge">種別：{{ $c->type }}</span>
              <span class="badge">期限：{{ $c->expires_at ? \Carbon\Carbon::parse($c->expires_at)->format('Y/m/d') : '—' }}</span>
              @if($c->used_at)
                <span class="badge">使用：{{ \Carbon\Carbon::parse($c->used_at)->format('Y/m/d H:i') }}</span>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</body>
</html>