<div id="liff-loading" style="
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0b0b0f;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-family: system-ui, -apple-system, BlinkMacSystemFont;
">
  <div class="liff-loading-spinner" style="
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255,255,255,.2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: liffSpin .8s linear infinite;
  "></div>
  <div class="liff-loading-text" style="
    margin-top: 14px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: .04em;
    opacity: .9;
  ">LINE認証中...</div>
</div>
<style>
  @keyframes liffSpin {
    to { transform: rotate(360deg); }
  }
</style>
