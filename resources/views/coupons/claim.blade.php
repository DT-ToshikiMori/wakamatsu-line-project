<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>クーポン取得</title>
  <style>
    body{margin:0;background:#0b0b0f;color:#fff;font-family:system-ui,-apple-system,BlinkMacSystemFont}
    .wrap{max-width:720px;margin:0 auto;padding:18px 14px 28px}
    .h1{font-weight:900;letter-spacing:.05em;font-size:18px;margin:0}

    .card{
      margin-top:14px;
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.12);
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 16px 40px rgba(0,0,0,.35);
    }
    .img{width:100%;aspect-ratio:3/1;background:#111;display:block;object-fit:cover}
    .body{padding:14px 16px}
    .title{font-weight:900;font-size:18px;letter-spacing:.03em}
    .note{opacity:.8;font-size:13px;margin-top:8px;line-height:1.5}
    .meta{opacity:.7;font-size:12px;margin-top:12px;display:flex;gap:10px;flex-wrap:wrap}
    .badge{font-size:11px;border-radius:999px;padding:4px 10px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08)}

    .btn{
      width:100%;margin-top:16px;padding:16px;border-radius:14px;border:0;
      font-weight:900;font-size:15px;cursor:pointer;box-sizing:border-box;line-height:1.2;
      transition:opacity .15s;
    }
    .btn:active{opacity:.7}
    .btn.primary{background:#06c755;color:#fff}
    .btn.secondary{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
    .btn:disabled{opacity:.4;cursor:default}
    a.btn{display:block;text-align:center;text-decoration:none}

    .msg{
      margin-top:16px;padding:14px;border-radius:14px;text-align:center;
      font-weight:700;font-size:14px;line-height:1.5;
    }
    .msg.success{background:rgba(6,199,85,.15);border:1px solid rgba(6,199,85,.3)}
    .msg.error{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.3)}
    .msg.info{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15)}

    .result-overlay{
      display:none;position:fixed;inset:0;z-index:100;
      background:rgba(0,0,0,.7);
      flex-direction:column;align-items:center;justify-content:center;
    }
    .result-overlay.show{display:flex}
    .result-card{
      background:#1a1a20;border-radius:24px;padding:32px 28px;text-align:center;
      max-width:300px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.5);
    }
    .result-icon{font-size:56px;margin-bottom:12px}
    .result-title{font-weight:900;font-size:20px;margin-bottom:8px}
    .result-desc{font-size:13px;opacity:.8;line-height:1.5}
  </style>
</head>
<body>
@include('partials.liff-loading')
<div class="wrap">
  <h1 class="h1">クーポン取得</h1>

  <div class="card">
    <img class="img" src="{{ $tpl->image_url ?? 'https://placehold.co/900x300/png?text=COUPON' }}" alt="coupon">
    <div class="body">
      <div class="title">{{ $tpl->title }}</div>
      @if($tpl->note)
        <div class="note">{{ $tpl->note }}</div>
      @endif
      <div class="meta">
        <span class="badge">種別：{{ $tpl->type }}</span>
        @if($expiresAt)
          <span class="badge">有効期限：{{ $expiresAt->format('Y/m/d H:i') }}</span>
        @else
          <span class="badge">有効期限：無期限</span>
        @endif
        @if($isLottery)
          <span class="badge" style="border-color:rgba(255,215,0,.4);background:rgba(255,215,0,.12)">抽選クーポン</span>
        @endif
      </div>
    </div>
  </div>

  @if($existing)
    {{-- 既に取得済み --}}
    <div class="msg info">このクーポンは取得済みです</div>
    @if($existing->status === 'issued')
      <a class="btn primary" href="/coupons/{{ $existing->id }}?store={{ $storeId }}">クーポンを確認する</a>
    @endif
    <a class="btn secondary" href="/coupons?store={{ $storeId }}">クーポン一覧へ</a>
  @elseif($isExpired)
    {{-- 期限切れ --}}
    <div class="msg error">このクーポンの有効期限が過ぎています</div>
    <a class="btn secondary" href="/coupons?store={{ $storeId }}">クーポン一覧へ</a>
  @else
    {{-- 取得可能 --}}
    <button class="btn primary" id="claimBtn" type="button">
      {{ $isLottery ? '抽選に参加する' : 'クーポンを取得する' }}
    </button>
    <a class="btn secondary" href="/coupons?store={{ $storeId }}" style="margin-top:10px">クーポン一覧へ</a>
  @endif
</div>

{{-- 抽選結果オーバーレイ --}}
<div class="result-overlay" id="resultOverlay">
  <div class="result-card">
    <div class="result-icon" id="resultIcon"></div>
    <div class="result-title" id="resultTitle"></div>
    <div class="result-desc" id="resultDesc"></div>
    <a class="btn primary" id="resultBtn" href="#" style="margin-top:20px">クーポンを確認する</a>
    <a class="btn secondary" href="/coupons?store={{ $storeId }}" style="margin-top:8px">クーポン一覧へ</a>
  </div>
</div>

@include('partials.liff-init')
<script>
  const claimBtn = document.getElementById('claimBtn');
  if (claimBtn) {
    claimBtn.addEventListener('click', async () => {
      claimBtn.disabled = true;
      claimBtn.textContent = '処理中...';

      try {
        const res = await fetch('/coupons/claim', {
          method: 'POST',
          headers: liffHeaders(),
          body: JSON.stringify({
            store: {{ $storeId }},
            bubble_id: {{ $bubbleId }},
            tpl_id: {{ $tplId }},
            sent_at: {{ $sentAt }}
          })
        });

        const data = await res.json();

        if (!data.ok) {
          claimBtn.textContent = data.message || '取得に失敗しました';
          return;
        }

        // 抽選モード
        if (data.is_lottery) {
          showLotteryResult(data);
          return;
        }

        // 通常モード: 成功
        window.location.href = '/coupons/' + data.user_coupon_id + '?store={{ $storeId }}';

      } catch (e) {
        claimBtn.textContent = 'エラーが発生しました';
        console.error(e);
      }
    });
  }

  function showLotteryResult(data) {
    const overlay = document.getElementById('resultOverlay');
    const icon = document.getElementById('resultIcon');
    const title = document.getElementById('resultTitle');
    const desc = document.getElementById('resultDesc');
    const btn = document.getElementById('resultBtn');

    if (data.is_win) {
      icon.textContent = String.fromCodePoint(0x1F389);
      title.textContent = '当選おめでとう！';
      desc.textContent = '「' + data.prize.title + '」を獲得しました！';
      btn.href = '/coupons/' + data.user_coupon_id + '?store={{ $storeId }}';
      btn.style.display = 'block';
    } else {
      icon.textContent = String.fromCodePoint(0x1F622);
      title.textContent = '残念…ハズレ';
      desc.textContent = 'また次回チャレンジしてね！';
      btn.style.display = 'none';
    }

    overlay.classList.add('show');
  }
</script>
</body>
</html>
