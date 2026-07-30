<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>プロフィール変更</title>
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
            <form action="{{ route('update-username') }}" method="post">
              @csrf
              <!-- ユーザー名 -->
              <div class="row align-items-center">
                <div class="col-sm-3">
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-person-fill me-2"></i>
                    <span class="fw-semibold">ユーザー名</span>
                  </div>
                </div>
                <div class="col-sm-9">
                  <input type="text"
                         class="form-control @error('name') is-invalid @enderror"
                         name="name"
                         value="{{ old('name') ?? $user->name}}">

                  @error('name')
                    <div class="invalid-feedback d-block">
                      @foreach ($errors->get('name') as $error)
                        <div>{{ $error }}</div>
                      @endforeach
                    </div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- カードフッター（ボタンエリア） -->
            <div class="card-footer bg-white border-top py-3">
              <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('account') }}" class="btn btn-outline-secondary">
                  <i class="bi bi-x-lg me-2"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary" name="profile_edit">
                  <i class="bi bi-check-lg me-2"></i>変更を保存
                </button>
              </div>
            </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

</body>

</html>