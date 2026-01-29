<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $store->name }} | スタンプカード</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{margin:0;background:#0b0b0f;color:#fff;font-family:system-ui,-apple-system,BlinkMacSystemFont}
    .wrap{max-width:520px;margin:0 auto;padding:18px 14px 28px}
    .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:16px}
    .top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .store{font-weight:800;letter-spacing:.06em}
    .sub{opacity:.8;font-size:12px;margin-top:4px}
    .badge{font-size:12px;background:rgba(255,255,255,.14);padding:6px 10px;border-radius:999px}
    .big{font-size:44px;font-weight:900;margin:10px 0 2px}
    .big2{font-size:28px;font-weight:900;margin:6px 0 2px;opacity:.95}
    .muted{opacity:.75;font-size:12px}
    /* Beginner stamp (real stamp-like) */
    .stamps{display:flex;gap:12px;margin-top:14px}
    .stamp{
      width:68px;height:68px;border-radius:999px;
      display:flex;align-items:center;justify-content:center;
      font-weight:900;font-size:16px;letter-spacing:.02em;
      border:2px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.06);
      color: rgba(255,255,255,.75);
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
      border-color: rgba(255,255,255,.75);
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
    .stamp .stampText{position:relative;z-index:2;text-transform:uppercase}
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
    .section{margin-top:16px}
    .row{display:flex;justify-content:space-between;gap:10px;align-items:center}
    .btn{width:100%;margin-top:14px;padding:14px 14px;border-radius:14px;border:0;background:#fff;color:#0b0b0f;font-weight:800;cursor:pointer}
    .btn.secondary{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
    body.gold .btn.secondary{border:1px solid rgba(255,215,0,.28); color:#fff}
    .ok{margin-top:12px;padding:10px 12px;border-radius:12px;background:rgba(46,204,113,.18);border:1px solid rgba(46,204,113,.35)}
    a{color:#fff}
    input[type=hidden]{display:none}

    /* Gold theme */
    body.gold{
      background: radial-gradient(1200px 600px at 20% 10%, rgba(255,215,0,.25), transparent 60%),
                  radial-gradient(900px 500px at 80% 30%, rgba(255,170,0,.18), transparent 55%),
                  #07070a;
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
      background: linear-gradient(135deg, rgba(255,215,0,.35), rgba(255,170,0,.18));
      border: 1px solid rgba(255,215,0,.25);
    }
    body.gold .btn{
      background: linear-gradient(135deg, #ffd54a, #ffb300);
      color:#1a1400;
    }

    /* Animations */
    @keyframes pop {
      0%{transform:scale(.85); opacity:0}
      60%{transform:scale(1.1); opacity:1}
      100%{transform:scale(1); opacity:1}
    }
    .plus{
      position:fixed; left:50%; top:40%;
      transform:translate(-50%,-50%);
      font-weight:900; font-size:36px;
      padding:10px 14px;
      border-radius:14px;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.18);
      backdrop-filter: blur(8px);
      opacity:0;
      pointer-events:none;
      z-index: 50;
    }
    .plus.show{ animation: pop .55s ease forwards; }

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
    }
  </style>
</head>
<body class="{{ $isGold ? 'gold' : '' }}">
<div class="wrap">
  <div class="card">
    <div class="top">
      <div>
        <div class="store">{{ $store->name }}</div>
        <div class="sub">スタンプカード（デモ：u={{ $lineUserId }}）ここが変わる</div>
      </div>
      <div class="badge"><span id="stampNow">{{ $progress }}</span>/{{ $goal }}</div>
    </div>


    @if($isGold)
      <div class="big" id="stampBig">GOLD</div>
      <div class="big2" id="visitBig">来店回数：{{ $user->visit_count ?? 0 }}回</div>
      <div id="nextText" class="goldMsg">
        <div class="goldMsgTitle">毎回100円OFF</div>
        <div class="goldMsgSub"><b>この画面を提示＋チェックイン</b>で割引が適用されます</div>
        <div class="goldMsgThanks">いつもご利用ありがとうございます。あなたはWAKAMATSUのゴールドメンバーです。</div>
      </div>
    @else
      <div class="big" id="stampBig">BEGINNER</div>
      <div class="big2" id="visitBig">来店回数：{{ $user->visit_count ?? 0 }}回</div>
      <div class="muted" id="nextText">ゴールドまで：あと {{ $remaining }} 回</div>
      <div class="stamps" id="stampsWrap" aria-label="beginner-stamps">
        @for($i=1;$i<=$goal;$i++)
          @php
            $rot = $i === 1 ? '-2deg' : ($i === 2 ? '2deg' : '-1deg');
          @endphp
          <div class="stamp {{ $i <= $progress ? 'on' : '' }}" data-i="{{ $i }}" style="--rot: {{ $rot }};">
            <div class="stampText">STAMP</div>
          </div>
        @endfor
      </div>
    @endif

    <div class="section">
      <div class="row">
        <div class="muted">最終来店</div>
        <div class="muted" id="lastVisit">{{ $user->last_visit_at ?? '—' }}</div>
      </div>
      <div class="row" style="margin-top:6px">
        <div class="muted">来店回数</div>
        <div class="muted" id="visitCount">{{ $user->visit_count ?? 0 }}</div>
      </div>
    </div>

    <button class="btn" id="checkinBtn" type="button">チェックイン（スタンプ+1）</button>
    <button class="btn secondary" id="clearBtn" type="button" style="margin-top:10px;">スタンプをクリア（テスト用）</button>
  </div>
</div>

<div class="plus" id="plus">+1</div>

<div class="modal" id="goldModal">
  <div class="m">
    <div class="title">GOLD 会員にランクアップ！</div>
    <div class="p">✅ ゴールド会員に昇格しました！<br>🎁 <b>昇格クーポンを付与しました</b>（デザインのみ）。<br>本日から <b>毎回100円OFF</b> です。</div>
    <button class="mbtn" id="goldOk" type="button">OK</button>
  </div>
</div>

<script>
  const storeId = {{ (int)$store->id }};
  const goal = {{ (int)$goal }};
  const u = @json($lineUserId);
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const btn = document.getElementById('checkinBtn');
  const clearBtn = document.getElementById('clearBtn');
  const plus = document.getElementById('plus');
  const goldModal = document.getElementById('goldModal');
  const goldOk = document.getElementById('goldOk');

  const stampNow = document.getElementById('stampNow');
  const stampBig = document.getElementById('stampBig');
  const visitBig = document.getElementById('visitBig');
  const lastVisit = document.getElementById('lastVisit');
  const visitCount = document.getElementById('visitCount');
  const nextText = document.getElementById('nextText');

  function computeState(stampTotal){
    const isGold = stampTotal >= 3;
    const progress = isGold ? ((stampTotal - 3) % 3) : stampTotal;
    return { isGold, progress };
  }

  function showPlus(){
    plus.classList.remove('show');
    void plus.offsetWidth;
    plus.classList.add('show');
  }

  function applyGoldTheme(isGold){
    if(isGold) document.body.classList.add('gold');
  }
  function removeGoldTheme(){
    document.body.classList.remove('gold');
  }

  function paintStamps(progress){
    const wrap = document.getElementById('stampsWrap');
    if (!wrap) return; // Goldでは存在しない
    wrap.querySelectorAll('.stamp').forEach(el => {
      const i = parseInt(el.dataset.i, 10);
      if (i <= progress) {
        el.classList.add('on');
      } else {
        el.classList.remove('on');
      }
    });
  }
  
  clearBtn.addEventListener('click', async () => {
    if (!confirm('スタンプ/来店回数をクリアします（テスト用）。よろしいですか？')) return;
    clearBtn.disabled = true;
    try {
      const res = await fetch(`/s/${storeId}/clear`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ u })
      });
      const data = await res.json();
      if(!data.ok) throw new Error('clear failed');

      // Reset UI to beginner state
      stampNow.textContent = '0';
      stampBig.textContent = 'BEGINNER';
      if (visitBig) visitBig.textContent = '来店回数：0回';
      visitCount.textContent = '0';
      lastVisit.textContent = '—';
      nextText.textContent = 'ゴールドまで：あと 3 回';
      removeGoldTheme();
      goldModal.classList.remove('on');

      // Recreate beginner stamps UI if it was removed after reaching GOLD
      let wrap = document.getElementById('stampsWrap');
      if (!wrap) {
        // insert after nextText
        wrap = document.createElement('div');
        wrap.className = 'stamps';
        wrap.id = 'stampsWrap';
        wrap.setAttribute('aria-label', 'beginner-stamps');

        const rots = ['-2deg','2deg','-1deg'];
        for (let i = 1; i <= 3; i++) {
          const s = document.createElement('div');
          s.className = 'stamp';
          s.dataset.i = String(i);
          s.style.setProperty('--rot', rots[i-1]);

          const t = document.createElement('div');
          t.className = 'stampText';
          t.textContent = 'STAMP';
          s.appendChild(t);

          wrap.appendChild(s);
        }

        nextText.insertAdjacentElement('afterend', wrap);
      }

      paintStamps(0);
      showPlus();
    } catch (e) {
      alert('クリアに失敗しました。もう一度お試しください。');
      console.error(e);
    } finally {
      clearBtn.disabled = false;
    }
  });

  btn.addEventListener('click', async () => {
    btn.disabled = true;

    try {
      const res = await fetch(`/s/${storeId}/checkin`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ u })
      });

      const data = await res.json();
      if(!data.ok) throw new Error('checkin failed');

      const stampTotal = data.stamp;
      const state = computeState(stampTotal);

      // badge + main
      stampNow.textContent = state.progress;
      // Beginner only: animate stamps; Gold has no stamps UI
      if (!state.isGold) paintStamps(state.progress);

      if (state.isGold) {
        stampBig.textContent = 'GOLD';
        // keep visitBig as 来店回数：N回
        if (visitBig) visitBig.textContent = `来店回数：${data.visit_count}回`;
        nextText.innerHTML = `
          <div class="goldMsgTitle">毎回100円OFF</div>
          <div class="goldMsgSub"><b>この画面を提示＋チェックイン</b>で割引が適用されます</div>
          <div class="goldMsgThanks">いつもご利用ありがとうございます。あなたはWAKAMATSUのゴールドメンバーです。</div>
        `;
        const wrap = document.getElementById('stampsWrap');
        if (wrap) wrap.remove();
      } else {
        stampBig.textContent = 'BEGINNER';
        if (visitBig) visitBig.textContent = `来店回数：${data.visit_count}回`;
        nextText.textContent = `ゴールドまで：あと ${Math.max(0, 3 - stampTotal)} 回`;
      }

      visitCount.textContent = data.visit_count;
      lastVisit.textContent = data.last_visit_at || '—';

      showPlus();
      applyGoldTheme(state.isGold);

      if(data.upgraded_to_gold){
        goldModal.classList.add('on');
      }

    } catch (e) {
      alert('チェックインに失敗しました。もう一度お試しください。');
      console.error(e);
    } finally {
      btn.disabled = false;
    }
  });

  goldOk.addEventListener('click', () => goldModal.classList.remove('on'));
</script>
</body>
</html>