<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Check-in</title>

  <style>
    body{
      margin:0;
      background:#355799;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
      font-family: system-ui, -apple-system, BlinkMacSystemFont;
    }
    .text{
      margin-top:6px;
      opacity:.8;
    }
    video{
      display:block;
      margin:18px auto 0;
      width:96px;
      height:96px;
    }
  </style>
</head>

<body>
  <div class="wrap">
    <video
    src="/assets/wakamatsu_loading.mp4"
    autoplay
    muted
    loop
    playsinline
    preload="auto"
    style="
        display:block;
        width: calc(100vw - 40px);
        max-width: 420px; /* ← 大きくなりすぎ防止（任意） */
        height: auto;
        margin: 24px auto 0;
        border-radius: 16px; /* ← 角丸あると一気にプロ感 */
    "
    ></video>
  </div>

  <script>
    const redirectUrl = @json($redirectUrl);

    setTimeout(() => {
       if (redirectUrl) {
         window.location.href = redirectUrl;
       }
    }, 5000);
  </script>
</body>
</html>