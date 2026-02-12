<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
(function() {
  const LIFF_ID = @json(config('services.line.liff_id'));
  let _liffReady = false;
  let _liffIdToken = null;

  /**
   * fetch用の認証ヘッダーを返す
   */
  window.liffHeaders = function() {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };
    if (_liffIdToken) {
      headers['Authorization'] = 'Bearer ' + _liffIdToken;
    }
    return headers;
  };

  async function initLiff() {
    const overlay = document.getElementById('liff-loading');

    try {
      await liff.init({ liffId: LIFF_ID });

      if (!liff.isLoggedIn()) {
        liff.login({ redirectUri: window.location.href });
        return;
      }

      _liffIdToken = liff.getIDToken();

      if (!_liffIdToken) {
        throw new Error('IDトークンが取得できませんでした');
      }

      // サーバーにIDトークンを送信してセッション確立
      const res = await fetch('/api/liff/init', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Authorization': 'Bearer ' + _liffIdToken
        },
        body: JSON.stringify({ id_token: _liffIdToken })
      });

      if (!res.ok) {
        throw new Error('セッション確立に失敗しました');
      }

      _liffReady = true;

      // ローディング非表示
      if (overlay) overlay.style.display = 'none';

      // ページに通知
      window.dispatchEvent(new CustomEvent('liff-ready', {
        detail: await res.json()
      }));

    } catch (e) {
      console.error('LIFF init error:', e);
      if (overlay) {
        overlay.querySelector('.liff-loading-text').textContent = 'LINEアプリからアクセスしてください';
        overlay.querySelector('.liff-loading-spinner')?.remove();
      }
    }
  }

  // DOM準備完了後に初期化
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiff);
  } else {
    initLiff();
  }
})();
</script>
