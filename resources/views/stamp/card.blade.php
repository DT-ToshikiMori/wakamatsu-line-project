<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>{{ $store->name }} | スタンプカード</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{
      margin:0;
      background: var(--theme-bg, #0b0b0f);
      color:#fff;
      font-family:system-ui,-apple-system,BlinkMacSystemFont;
    }
    /* Background logo between background and card */
    .bgLogoWrap{
      position:fixed;
      inset:0;
      pointer-events:none;
      z-index:0;
    }
    .bgLogo{
      position:absolute;
      top:0%;
      left:45%;
      width:640px;
      max-width:70vw;
      opacity: var(--logo-opacity, .10);
      transform:none;
      filter: blur(0.3px);
    }
    .wrap{
      max-width:520px;
      margin:0 auto;
      padding:18px 14px 28px;
      position:relative;
      z-index:1;
    }
    .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:16px}
    .top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .store{font-weight:800;letter-spacing:.06em}
    .sub{opacity:.8;font-size:12px;margin-top:4px}
    .userProfile{
      display:flex;
      align-items:center;
      gap:10px;
      margin-top:8px;
    }
    .userAvatar{
      width:36px;
      height:36px;
      border-radius:999px;
      overflow:hidden;
      background:rgba(255,255,255,.12);
      border:1px solid rgba(255,255,255,.25);
      flex-shrink:0;
    }
    .userAvatar img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .userName{
      font-size:13px;
      font-weight:700;
      letter-spacing:.02em;
      opacity:.9;
    }
    .badge{
      font-size:12px;
      background:rgba(255,255,255,.14);
      padding:6px 10px;
      border-radius:999px
    }
    .big{font-size:44px;font-weight:900;margin:10px 0 2px}
    .big2{font-size:28px;font-weight:900;margin:6px 0 2px;opacity:.95}
    .muted{opacity:.75;font-size:12px}
    /* Beginner stamp (real stamp-like) */
    .stamps{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      justify-content:center;
      max-width:320px; /* 5個/段の目安 (サイズ+gapに合わせる) */
      margin:14px auto 0;
    }
    .stamp{
      width:52px;height:52px;border-radius:999px;
      display:flex;align-items:center;justify-content:center;
      font-weight:900;font-size:16px;letter-spacing:.02em;
      border:2px solid rgba(255,255,255,.28);
      background: transparent;
      color: rgba(255,255,255,.45);
      position:relative;overflow:hidden;
      transform: rotate(-2deg);
    }
    .stamp:nth-child(2){transform: rotate(2deg)}
    .stamp:nth-child(3){transform: rotate(-1deg)}

    /* ink texture */
    .stamp::before{
      content:'';
      position:absolute;inset:-10px;
      background:
        radial-gradient(circle at 30% 25%, rgba(255,255,255,.10), transparent 55%),
        radial-gradient(circle at 70% 65%, rgba(255,255,255,.08), transparent 58%),
        radial-gradient(circle at 50% 50%, rgba(0,0,0,.18), transparent 62%);
      opacity:.65;
      pointer-events:none;
    }

    /* rough edge */
    .stamp::after{
      content:'';
      position:absolute;inset:6px;
      border-radius:999px;
      border:1px dashed rgba(255,255,255,.12);
      pointer-events:none;
    }

    @keyframes stampPress{
      0%{transform: translateY(6px) scale(.88) rotate(var(--rot)); opacity:0; filter: blur(2px)}
      55%{transform: translateY(0) scale(1.06) rotate(var(--rot)); opacity:1; filter: blur(0)}
      100%{transform: translateY(0) scale(1) rotate(var(--rot)); opacity:1}
    }

    .stamp.on{
      border-color: rgba(255,255,255,.85);
      background:
        radial-gradient(circle at 35% 30%, rgba(255,255,255,.22), transparent 55%),
        radial-gradient(circle at 60% 70%, rgba(255,255,255,.18), transparent 58%),
        rgba(255,255,255,.10);
      color:#fff;
      box-shadow:
        0 10px 30px rgba(0,0,0,.35),
        inset 0 0 0 2px rgba(255,255,255,.06);
    }
    .stamp.on .stampText{
      animation: stampPress .38s ease-out;
    }

    /* keep text above textures */
    .stamp .stampText{
      position:relative;
      z-index:2;
      text-transform:uppercase;
      font-size:11px;
      letter-spacing:.06em;
    }
    .goldMsg{margin-top:10px}
    .goldMsgTitle{font-size:22px;font-weight:900;letter-spacing:.04em}
    .goldMsgSub{opacity:.9;margin-top:4px;font-size:14px}
    .goldMsgThanks{opacity:.85;margin-top:10px;font-size:13px;line-height:1.5}
    body.gold .goldMsgTitle{
      background: linear-gradient(135deg, #ffe082, #ffb300);
      -webkit-background-clip:text;
      color:transparent;
    }
    body.gold .goldMsgSub b{color:#fff}
    /* Gold rank-up modal coupon card */
    .couponMini{
      margin-top:12px;
      border-radius:18px;
      overflow:hidden;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      box-shadow: 0 16px 40px rgba(0,0,0,.35);
    }
    .couponMiniImg{
      width:100%;
      aspect-ratio: 3 / 1;
      object-fit:cover;
      display:block;
      background:#111;
    }
    .couponMiniBody{padding:12px 14px}
    .couponMiniTitle{font-weight:900;font-size:16px;letter-spacing:.03em}
    .couponMiniNote{opacity:.8;font-size:12px;margin-top:6px;line-height:1.4}
    .modalActions{display:grid;gap:10px;margin-top:14px}
    .mbtn.secondary{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
    .section{margin-top:16px}
    .row{display:flex;justify-content:space-between;gap:10px;align-items:center}
    .btn{
      width:100%;
      margin-top:14px;
      padding:14px 14px;
      border-radius:14px;
      border:0;
      background:#fff;
      color:#0b0b0f;
      font-weight:800;
      cursor:pointer
    }
    /* Theme accent color overrides */
    .btn{
      background: var(--theme-accent, #ffffff);
      color:#0b0b0f;
    }
    .badge{
      background: color-mix(in srgb, var(--theme-accent, #ffffff) 25%, rgba(255,255,255,.14));
    }
    body.gold .btn{
      background: linear-gradient(135deg, var(--theme-accent, #ffd54a), var(--theme-accent, #ffb300));
    }
    .btn.secondary{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
    body.gold .btn.secondary{border:1px solid rgba(255,215,0,.28); color:#fff}
    .ok{margin-top:12px;padding:10px 12px;border-radius:12px;background:rgba(46,204,113,.18);border:1px solid rgba(46,204,113,.35)}
    a{color:#fff}
    input[type=hidden]{display:none}

    .navLink{
      margin-top:12px;
      text-align:center;
    }
    .navLink a{
      display:inline-block;
      font-size:13px;
      font-weight:700;
      letter-spacing:.04em;
      padding:10px 14px;
      border-radius:999px;
      text-decoration:none;
      color:#fff;
      border:1px solid rgba(255,255,255,.25);
      background:rgba(255,255,255,.06);
    }
    body.gold .navLink a{
      border-color: rgba(255,215,0,.45);
      background: rgba(255,215,0,.10);
    }

    /* Gold theme (keep DB-driven base background) */
    body.gold{
      background: var(--theme-bg, #07070a);
    }
    body.gold::before{
      content:'';
      position:fixed;
      inset:0;
      pointer-events:none;
      z-index:-1;
      background:
        radial-gradient(1200px 600px at 20% 10%, rgba(255,215,0,.22), transparent 60%),
        radial-gradient(900px 500px at 80% 30%, rgba(255,170,0,.16), transparent 55%);
      mix-blend-mode: screen;
      opacity: .65;
    }
    body.gold .card{
      border: 1px solid rgba(255,215,0,.25);
      box-shadow: 0 20px 60px rgba(255,190,0,.10);
      position: relative;
      overflow: hidden;
    }
    body.gold .card::before{
      content:'';
      position:absolute;
      inset:-120px;
      background: conic-gradient(from 180deg, rgba(255,215,0,.00), rgba(255,215,0,.18), rgba(255,255,255,.08), rgba(255,170,0,.14), rgba(255,215,0,.00));
      transform: rotate(8deg);
      animation: goldSpin 6s linear infinite;
      pointer-events:none;
    }
    @keyframes goldSpin{
      0%{transform: rotate(8deg) scale(1.05);}
      100%{transform: rotate(368deg) scale(1.05);}
    }
    body.gold .card > *{
      position: relative;
      z-index: 1;
    }
    body.gold .badge{
      background: color-mix(in srgb, var(--theme-accent, #ffd54a) 22%, rgba(255,255,255,.10));
      border: 1px solid color-mix(in srgb, var(--theme-accent, #ffd54a) 35%, rgba(255,255,255,.12));
    }


    .modal{
      position:fixed; inset:0;
      display:none; align-items:center; justify-content:center;
      background: rgba(0,0,0,.55);
      padding:18px;
      z-index: 60;
    }
    .modal.on{display:flex}
    .modal .m{
      width:min(520px, 92vw);
      border-radius:18px;
      padding:16px;
      background: rgba(20,20,26,.95);
      border:1px solid rgba(255,215,0,.25);
      box-shadow: 0 20px 60px rgba(0,0,0,.45);
    }
    .modal .title{
      font-weight:900; letter-spacing:.06em;
      background: linear-gradient(135deg, #ffe082, #ffb300);
      -webkit-background-clip:text;
      color:transparent;
      font-size:20px;
    }
    .modal .p{opacity:.85; margin-top:8px; font-size:13px}
    .modal .mbtn{
      margin-top:14px;
      width:100%;
      padding:12px 14px;
      border-radius:14px;
      border:0;
      background: linear-gradient(135deg, #ffd54a, #ffb300);
      color:#1a1400;
      font-weight:900;
      cursor:pointer;
      font-size:14px;
    }
    .modal a.mbtn{
      display:block;
      text-align:center;
      text-decoration:none;
      box-sizing:border-box;
    }
  </style>
</head>
<body
  class="{{ $isGold ? 'gold' : '' }}"
  style="
    --theme-bg: {{ $currentCard->theme_bg ?? '#0b0b0f' }};
    --theme-accent: {{ $currentCard->theme_accent ?? '#ffffff' }};
    --logo-opacity: {{ $currentCard->theme_logo_opacity ?? 0.10 }};
  "
>
@include('partials.liff-loading')
<div class="bgLogoWrap">
  <img src="/logo.png" alt="logo" class="bgLogo">
</div>
<div class="wrap">
  <div class="card">
    <div class="top">
      <div>
        <div class="store">{{ $store->name }}</div>

        <div class="userProfile">
          <div class="userAvatar">
            <img src="{{ $user->profile_image_url ?? 'https://placehold.co/72x72/png?text=USER' }}" alt="user">
          </div>
          <div class="userName">
            {{ $user->display_name ?? 'お客様' }} 様
          </div>
        </div>
      </div>
      <div class="badge"><span id="stampNow">{{ $progress }}</span>/{{ $goal }}</div>
    </div>


    @if($isGold)
      <div class="big" id="stampBig">{{ $currentCard->display_name ?? 'GOLD' }}</div>
      <div class="big2" id="visitBig">来店回数：{{ $user->visit_count ?? 0 }}回</div>
      <div id="nextText" class="goldMsg">
        <div class="goldMsgTitle">毎回100円OFF</div>
        <div class="goldMsgSub"><b>この画面を提示＋チェックイン</b>で割引が適用されます</div>
      </div>
    @else
      <div class="big" id="stampBig">{{ $currentCard->display_name ?? 'BEGINNER' }}</div>
      <div class="big2" id="visitBig">来店回数：{{ $user->visit_count ?? 0 }}回</div>
      <div class="muted" id="nextText">ゴールドまで：あと {{ $remaining }} 回</div>
    @endif

    <div class="stamps" id="stampsWrap" aria-label="rank-stamps">
      @for($i=1;$i<=$goal;$i++)
        @php
          $rot = $i === 1 ? '-2deg' : ($i === 2 ? '2deg' : '-1deg');
        @endphp
        <div class="stamp {{ $i <= $progress ? 'on' : '' }}" data-i="{{ $i }}" style="--rot: {{ $rot }};">
          <div class="stampText">STAMP</div>
        </div>
      @endfor
    </div>

    <div class="section">
      <div class="row">
        <div class="muted">最終来店</div>
        <div class="muted" id="lastVisit">{{ $user->last_visit_at ?? '—' }}</div>
      </div>
    </div>

    <button class="btn" id="checkinBtn" type="button">チェックイン（スタンプ+1）</button>
    <button class="btn secondary" id="clearBtn" type="button" style="font-size:12px;padding:10px 14px;margin-top:8px;">スタンプをクリア（テスト用）</button>
  </div>
</div>


<div class="modal" id="goldModal">
  <div class="m">
    <div class="title" id="rankupTitle">ランクアップ！</div>
    <div class="couponMini" aria-label="rank-up-coupon">
      <img class="couponMiniImg" id="rankupCouponImg" src="https://placehold.co/900x300/png?text=RANK+UP+COUPON" alt="coupon">
      <div class="couponMiniBody">
        <div class="couponMiniTitle" id="rankupCouponTitle">GOLDクーポン</div>
        <div class="couponMiniNote" id="rankupCouponNote">次回のお会計でご利用いただけます。レジで提示してください。</div>
      </div>
    </div>

    <div class="modalActions">
      <a class="mbtn secondary" role="button" aria-label="クーポン一覧へ" href="/coupons?store={{ (int)$store->id }}">クーポン一覧へ</a>
      <button class="mbtn secondary" id="goldOk" type="button">閉じる</button>
    </div>
  </div>
</div>

@include('partials.lottery-slot')
@include('partials.tab-bar', ['tabStoreId' => (int)$store->id, 'tabActive' => 'card'])
@include('partials.liff-init')
<script>
  const storeId = {{ (int)$store->id }};
  const goal = {{ (int)$goal }};
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const currentCardId = @json($currentCard->id ?? null);
  const isBeginnerInitial = @json($isBeginner ?? false);

  const btn = document.getElementById('checkinBtn');
  const goldModal = document.getElementById('goldModal');
  const goldOk = document.getElementById('goldOk');
  const rankupTitle = document.getElementById('rankupTitle');
  const rankupCouponImg = document.getElementById('rankupCouponImg');
  const rankupCouponTitle = document.getElementById('rankupCouponTitle');
  const rankupCouponNote = document.getElementById('rankupCouponNote');

  const stampNow = document.getElementById('stampNow');
  const stampBig = document.getElementById('stampBig');
  const visitBig = document.getElementById('visitBig');
  const lastVisit = document.getElementById('lastVisit');
  const nextText = document.getElementById('nextText');
  let pendingReloadAfterModal = false;

  function computeStateFromResponse(data){
    const cardProgress = (data.card_progress !== undefined && data.card_progress !== null)
      ? parseInt(data.card_progress, 10)
      : 0;
    const currentCardId = (data.current_card_id !== undefined && data.current_card_id !== null)
      ? String(data.current_card_id)
      : null;

    return { currentCardId, progress: isNaN(cardProgress) ? 0 : cardProgress };
  }


  function applyGoldTheme(isGold){
    if(isGold) document.body.classList.add('gold');
  }
  function removeGoldTheme(){
    document.body.classList.remove('gold');
  }

  function paintStamps(progress){
    const wrap = document.getElementById('stampsWrap');
    if (!wrap) return;
    wrap.querySelectorAll('.stamp').forEach(el => {
      const i = parseInt(el.dataset.i, 10);
      if (i <= progress) {
        el.classList.add('on');
      } else {
        el.classList.remove('on');
      }
    });
  }

  btn.addEventListener('click', async () => {
    btn.disabled = true;

    try {
      const res = await fetch(`/s/${storeId}/checkin`, {
        method: 'POST',
        headers: liffHeaders(),
      });

      const data = await res.json();
      if(!data.ok) throw new Error('checkin failed');

      const next = computeStateFromResponse(data);

      // 抽選演出があればスロットを先に表示
      if (data.lottery) {
        await new Promise(resolve => {
          window._slotCloseCallback = resolve;
          startSlotAnimation(data.lottery, storeId);
        });
      }

      // Show rank-up modal and reload after close if upgraded
      if (data.upgraded_to_gold) {
        if (rankupTitle && data.upgraded_to) {
          rankupTitle.textContent = `${data.upgraded_to} にランクアップ！`;
        }

        if (data.issued_coupon) {
          if (rankupCouponImg && data.issued_coupon.image_url) {
            rankupCouponImg.src = data.issued_coupon.image_url;
          }
          if (rankupCouponTitle && data.issued_coupon.title) {
            rankupCouponTitle.textContent = data.issued_coupon.title;
          }
          if (rankupCouponNote) {
            rankupCouponNote.textContent = data.issued_coupon.note || '';
          }
        }

        pendingReloadAfterModal = true;
        goldModal.classList.add('on');
        return;
      }

      // If the rank/card changed but no modal is needed, reload to render the correct UI/goal
      if (next.currentCardId && currentCardId && String(next.currentCardId) !== String(currentCardId)) {
        window.location.reload();
        return;
      }

      // badge progress update
      stampNow.textContent = String(next.progress);

      // visit count / last visit
      if (visitBig) visitBig.textContent = `来店回数：${data.visit_count}回`;
      lastVisit.textContent = data.last_visit_at || '—';

      // Stamps UI (all ranks)
      paintStamps(next.progress);

      // Beginner text only
      if (isBeginnerInitial) {
        nextText.textContent = `ゴールドまで：あと ${Math.max(0, goal - next.progress)} 回`;
      }

    } catch (e) {
      alert('チェックインに失敗しました。もう一度お試しください。');
      console.error(e);
    } finally {
      btn.disabled = false;
    }
  });

  goldOk.addEventListener('click', () => {
    goldModal.classList.remove('on');
    if (pendingReloadAfterModal) {
      window.location.reload();
    }
  });

  // クリアボタン
  const clearBtn = document.getElementById('clearBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', async () => {
      if (!confirm('スタンプと来店回数をすべてリセットします。よろしいですか？')) return;
      clearBtn.disabled = true;
      try {
        const res = await fetch(`/s/${storeId}/clear`, {
          method: 'POST',
          headers: liffHeaders(),
        });
        const data = await res.json();
        if (data.ok) {
          window.location.reload();
        } else {
          alert('クリアに失敗しました');
        }
      } catch (e) {
        alert('クリアに失敗しました');
        console.error(e);
      } finally {
        clearBtn.disabled = false;
      }
    });
  }
</script>
</body>
</html>
