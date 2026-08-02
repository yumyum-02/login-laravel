<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>アカウント情報</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('/css/style.css')}}" rel="stylesheet">
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
              <i class="bi bi-person-gear me-2 text-primary"></i>アカウント情報
            </h1>
            <p class="text-muted mb-0">プロフィール情報を確認・編集できます</p>
          </div>

          <!-- プロフィール情報カード -->
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="bi bi-person-badge me-2"></i>プロフィール情報
              </h5>
            </div>
            <div class="card-body p-4">
              <!-- ユーザー名 -->
              <div class="row align-items-center mb-3 pb-3 border-bottom">
                <div class="col-sm-3">
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-person-fill me-2"></i>
                    <span class="fw-semibold">ユーザー名</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <p class="mb-0">{{ $user->name }}</p>
                </div>
                <div class="col-sm-3 text-end">
                  <a href="{{ route('edit-username') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i>変更
                  </a>
                </div>
              </div>

              <!-- アイコン -->
              <div class="row align-items-center">
                <div class="col-sm-3">
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-image-fill me-2"></i>
                    <span class="fw-semibold">アイコン</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <!-- アイコン表示 -->
                </div>
                <div class="col-sm-3 text-end">
                  <a href="../account-edit/edit-icon.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i>変更
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- アカウント管理カード -->
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title mb-0">
                <i class="bi bi-shield-lock me-2"></i>アカウント管理
              </h5>
            </div>
            <div class="card-body p-4">
              <!-- メールアドレス -->
              <div class="row align-items-center mb-3 pb-3 border-bottom">
                <div class="col-sm-3">
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-envelope-fill me-2"></i>
                    <span class="fw-semibold">メールアドレス</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <p class="mb-0">{{ $user->email }}</p>
                </div>
                <div class="col-sm-3 text-end">
                  <a href="{{ route('edit-email') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i>変更
                  </a>
                </div>
              </div>

              <!-- パスワード -->
              <div class="row align-items-center">
                <div class="col-sm-3">
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-lock-fill me-2"></i>
                    <span class="fw-semibold">パスワード</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <p class="mb-0 text-muted">••••••••</p>
                </div>
                <div class="col-sm-3 text-end">
                  <a href="../account-edit/edit-password.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i>変更
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>