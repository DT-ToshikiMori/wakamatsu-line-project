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
    .center{text-align:center}
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
    }
  </style>
</head>
<body>
  <div class="center">
    <div class="spinner" id="spinner"></div>
    <div class="msg" id="msg">LINE認証中...</div>
    <div class="err" id="err"></div>
  </div>

  <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
  <script>
  (async function() {
    const LIFF_ID = @json(config('services.line.liff_id'));
    const msg = document.getElementById('msg');
    const err = document.getElementById('err');
    const spinner = document.getElementById('spinner');

    try {
      await liff.init({ liffId: LIFF_ID });

      if (!liff.isLoggedIn()) {
        msg.textContent = 'LINEログインにリダイレクト中...';
        liff.login({ redirectUri: window.location.href });
        return;
      }

      const idToken = liff.getIDToken();
      if (!idToken) throw new Error('IDトークンが取得できません');

      msg.textContent = 'セッション確立中...';

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const res = await fetch('/api/liff/init', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Authorization': 'Bearer ' + idToken
        },
        body: JSON.stringify({ id_token: idToken })
      });

      if (!res.ok) throw new Error('認証に失敗しました (' + res.status + ')');

      // セッション確立完了 → 同じURLをリロード（今度はセッションがあるので通過する）
      window.location.reload();

    } catch (e) {
      console.error('LIFF auth error:', e);
      spinner.style.display = 'none';
      msg.textContent = 'LINEアプリからアクセスしてください';
      err.textContent = e.message || '';
    }
  })();
  </script>
</body>
</html>
