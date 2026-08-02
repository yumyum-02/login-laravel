# ログイン実装メモ（login ブランチ）

会員登録の次に、ログイン処理・画面保護・未ログイン時のリダイレクトを実装した流れをまとめたもの。

前提: [dsc_01make-regist.md](./dsc_01make-regist.md) の会員登録が動いていること。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

```
GET  /          → LoginController@create  → ログインフォーム表示
POST /          → LoginController@store   → バリデーション → Auth::attempt → /dashboard へ
GET  /dashboard → auth ミドルウェア       → 未ログインなら login へ（メッセージ付き）
```

| 画面 / 処理 | URL | コントローラー |
|-------------|-----|----------------|
| ログイン表示 | `GET /` | `LoginController@create`（名前: `login`） |
| ログイン送信 | `POST /` | `LoginController@store` |
| ダッシュボード | `GET /dashboard` | クロージャ + `auth` |

---

## 1. コントローラーの作成

```bash
php artisan make:controller LoginController
```

会員登録と同じく、画面表示は `create`、送信処理は `store` に分ける。

対象ファイル: `app/Http/Controllers/LoginController.php`

---

## 2. ログイン処理（`LoginController`）

参考: [認証](https://readouble.com/laravel/12.x/ja/authentication.html)

### 2-1. 画面表示（`create`）

```php
public function create(): View
{
    return view('auth.login');
}
```

### 2-2. バリデーション（`store`）

会員登録のうち、ログインに必要な項目だけ使う。登録時の `Password::min(8)->letters()...` はログインでは不要（すでに DB に保存済みのため）。

```php
$validated = $request->validate([
    'email' => ['required', 'email', 'max:255', 'string'],
    'password' => ['required', 'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/', 'min:8', 'max:64'],
]);
```

### 2-3. ユーザー認証（`Auth::attempt`）

自分で `User::where(...)->first()` してパスワードを比べる必要はない。`Auth::attempt` が次をまとめて行う。

1. メールでユーザーを探す
2. パスワードが合っているか確認する
3. 合っていればログイン状態にする

会員登録時にメールを小文字で保存しているため、ログイン時も揃える。

```php
$credentials = [
    'email' => mb_strtolower($validated['email'], 'UTF-8'),
    'password' => $validated['password'],
];

if (! Auth::attempt($credentials)) {
    throw ValidationException::withMessages([
        'email' => 'ログイン情報が正しくありません。',
    ]);
}

return redirect('/dashboard');
```

必要な use:

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
```

#### `$credentials` について

- `Auth::attempt` に渡す「メール・パスワードの配列」そのもの
- 変数名に特別な意味はなく、配列をそのまま渡してもよい
- `mb_strtolower` は登録時と同じく小文字化するためのもの

### 2-4. ログアウト（`destroy`）

メソッドの下書きはある。ルート接続とサイドバーからは [dsc_02-2make-logout.md](./dsc_02-2make-logout.md) で実装する。

```php
public function destroy(Request $request): RedirectResponse
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
}
```

---

## 3. ルート（`routes/web.php`）

### 3-1. ログインをコントローラーに接続

クロージャで view を返していた部分を `LoginController` に置き換える。名前付きルート `login` は、未ログイン時のリダイレクト先指定に使う。

```php
use App\Http\Controllers\LoginController;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store']);
```

### 3-2. ダッシュボードを保護

参考: [ルートの保護](https://readouble.com/laravel/12.x/ja/authentication.html#protecting-routes)

`auth` を付けると、ログイン済みの人だけが入れる。

```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');
```

---

## 4. ログイン画面（`resources/views/auth/login.blade.php`）

会員登録画面（`dsc_01make-regist.md`）と同じ考え方で Laravel 向けに直す。

| 項目 | 変更内容 |
|------|----------|
| CSRF | 手動の hidden をやめ、`@csrf` を使う |
| フォーム送信先 | 元の `./exec_login.php` → `{{ route('login') }}`（実体は `/`） |
| エラー表示 | `@error('email')` / `@error('password')` と `is-invalid` |
| 入力の再表示 | `value="{{ old('email') }}"` |
| 成功メッセージ | 会員登録完了用 `session('message')`（緑） |
| 注意メッセージ | 未ログイン誘導用 `session('error')`（赤） |
| 会員登録リンク | `{{ url('/regist') }}` |

```blade
@if (session('message'))
  <div class="alert alert-success" role="alert">{{ session('message') }}</div>
@endif

@if (session('error'))
  <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<form action="{{ route('login') }}" method="post">
  @csrf
  ...
