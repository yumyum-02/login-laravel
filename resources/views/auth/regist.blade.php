<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録画面</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('/css/style.css')}}">
</head>

<body class="bg-light min-vh-100 d-flex align-items-center py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">会員登録</h2>

            <form action="/regist" method="post">
              @csrf
              <!-- ユーザー名 -->
              <div class="mb-3">
                <label for="name" class="form-label">ユーザー名 <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="text"
                         class="form-control @if ($errors->has('name')) is-invalid @endif"
                         id="name"
                         name="name"
                         placeholder=""
                         value="{{ old('name') }}">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="name-tooltip">
                    <div class="validation-rule">※3文字以上16文字以内</div>
                    <div class="validation-rule">※漢字、ひらがな、カタカナ、半角英数字、スペース</div>
                  </div>
                </div>

                @if ($errors->has('name'))
                <div class="invalid-feedback d-block">
                  @foreach ($errors->get('name') as $error)
                    <div>{{ $error }}</div>
                  @endforeach
                </div>
                @endif
              </div>

              <!-- メールアドレス -->
              <div class="mb-3">
                <label for="email" class="form-label">メールアドレス <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="email"
                         class="form-control @if ($errors->has('email')) is-invalid @endif"
                         id="email"
                         name="email"
                         placeholder=""
                         value="{{ old('email')}}">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="email-tooltip">
                    <div class="validation-rule">※有効なメールアドレス形式</div>
                    <div class="validation-rule">※255文字以内</div>
                  </div>
                </div>

                @if ($errors->has('email'))
                <div class="invalid-feedback d-block">
                  @foreach ($errors->get('email') as $error)
                    <div>{{ $error }}</div>
                  @endforeach
                </div>
                @endif
              </div>

              <!-- パスワード -->
              <div class="mb-4">
                <label for="password" class="form-label">パスワード <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="password"
                         class="form-control @if ($errors->has('password')) is-invalid @endif"
                         id="password"
                         name="password"
                         placeholder="">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="password-tooltip">
                  <div class="validation-rule">※8文字以上64文字以内</div>
                  <div class="validation-rule">※半角英数字と記号（! @ # $ % ^ & * ( ) - _ + =）</div>
                  <div class="validation-rule">※大文字・小文字・数字・記号をそれぞれ1文字以上含める</div>
                  </div>
                </div>

                @if ($errors->has('password'))
                  <div class="invalid-feedback d-block">
                  @foreach ($errors->get('password') as $error)
                    <div>{{ $error }}</div>
                  @endforeach
                </div>
                @endif
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" name="regist_btn">登録する</button>
              </div>

            </form>

            <hr class="my-4">
            <p class="text-center mb-0">
              <a href="{{ url('/') }}" class="text-decoration-none">← ログイン画面へ戻る</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // 各入力欄のツールチップ表示/非表示のみ
    ['name', 'email', 'password'].forEach(fieldName => {
      const input = document.getElementById(fieldName);
      const tooltip = document.getElementById(fieldName + '-tooltip');

      // フォーカス時: ツールチップ表示
      input.addEventListener('focus', () => {
        tooltip.classList.add('show');
      });

      // フォーカス外れ: ツールチップ非表示
      input.addEventListener('blur', () => {
        tooltip.classList.remove('show');
      });
    });
  </script>
</body>

</html>
