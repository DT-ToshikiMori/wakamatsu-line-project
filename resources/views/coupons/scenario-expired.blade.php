<!DOCTYPE html>
<html lang="ja">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>クーポンの有効期限</title></head>
<body style="margin:0;background:#f7f7f7;color:#333;font-family:sans-serif">
<main style="max-width:480px;margin:64px auto;padding:32px 24px;text-align:center;background:white;border-radius:12px">
<h1 style="font-size:20px">このクーポンの有効期限が過ぎています。</h1>
<p>クーポン一覧から、ほかのクーポンをご確認ください。</p>
<a href="{{ url('/coupons') }}?store={{ (int) $storeId }}" style="display:inline-block;padding:14px 24px;background:#222;color:white;border-radius:8px;text-decoration:none">クーポン一覧へ</a>
</main></body></html>
