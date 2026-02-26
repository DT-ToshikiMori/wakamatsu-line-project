<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>{{ $store->name }} | ユーザー登録</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{
      margin:0;
      background:#0b0b0f;
      color:#fff;
      font-family:system-ui,-apple-system,BlinkMacSystemFont;
    }
    .wrap{
      max-width:520px;
      margin:0 auto;
      padding:18px 14px 28px;
      position:relative;
      z-index:1;
    }
    .card{
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.12);
      border-radius:18px;
      padding:16px;
    }
    .store{font-weight:800;letter-spacing:.06em}
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
    .formTitle{
      font-size:18px;
      font-weight:900;
      margin-top:20px;
      letter-spacing:.04em;
    }
    .formDesc{
      font-size:13px;
      opacity:.75;
      margin-top:6px;
      line-height:1.5;
    }
    .field{margin-top:16px}
    .field label{
      display:block;
      font-size:13px;
      font-weight:700;
      margin-bottom:8px;
      letter-spacing:.02em;
    }
    .required{
      color:#f59e0b;
      font-size:11px;
      margin-left:4px;
    }
    .radioGroup{
      display:flex;
      gap:8px;
    }
    .radioGroup label{
      flex:1;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      padding:10px 8px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.18);
      background:rgba(255,255,255,.04);
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:all .2s;
    }
    .radioGroup input[type="radio"]{display:none}
    .radioGroup input[type="radio"]:checked + span{
      /* parent label styled via JS */
    }
    .radioGroup label.selected{
      border-color:rgba(245,196,81,.6);
      background:rgba(245,196,81,.12);
    }
    select{
      width:100%;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.18);
      background:rgba(255,255,255,.06);
      color:#fff;
      font-size:14px;
      font-weight:600;
      appearance:none;
      -webkit-appearance:none;
      cursor:pointer;
    }
    select option{
      background:#1a1a20;
      color:#fff;
    }
    .btn{
      width:100%;
      margin-top:20px;
      padding:14px 14px;
      border-radius:14px;
      border:0;
      background:#f5c451;
      color:#0b0b0f;
      font-weight:800;
      font-size:15px;
      cursor:pointer;
      letter-spacing:.04em;
    }
    .btn:disabled{
      opacity:.5;
      cursor:not-allowed;
    }
    .error{
      color:#ef4444;
      font-size:12px;
      margin-top:4px;
    }
  </style>
</head>
<body>
@include('partials.liff-loading')
<div class="wrap">
  <div class="card">
    <div class="store">{{ $store->name }}</div>
    <div class="userProfile">
      <div class="userAvatar">
        <img src="{{ $user->profile_image_url ?? 'https://placehold.co/72x72/png?text=USER' }}" alt="user">
      </div>
      <div class="userName">
        {{ $user->display_name ?? 'お客様' }} 様
      </div>
    </div>

    <div class="formTitle">ユーザー登録</div>
    <div class="formDesc">スタンプカードをご利用いただくため、以下の情報をご入力ください。</div>

    <form method="POST" action="/s/{{ $store->id }}/register" id="registerForm">
      @csrf

      <div class="field">
        <label>性別<span class="required">必須</span></label>
        <div class="radioGroup" id="genderRadios">
          <label class="{{ old('gender') === 'male' ? 'selected' : '' }}">
            <input type="radio" name="gender" value="male" {{ old('gender') === 'male' ? 'checked' : '' }}>
            <span>男性</span>
          </label>
          <label class="{{ old('gender') === 'female' ? 'selected' : '' }}">
            <input type="radio" name="gender" value="female" {{ old('gender') === 'female' ? 'checked' : '' }}>
            <span>女性</span>
          </label>
          <label class="{{ old('gender') === 'other' ? 'selected' : '' }}">
            <input type="radio" name="gender" value="other" {{ old('gender') === 'other' ? 'checked' : '' }}>
            <span>その他</span>
          </label>
        </div>
        @error('gender')
          <div class="error">{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label>生まれ年</label>
        <select name="birth_year">
          <option value="">選択しない</option>
          @for($y = date('Y'); $y >= 1920; $y--)
            <option value="{{ $y }}" {{ (int)old('birth_year') === $y ? 'selected' : '' }}>{{ $y }}年</option>
          @endfor
        </select>
      </div>

      <div class="field">
        <label>誕生月</label>
        <select name="birth_month">
          <option value="">選択しない</option>
          @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ (int)old('birth_month') === $m ? 'selected' : '' }}>{{ $m }}月</option>
          @endfor
        </select>
      </div>

      <button type="submit" class="btn" id="submitBtn">登録する</button>
    </form>
  </div>
</div>

@include('partials.liff-init')
<script>
  // Radio button selection styling
  document.querySelectorAll('#genderRadios label').forEach(label => {
    label.addEventListener('click', () => {
      document.querySelectorAll('#genderRadios label').forEach(l => l.classList.remove('selected'));
      label.classList.add('selected');
    });
  });

  // Form submit with LIFF auth header
  document.getElementById('registerForm').addEventListener('submit', function(e) {
    const checked = document.querySelector('input[name="gender"]:checked');
    if (!checked) {
      e.preventDefault();
      alert('性別を選択してください');
      return;
    }
    document.getElementById('submitBtn').disabled = true;
  });
</script>
</body>
</html>
