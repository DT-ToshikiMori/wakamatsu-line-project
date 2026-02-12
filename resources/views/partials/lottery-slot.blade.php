{{-- スロットマシン風モーダル（3リール） --}}
<div class="slotModal" id="slotModal">
  <div class="slotWrap">
    <div class="slotTitle">抽選中...</div>

    <div class="slotMachine">
      <div class="slotReel" id="reel1">
        <div class="reelInner"></div>
      </div>
      <div class="slotReel" id="reel2">
        <div class="reelInner"></div>
      </div>
      <div class="slotReel" id="reel3">
        <div class="reelInner"></div>
      </div>
    </div>

    <div class="slotResult" id="slotResult" style="display:none">
      <div class="slotResultWin" id="slotResultWin" style="display:none">
        <div class="slotResultEmoji">🎉</div>
        <img class="slotResultImg" id="slotResultImg" src="" alt="prize">
        <div class="slotResultTitle" id="slotResultTitle"></div>
        <div class="slotResultSub">おめでとうございます！</div>
        <a class="slotBtn" id="slotCouponLink" href="/coupons">クーポンを見る</a>
      </div>
      <div class="slotResultMiss" id="slotResultMiss" style="display:none">
        <div class="slotResultEmoji">😢</div>
        <div class="slotResultTitle">残念...</div>
        <div class="slotResultSub">また挑戦してね！</div>
      </div>
      <button class="slotBtn secondary" id="slotClose" type="button">閉じる</button>
    </div>
  </div>
</div>

