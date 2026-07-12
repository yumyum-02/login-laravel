<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン画面</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('/css/style.css')}}">
</head>

<body class="bg-light min-vh-100 d-flex align-items-center py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">ログイン</h2>

              @if (session('message'))
              <div class="alert alert-success" role="alert">{{ session('message') }}</div>
              @endif
              @if (session('error'))
              <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
              @endif

            <form action="{{ route('login')}}" method="post">
              @csrf
              <div class="mb-3">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       placeholder=""
                       value="{{ old('email')}}">

                @error('email')
                  <div class="invalid-feedback d-block">
                    @foreach ($errors->get('email') as $error)
                      <div>{{ $error }}</div>
                    @endforeach
                  </div>
                @enderror
              </div>
              <div class="mb-4">
                <label for="password" class="form-label">パスワード</label>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       placeholder="">

                @error('password')
                  <div class="invalid-feedback d-block">
                    @foreach ($errors->get('password') as $error)
                      <div>{{ $error }}</div>
                    @endforeach
                  </div>
                @enderror
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" name="login_btn">ログイン</button>
              </div>

            </form>

            <hr class="my-4">
            <p class="text-center mb-0">
              <a href="{{ url('/regist')}}" class="text-decoration-none">会員登録はこちら →</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>