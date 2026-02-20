<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>クーポン一覧</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{margin:0;background:#0b0b0f;color:#fff;font-family:system-ui,-apple-system,BlinkMacSystemFont}
    .wrap{max-width:720px;margin:0 auto;padding:18px 14px 28px}
    .top{display:flex;align-items:flex-end;justify-content:space-between;gap:12px}
    .h1{font-weight:900;letter-spacing:.05em;font-size:18px;margin:0}
    .sub{opacity:.75;font-size:12px;margin-top:6px}
    .pill{font-size:12px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:6px 10px;border-radius:999px}

    /* Coupon filter tabs */
    .filterTabs{
      display:flex;
      gap:0;
      margin-top:14px;
      border-radius:12px;
      overflow:hidden;
      border:1px solid rgba(255,255,255,.15);
    }
    .filterTab{
      flex:1;
      padding:10px 8px;
      text-align:center;
      font-size:13px;
      font-weight:700;
      letter-spacing:.02em;
      cursor:pointer;
      background:transparent;
      color:rgba(255,255,255,.55);
      border:0;
      transition:background .15s, color .15s;
    }
    .filterTab.active{
      background:rgba(255,255,255,.12);
      color:#fff;
    }

    .grid{display:grid;gap:12px;margin-top:12px}
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
    .card.hidden{display:none}
    .img{
      width:100%;
      aspect-ratio: 3 / 1;
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
    .empty{margin-top:24px;text-align:center;opacity:.5;font-size:13px}
    .footer{margin-top:14px;opacity:.6;font-size:11px}
  </style>
</head>
<body>
  @include('partials.liff-loading')
  <div class="wrap">
    <div class="top">
      <div>
        <h1 class="h1">クーポン一覧</h1>
      </div>
      <div class="pill">{{ count($coupons) }}件</div>
    </div>

    <div class="filterTabs">
      <button class="filterTab active" data-filter="available" type="button">利用可能</button>
      <button class="filterTab" data-filter="used" type="button">使用済・期限切れ</button>
    </div>

    <div class="grid" id="couponGrid">
      @foreach($coupons as $c)
        @php
          $status = $c->status;
          $isAvailable = ($status === 'issued');
          $label = $status === 'issued' ? '未使用' : ($status === 'used' ? '使用済み' : '期限切れ');
          $cls = $status === 'issued' ? 'unused' : ($status === 'used' ? 'used' : 'expired');
        @endphp
        <a class="card" href="/coupons/{{ $c->user_coupon_id }}?store={{ $storeId }}" data-status="{{ $isAvailable ? 'available' : 'used' }}">
          <img class="img" src="{{ \App\Models\CouponTemplate::resolveImageUrl($c->image_url) ?? 'https://placehold.co/900x300/png?text=COUPON' }}" alt="coupon">
          <div class="body">
            <div class="row">
              <div class="title">{{ $c->title }}</div>
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
    <div class="empty hidden" id="emptyMsg">該当するクーポンはありません</div>
  </div>
  @include('partials.tab-bar', ['tabStoreId' => $storeId, 'tabActive' => 'coupons'])
  @include('partials.liff-init')
  <script>
    const tabs = document.querySelectorAll('.filterTab');
    const cards = document.querySelectorAll('#couponGrid .card');
    const emptyMsg = document.getElementById('emptyMsg');

    function applyFilter(filter) {
      let visible = 0;
      cards.forEach(card => {
        const match = card.dataset.status === filter;
        card.classList.toggle('hidden', !match);
        if (match) visible++;
      });
      emptyMsg.classList.toggle('hidden', visible > 0);
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        applyFilter(tab.dataset.filter);
      });
    });

    // Initial filter
    applyFilter('available');
  </script>
</body>
</html>
