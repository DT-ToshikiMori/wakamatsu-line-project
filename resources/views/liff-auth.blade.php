<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>WAKAMATSU</title>
  <style>
    body{
      margin:0;
      background:#0b0b0f;
      color:#fff;
      font-family:system-ui,-apple-system,BlinkMacSystemFont;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
    }
    .center{text-align:center;padding:20px}
    .spinner{
      width:36px;height:36px;
      border:3px solid rgba(255,255,255,.2);
      border-top-color:#fff;
      border-radius:50%;
      animation:spin .8s linear infinite;
      margin:0 auto;
    }
    @keyframes spin{to{transform:rotate(360deg)}}
    .msg{
      margin-top:14px;
      font-size:14px;
      font-weight:700;
      letter-spacing:.04em;
      opacity:.9;
    }
    .err{
      margin-top:10px;
      font-size:12px;
      opacity:.7;
      line-height:1.5;
      word-break:break-all;
    }
    .debug{
      margin-top:16px;
      font-size:11px;
      opacity:.4;
      text-align:left;
      max-width:340px;
      margin-left:auto;
      margin-right:auto;
      line-height:1.6;
    }
  </style>
</head>
<body>
  <div class="center">
    <div class="spinner" id="spinner"></div>
    <div class="msg" id="msg">LINE認証中...</div>
    <div class="err" id="err"></div>
    <div class="debug" id="debug"></div>
  </div>

  <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
  <script>
  (async function() {
    const LIFF_ID = @json(config('services.line.liff_id'));
    const msg = document.getElementById('msg');
    const err = document.getElementById('err');
    const spinner = document.getElementById('spinner');
    const debug = document.getElementById('debug');

    function log(text) {
      debug.textContent += text + '\n';
      console.log('[liff-auth]', text);
    }

    function fail(message, detail) {
      spinner.style.display = 'none';
      msg.textContent = message;
      if (detail) err.textContent = detail;
    }

    if (!LIFF_ID) {
      fail('設定エラー', 'LIFF_IDが未設定です');
      return;
    }

    log('LIFF_ID: ' + LIFF_ID);

    try {
      log('liff.init() 開始...');
      await liff.init({ liffId: LIFF_ID });
      log('liff.init() 完了');

      const isInClient = liff.isInClient();
      const isLoggedIn = liff.isLoggedIn();
      log('isInClient: ' + isInClient);
      log('isLoggedIn: ' + isLoggedIn);

      if (!isLoggedIn) {
        if (isInClient) {
          // LINEアプリ内なのにログインできてない → LIFF設定の問題
          fail('認証エラー', 'LINEアプリ内でログイン状態を取得できません。LIFFアプリの設定を確認してください。');
          return;
        }
        // 外部ブラウザ → LINEログインにリダイレクト（1回だけ）
        const key = 'liff_login_attempt';
        if (sessionStorage.getItem(key)) {
          sessionStorage.removeItem(key);
          fail('ログインに失敗しました', 'LINEアプリから開いてください');
          return;
        }
        sessionStorage.setItem(key, '1');
        msg.textContent = 'LINEログインにリダイレクト中...';
        liff.login({ redirectUri: window.location.href });
        return;
      }

      // ログイン済み → IDトークン取得
      const idToken = liff.getIDToken();
      log('idToken: ' + (idToken ? idToken.substring(0, 20) + '...' : 'null'));

      if (!idToken) {
        fail('IDトークン取得エラー', 'LIFFアプリのスコープに「openid」が必要です。LINE Developers Consoleで確認してください。');
        return;
      }

      msg.textContent = 'セッション確立中...';

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const res = await fetch('/api/liff/init', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Authorization': 'Bearer ' + idToken
        },
        body: JSON.stringify({ id_token: idToken })
      });

      log('POST /api/liff/init → ' + res.status);

      if (!res.ok) {
        const body = await res.text();
        log('response: ' + body);
        fail('認証に失敗しました (' + res.status + ')', body);
        return;
      }

      const data = await res.json();
      log('user_id: ' + (data.user_id || '?'));

      // セッション確立完了 → リロード
      msg.textContent = '読み込み中...';
      window.location.reload();

    } catch (e) {
      console.error('LIFF auth error:', e);
      fail('エラーが発生しました', e.message || String(e));
    }
  })();
  </script>
</body>
</html>
