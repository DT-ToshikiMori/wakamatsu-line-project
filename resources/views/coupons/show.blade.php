<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>クーポン詳細</title>
  <style>
    body{margin:0;background:#0b0b0f;color:#fff;font-family:system-ui,-apple-system,BlinkMacSystemFont}
    .wrap{max-width:720px;margin:0 auto;padding:18px 14px 28px}
    .top{display:flex;justify-content:space-between;align-items:flex-end;gap:12px}
    .h1{font-weight:900;letter-spacing:.05em;font-size:18px;margin:0}
    .sub{opacity:.75;font-size:12px;margin-top:6px}
    .pill{font-size:12px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:6px 10px;border-radius:999px}
    .stage{margin-top:14px}

    /* listと同じカードUI */
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
    .used{
      margin-top:12px;
      padding:10px 12px;
      border-radius:14px;
      background: rgba(46,204,113,.16);
      border: 1px solid rgba(46,204,113,.35);
      font-weight:800;
    }

    .btn{width:100%;margin-top:12px;padding:14px;border-radius:14px;border:0;background:#fff;color:#0b0b0f;font-weight:900;cursor:pointer;box-sizing:border-box;line-height:1.2}
    .btn.secondary{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
    a.btn{display:block;text-align:center;text-decoration:none}
    .btnRow{margin-top:10px;display:grid;gap:10px}

    /* 回転（Z軸で180°） */
    .rotateWrap{
      transition: transform .55s cubic-bezier(.2,.7,.2,1);
      transform-origin: center center;
      will-change: transform;
    }
    .rotated{transform: rotate(180deg);}

    /* レジ表示用パネル（未使用のみ / 回転後に表示） */
    .verifyBox{
      margin-top:12px;
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(255,255,255,.14);
      background:rgba(255,255,255,.06);
      text-align:center;
    }
    .verifyBox.hidden{display:none}
    .clock{font-weight:900;font-size:18px;letter-spacing:.03em}
    .warn{opacity:.85;font-size:12px;margin-top:8px;line-height:1.5}
    .rotated .verifyBox{border-color:rgba(255,215,0,.25)}
  </style>
</head>
<body>
@include('partials.liff-loading')
<div class="wrap">
  <div class="top">
    <div>
      <h1 class="h1">クーポン詳細</h1>
    </div>
    <div class="pill">{{ $isUsed ? '使用済み' : '未使用' }}</div>
  </div>

  <div class="stage">
    <div id="rotateWrap" class="rotateWrap">
      <div class="card">
        <img class="img" src="{{ \App\Models\CouponTemplate::resolveImageUrl($coupon->image_url) ?? 'https://placehold.co/900x300/png?text=COUPON' }}" alt="coupon">
        <div class="body">
          <div class="row">
            <div class="title">{{ $coupon->title }}</div>
            <span class="badge">{{ $coupon->type }}</span>
          </div>
          <div class="note">{{ $coupon->note ?? '' }}</div>
          <div class="meta">
            <span class="badge">期限：{{ $coupon->expires_at ? \Carbon\Carbon::parse($coupon->expires_at)->format('Y/m/d') : '—' }}</span>
          </div>

          @if(!$isUsed)
            <div class="btnRow">
              <button class="btn" id="useBtn" type="button">使用する（レジで提示）</button>
              <a class="btn secondary" href="/coupons?store={{ (int)$storeId }}">一覧に戻る</a>
            </div>

            <div class="verifyBox hidden" id="verifyBox">
              <div class="clock" id="clock">--:--:--</div>
              <div class="warn">レジで店員に見せてください。<br>店員が「使用を確認する」を押して完了します。</div>
              <div class="btnRow">
                <button class="btn" id="confirmBtn" type="button">使用を確認する（スタッフ）</button>
              </div>
            </div>
          @else
            <div class="used">
              使用済みです
              @if(!empty($usedAt))
                <span style="opacity:.7;font-weight:600;margin-left:6px;">
                  ({{ \Carbon\Carbon::parse($usedAt)->format('Y/m/d H:i') }})
                </span>
              @endif
            </div>
            <div class="btnRow">
              <a class="btn secondary" href="/coupons?store={{ (int)$storeId }}">一覧に戻る</a>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</div>

@include('partials.tab-bar', ['tabStoreId' => (int)$storeId, 'tabActive' => 'coupons'])
@include('partials.liff-init')
<script>
  const wrap = document.getElementById('rotateWrap');
  const useBtn = document.getElementById('useBtn');
  const confirmBtn = document.getElementById('confirmBtn');
  const verifyBox = document.getElementById('verifyBox');
  const clock = document.getElementById('clock');

  function tick(){
    if (!clock) return;
    const d = new Date();
    const pad = n => String(n).padStart(2,'0');
    clock.textContent = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }
  tick();
  if (clock) setInterval(tick, 1000);

  if (useBtn) useBtn.addEventListener('click', () => {
    wrap.classList.add('rotated');
    if (verifyBox) verifyBox.classList.remove('hidden');
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', async () => {
      if (!confirm('このクーポンを使用済みにします。よろしいですか？')) return;

      const res = await fetch(`/coupons/{{ $coupon->user_coupon_id }}/use`, {
        method: 'POST',
        headers: liffHeaders(),
        body: JSON.stringify({
          store: {{ (int)$storeId }}
        })
      });

      const data = await res.json();
      if (!data.ok) {
        alert('使用確定に失敗しました');
        return;
      }

      alert('使用完了');
      window.location.reload();
    });
  }
</script>
</body>
</html>