<style>
  .slotModal{
    position:fixed;inset:0;
    display:none;align-items:center;justify-content:center;
    background:rgba(0,0,0,.85);
    z-index:70;
    padding:18px;
  }
  .slotModal.on{display:flex}

  .slotWrap{
    width:min(420px, 92vw);
    text-align:center;
    color:#fff;
  }

  .slotTitle{
    font-size:22px;
    font-weight:900;
    letter-spacing:.06em;
    margin-bottom:20px;
    background:linear-gradient(135deg, #ffe082, #ffb300);
    -webkit-background-clip:text;
    color:transparent;
  }

  .slotMachine{
    display:flex;
    gap:8px;
    justify-content:center;
    margin:0 auto;
    perspective:600px;
  }

  .slotReel{
    width:100px;
    height:120px;
    overflow:hidden;
    border-radius:14px;
    background:rgba(255,255,255,.08);
    border:2px solid rgba(255,215,0,.35);
    position:relative;
  }

  .reelInner{
    display:flex;
    flex-direction:column;
    position:absolute;
    top:0;left:0;right:0;
    transition:none;
  }

  .reelItem{
    width:100px;
    height:120px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    gap:4px;
    flex-shrink:0;
  }

  .reelItem img{
    width:64px;
    height:64px;
    object-fit:cover;
    border-radius:10px;
  }

  .reelItem .reelLabel{
    font-size:10px;
    font-weight:700;
    opacity:.9;
    max-width:90px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .reelItem.miss{
    opacity:.6;
  }
  .reelItem.miss .reelLabel{
    font-size:18px;
  }

  @keyframes reelSpin{
    0%{transform:translateY(0)}
    100%{transform:translateY(calc(-120px * var(--items)))}
  }

  .reelInner.spinning{
    animation: reelSpin var(--spin-duration) linear infinite;
  }

  .slotResult{
    margin-top:24px;
  }

  .slotResultEmoji{
    font-size:48px;
    margin-bottom:8px;
  }

  .slotResultImg{
    width:140px;
    height:140px;
    object-fit:cover;
    border-radius:16px;
    margin:8px auto;
    display:block;
    border:2px solid rgba(255,215,0,.4);
  }

  .slotResultTitle{
    font-size:20px;
    font-weight:900;
    margin-top:8px;
  }

  .slotResultSub{
    font-size:13px;
    opacity:.8;
    margin-top:4px;
  }

  .slotBtn{
    display:block;
    width:100%;
    margin-top:16px;
    padding:14px;
    border-radius:14px;
    border:0;
    background:linear-gradient(135deg, #ffd54a, #ffb300);
    color:#1a1400;
    font-weight:900;
    font-size:15px;
    cursor:pointer;
    text-decoration:none;
    text-align:center;
    box-sizing:border-box;
  }

  .slotBtn.secondary{
    background:transparent;
    color:#fff;
    border:1px solid rgba(255,255,255,.25);
    margin-top:10px;
  }
</style>

<script>
function startSlotAnimation(lotteryData, storeId) {
  const modal = document.getElementById('slotModal');
  const slotTitle = modal.querySelector('.slotTitle');
  const result = document.getElementById('slotResult');
  const winDiv = document.getElementById('slotResultWin');
  const missDiv = document.getElementById('slotResultMiss');

  // Reset
  result.style.display = 'none';
  winDiv.style.display = 'none';
  missDiv.style.display = 'none';
  slotTitle.textContent = '抽選中...';
  modal.classList.add('on');

  const prizes = lotteryData.prizes || [];
  const winPrize = lotteryData.prize;
  const isWin = lotteryData.is_win;

  // Build reel items
  const reelIds = ['reel1', 'reel2', 'reel3'];
  const spinDurations = [0.8, 1.4, 2.0]; // left, center, right stop times
  const totalSpinItems = 20; // items to spin through before stopping

  reelIds.forEach((reelId, reelIdx) => {
    const reel = document.getElementById(reelId);
    const inner = reel.querySelector('.reelInner');
    inner.innerHTML = '';
    inner.classList.remove('spinning');
    inner.style.transform = '';
    inner.style.transition = '';

    // Generate reel items: random prizes then the winning prize at the end
    const items = [];
    for (let i = 0; i < totalSpinItems; i++) {
      const randomPrize = prizes[Math.floor(Math.random() * prizes.length)];
      items.push(randomPrize);
    }
    // Final item is the winning prize
    items.push(winPrize);

    items.forEach(p => {
      const div = document.createElement('div');
      div.className = 'reelItem' + (p.is_miss ? ' miss' : '');

      if (p.image_url && !p.is_miss) {
        const img = document.createElement('img');
        img.src = p.image_url;
        img.alt = p.title;
        div.appendChild(img);
      }

      const label = document.createElement('div');
      label.className = 'reelLabel';
      label.textContent = p.is_miss ? '✕' : p.title;
      div.appendChild(label);

      inner.appendChild(div);
    });

    // Start spinning animation
    const itemHeight = 120;
    const totalItems = items.length;

    // Set CSS variable for animation
    inner.style.setProperty('--items', totalItems - 1);
    inner.style.setProperty('--spin-duration', '0.3s');
    inner.classList.add('spinning');

    // Stop after delay - transition to final position
    const stopDelay = spinDurations[reelIdx] * 1000;
    setTimeout(() => {
      inner.classList.remove('spinning');
      const finalOffset = -(totalItems - 1) * itemHeight;
      inner.style.transition = 'transform 0.5s cubic-bezier(0.2, 0.8, 0.3, 1)';
      inner.style.transform = `translateY(${finalOffset}px)`;
    }, stopDelay);
  });

  // Show result after all reels stop
  const totalDuration = (spinDurations[2] + 0.8) * 1000;
  setTimeout(() => {
    slotTitle.textContent = isWin ? '当選！' : 'ハズレ...';
    result.style.display = 'block';

    if (isWin) {
      winDiv.style.display = 'block';
      const img = document.getElementById('slotResultImg');
      const title = document.getElementById('slotResultTitle');
      const link = document.getElementById('slotCouponLink');

      if (winPrize.image_url) {
        img.src = winPrize.image_url;
        img.style.display = 'block';
      } else {
        img.style.display = 'none';
      }
      title.textContent = winPrize.title;
      link.href = '/coupons?store=' + storeId;
    } else {
      missDiv.style.display = 'block';
    }
  }, totalDuration);

  // Close button
  document.getElementById('slotClose').onclick = () => {
    modal.classList.remove('on');
    if (window._slotCloseCallback) {
      window._slotCloseCallback();
      window._slotCloseCallback = null;
    }
  };

  // Coupon link also closes
  const couponLink = document.getElementById('slotCouponLink');
  couponLink.onclick = () => {
    modal.classList.remove('on');
  };
}
</script>
