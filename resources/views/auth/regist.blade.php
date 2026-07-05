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

            <div class="alert alert-success" role="alert"></div>

            <form action="./exec_register.php" method="post">
              <!-- ユーザー名 -->
              <div class="mb-3">
                <label for="name" class="form-label">ユーザー名 <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="text"
                         class="form-control"
                         id="name"
                         name="name"
                         placeholder=""
                         value="">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="name-tooltip">
                    <div class="validation-rule">※3文字以上32文字以内</div>
                    <div class="validation-rule">※漢字、ひらがな、カタカナ、半角英数字、スペース</div>
                  </div>
                </div>

                  <div class="invalid-feedback d-block">
                      <div></div>
                  </div>
              </div>

              <!-- メールアドレス -->
              <div class="mb-3">
                <label for="email" class="form-label">メールアドレス <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="email"
                         class="form-control"
                         id="email"
                         name="email"
                         placeholder=""
                         value="">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="email-tooltip">
                    <div class="validation-rule">※有効なメールアドレス形式</div>
                    <div class="validation-rule">※320文字以内</div>
                  </div>
                </div>

                  <div class="invalid-feedback d-block">
                      <div></div>
                  </div>
              </div>

              <!-- パスワード -->
              <div class="mb-4">
                <label for="password" class="form-label">パスワード <small class="text-danger">※入力必須</small></label>

                <div class="position-relative">
                  <input type="password"
                         class="form-control"
                         id="password"
                         name="password"
                         placeholder="">

                  <!-- バリデーションツールチップ -->
                  <div class="validation-tooltip" id="password-tooltip">
                    <div class="validation-rule">※16文字以上64文字以内</div>
                    <div class="validation-rule">※半角英数字と記号（! @ # $ % ^ & * ( ) - _ + =）</div>
                  </div>
                </div>

                  <div class="invalid-feedback d-block">
                      <div></div>
                  </div>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" name="regist_btn">登録する</button>
              </div>

              <input type="hidden" name="csrf_token" value="">
            </form>

            <hr class="my-4">
            <p class="text-center mb-0">
              <a href="/" class="text-decoration-none">← ログイン画面へ戻る</a>
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
