<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Admin Users</title>
</head>
<body>
  <h1>来店ユーザー一覧</h1>

  <form method="get">
    <label>
      store_id:
      <input type="text" name="store_id" value="{{ $storeId }}">
    </label>
    <label>
      days_gte:
      <input type="text" name="days_gte" value="{{ $daysGte }}">
    </label>
    <button type="submit">Filter</button>
  </form>

  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>店舗</th>
      <th>LINEユーザーID</th>
      <th>来店回数</th>
      <th>最終来店日</th>
    </tr>
    @foreach ($users as $u)
      <tr>
        <td>{{ $u->store_name }} ({{ $u->store_id }})</td>
        <td>{{ $u->line_user_id }}</td>
        <td>{{ $u->visit_count }}</td>
        <td>{{ $u->last_visit_at }}</td>
      </tr>
    @endforeach
  </table>
</body>
</html>