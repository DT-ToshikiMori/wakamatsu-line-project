<style>
  .tabBar{
    position:fixed;
    bottom:0;left:0;right:0;
    display:flex;
    background:rgba(16,16,20,.96);
    border-top:1px solid rgba(255,255,255,.10);
    z-index:50;
    padding-bottom: env(safe-area-inset-bottom, 0);
  }
  .tabBar a{
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:3px;
    padding:10px 0 8px;
    text-decoration:none;
    color:rgba(255,255,255,.45);
    font-size:10px;
    font-weight:700;
    letter-spacing:.03em;
    transition:color .15s;
  }
  .tabBar a.active{
    color:#fff;
  }
  .tabBar svg{
    width:22px;height:22px;
    fill:currentColor;
  }
  .tabBarSpacer{
    height:68px;
  }
</style>
<div class="tabBarSpacer"></div>
<nav class="tabBar">
  <a href="/s/{{ $tabStoreId ?? 1 }}/card" class="{{ ($tabActive ?? '') === 'card' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 14H4V6h16v12zM6 10h2v2H6v-2zm4 0h2v2h-2v-2zm4 0h2v2h-2v-2zm-8 4h2v2H6v-2zm4 0h2v2h-2v-2z"/></svg>
    <span>スタンプ</span>
  </a>
  <a href="/coupons?store={{ $tabStoreId ?? 1 }}" class="{{ ($tabActive ?? '') === 'coupons' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24"><path d="M21 5H3a1 1 0 00-1 1v4.5a1 1 0 001 1 1.5 1.5 0 010 3 1 1 0 00-1 1V20a1 1 0 001 1h18a1 1 0 001-1v-4.5a1 1 0 00-1-1 1.5 1.5 0 010-3 1 1 0 001-1V6a1 1 0 00-1-1zm-1 5.1a3.5 3.5 0 000 5.8V19H4v-3.1a3.5 3.5 0 000-5.8V7h16v3.1z"/></svg>
    <span>クーポン</span>
  </a>
</nav>
