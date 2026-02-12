<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
(function() {
  const LIFF_ID = @json(config('services.line.liff_id'));
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

    // セッション確立済みでページが表示されている場合、
    // LIFF SDKの初期化だけ行い認証フローはスキップ
    if (!LIFF_ID) {
      if (overlay) overlay.style.display = 'none';
      return;
    }

    try {
      await liff.init({ liffId: LIFF_ID });

      if (liff.isLoggedIn()) {
        _liffIdToken = liff.getIDToken();
      }

      // この時点でページはセッション認証済みで表示されているので
      // ローディングを非表示にするだけでOK
      if (overlay) overlay.style.display = 'none';

    } catch (e) {
      console.warn('LIFF init (on page):', e.message);
      // ページ自体はセッション認証で表示済みなので、LIFF SDK失敗は致命的ではない
      if (overlay) overlay.style.display = 'none';
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiff);
  } else {
    initLiff();
  }
})();
</script>