</form>
```

### 注意: `@error` と `session()` の違い

| 書き方 | 用途 |
|--------|------|
| `@error('email')` | バリデーション（入力チェック）のエラー |
| `@if (session('error'))` | フラッシュメッセージ（「ログインしてください」など） |

未ログイン誘導のメッセージを `@error('error')` で出そうとすると表示されない。

### 注意: フォームの `action` とルートを一致させる

`POST /login` というルートは無い。送信先を `url('/login')` にすると `LoginController@store` が呼ばれず、認証されずに見えてしまう原因になる。

正しい例:

- `action="{{ route('login') }}"`（名前付きルート。GET と同じ `/`）
- または `action="{{ url('/') }}"`

---

## 5. 未ログインユーザーの扱い（`bootstrap/app.php`）

### 5-1. ログイン画面へリダイレクト

参考: [未認証ユーザーのリダイレクト](https://readouble.com/laravel/12.x/ja/authentication.html#redirecting-unauthenticated-users)

`auth` 付きページに未ログインで来たとき、どこへ送るかを決める。

単純なパス指定:

```php
$middleware->redirectGuestsTo('/');
```

名前付きルートを使う場合（このブランチで採用）:

```php
$middleware->redirectGuestsTo(fn (Request $request) => route('login'));
```

`route()` には **URL ではなくルート名** を渡す。`route('/')` は誤り。

### 5-2. リダイレクト時にメッセージを出す

参考: [フラッシュデータ](https://readouble.com/laravel/12.x/ja/session.html#flash-data)

飛ばす直前にセッションへ書き、ログイン画面で表示する。

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(function (Request $request) {
        session()->flash('error', 'ログインしてください');

        return route('login');
    });
})
```

Blade 側:

```blade
@if (session('error'))
  <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
```

### 5-3. 認証済みユーザーのリダイレクト（任意・未実装）

参考: [認証済みユーザーのリダイレクト](https://readouble.com/laravel/12.x/ja/authentication.html#redirecting-authenticated-users)

すでにログインしている人が `/` や `/regist` を開いたとき、ダッシュボードへ送る設定。

**必須ではない。** ログイン成功後の `redirect('/dashboard')` や `auth` 保護とは別物。余裕があれば次で足せる。

```php
// bootstrap/app.php
$middleware->redirectUsersTo('/dashboard');

// routes/web.php（例）
Route::get('/', [LoginController::class, 'create'])
    ->middleware('guest')
    ->name('login');
```

---

## 動作確認チェックリスト

- [ ] 登録済みユーザーでログイン → `/dashboard` に行ける
- [ ] 未登録／パスワード違い → 「ログイン情報が正しくありません。」が出る
- [ ] 未ログインで `/dashboard` → `/` に飛び、「ログインしてください」が出る
- [ ] 会員登録直後 → 緑の「会員登録が完了しました…」が出る
- [ ] フォーム送信先が `/`（`route('login')`）になっている
- [ ] テスト時は前回のログイン状態を消す（Cookie 削除 or シークレットウィンドウ）

「登録していないのに入れた」ように見えるときは、まず次を疑う。

1. フォームの `action` がルートとずれていて `Auth::attempt` が動いていない
2. 以前のログインセッションが残っている（ログアウト未接続のため残りやすい）

---

## 対象コミット（login ブランチ）

| コミット | 内容 |
|----------|------|
| [5dd975d](https://github.com/yumyum-02/login-laravel/commit/5dd975d81bf6507a5012206f1d11804ad5fab512) | `LoginController` 作成、`web.php` 接続 |
| [839dfc8](https://github.com/yumyum-02/login-laravel/commit/839dfc8dc0b81104273664abc7022671f4a514d4) | `login.blade.php`（CSRF・`@error`・`old`） |
| [649217d](https://github.com/yumyum-02/login-laravel/commit/649217d825aeeb395c1d739ffb32b190b4edef5e) | `auth`・`redirectGuestsTo`・フラッシュメッセージ |
| [65c9973](https://github.com/yumyum-02/login-laravel/commit/65c9973f364c91d51d853e0a6aac94c313f9330f) | フォーム `action` を `route('login')` に修正 |

変更ファイル:

- `app/Http/Controllers/LoginController.php`
- `routes/web.php`
- `resources/views/auth/login.blade.php`
- `bootstrap/app.php`

---

## 次のステップ案

- ~~ログアウト用ルートの接続と画面から呼ぶボタン~~ → [dsc_02-2make-logout.md](./dsc_02-2make-logout.md)
- ログイン成功後の `$request->session()->regenerate()`（セッション固定攻撃対策）
- 認証済みユーザー向け `guest` + `redirectUsersTo`（任意）
- ダッシュボードでログイン中ユーザー情報の表示（`Auth::user()`）
