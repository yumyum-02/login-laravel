<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>掲示板</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>

<body class="bg-light min-vh-100">
  <x-navbar></x-navbar>
  <x-sidebar></x-sidebar>

  <main class="p-4">
  <div class="container-fluid">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <!-- ページヘッダー -->
          <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">
              <i class="bi bi-chat-fill me-2 text-primary"></i>掲示板
            </h1>
            <p class="text-muted mb-0">掲示板では、ユーザー同士で自由にメッセージを交換できます</p>
          </div>

          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="bi bi-chat-fill me-2"></i>投稿一覧
              </h5>
              <button class="btn btn-primary">新規投稿</button>
            </div>
            <div class="card-body p-4">
              <p>投稿者名</p><p>投稿日時</p>
              <p>投稿内容</p>
              <button class="btn btn-primary">いいね</button>
              <button class="btn btn-primary">コメント</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </main>

</body>

</html>