# パスワード変更実装メモ

パスワード変更（画面表示・更新処理）を実装した流れをまとめたもの。

前提: [dsc_04edit-email.md](./dsc_04edit-email.md) のメールアドレス変更が動いていること（アカウント画面・認証があること）。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

```
GET  /edit-password  → クロージャ + auth              → パスワード変更フォーム表示
POST /edit-password  → EditPasswordController@update
                     → バリデーション → DB更新 → セッション再生成 → /account へ
```

| 画面 / 処理 | URL | 名前 | 備考 |
|-------------|-----|------|------|
| パスワード変更（表示） | `GET /edit-password` | `edit-password` | 元 PHP の `edit-password` 相当 |
| パスワード変更（更新） | `POST /edit-password` | `update-password` | ルート名は GET と分ける |

アカウント情報画面（`/account`）の「変更」リンクから `edit-password` へ遷移する。

---

## 1. アカウント情報画面からのリンク

### 1-1. Blade（`resources/views/account.blade.php`）

パスワードの変更ボタンを `route('edit-password')` へリンクする。

```blade
<a href="{{ route('edit-password') }}" class="btn btn-outline-secondary btn-sm">
  <i class="bi bi-pencil me-1"></i>変更
</a>
```

### 対象コミット

- 変更画面・コントローラー用意: [4d17c27](https://github.com/yumyum-02/login-laravel/commit/4d17c27f24fc3f3fe8c9abd0f6fda65a356ce07d)

---

## 2. パスワード変更画面（表示）

### 2-1. ルート（`routes/web.php`）

```php
use App\Http\Controllers\EditPasswordController;
use Illuminate\Support\Facades\Auth;

Route::get('edit-password', function () {
    $user = Auth::user();
    return view('edit-password', ['user' => $user]);
})->name('edit-password')->middleware('auth');
```

### 2-2. Blade（`resources/views/edit-password.blade.php`）

CSS と共通パーツ:

```blade
<link href="{{ asset('css/style.css') }}" rel="stylesheet">

<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

フォームの送信先は **更新用ルート名** にする（画面表示用の `edit-password` ではない）。

確認用欄の名前は `new_password_confirmation` にする（`same:new_password` で一致チェックするため）。

```blade
<form action="{{ route('update-password') }}" method="post">
  @csrf
  <input type="password" name="current_password">
  <input type="password" name="new_password">
  <input type="password" name="new_password_confirmation">
  ...
</form>
```

### 2-3. コントローラーの作成

```bash
php artisan make:controller EditPasswordController
```

更新処理用ルート:

```php
Route::post('edit-password', [EditPasswordController::class, 'update'])
    ->name('update-password')
    ->middleware('auth');
```

- URL（パス）は GET と同じ `edit-password` でよい
- `name` は衝突するため `update-password` と分ける
- 先頭で `use App\Http\Controllers\EditPasswordController;` を忘れない

### 対象コミット

- 変更画面・コントローラー用意: [4d17c27](https://github.com/yumyum-02/login-laravel/commit/4d17c27f24fc3f3fe8c9abd0f6fda65a356ce07d)

---

## 3. パスワード変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-password.php`（[yumyum-02/login](https://github.com/yumyum-02/login)）

### 3-1. 元 PHP との対応

#### コントローラーに書くこと

| 元 PHP | Laravel |
|--------|---------|
| `getCurrentPasswordErrors` | `current_password` ルール |
| `getPasswordValidationErrors` | `required` / `regex` / `min` / `max` / `Password::...` |
| `getPasswordCheck` | `new_password_confirmation` に `required` + `same:new_password` |
| `password_hash` + `updateUser` | `$request->user()->update(['password' => ...])`（モデルの `hashed` キャストでハッシュ化） |
| `session_regenerate_id(true)` | `$request->session()->regenerate()` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |

#### コントローラーに書かなくてよいこと

| 元 PHP | Laravel での担当 |
|--------|------------------|
| `requireLogin(...)` | ルートの `middleware('auth')` |
| CSRF 検証 | Blade の `@csrf` |
| try-catch（システム / DB エラー） | Laravel の例外処理に任せる（今回は自作しない） |

---

### 3-2. バリデーション

参考:

- [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)
- [current_password](https://readouble.com/laravel/12.x/ja/validation.html#rule-current-password)
- [same](https://readouble.com/laravel/12.x/ja/validation.html#rule-same)
- [Password ルールオブジェクト](https://readouble.com/laravel/12.x/ja/validation.html#validating-passwords)

#### 現在のパスワード

元 PHP の `password_verify` 相当。`authentication.html#password-confirmation`（別画面での再入力）とは別物。

```php
'current_password' => ['required', 'current_password'],
```

#### 新しいパスワード

元 PHP のルール:

- 必須
- 使える文字: 半角英数字と記号 `!@#$%^&*()-_+=`
- 8文字以上 64文字以内
- 確認用と一致

Laravel 版では、上に加えて会員登録と同じ強さ（`Password::min(8)->letters()->mixedCase()->numbers()->symbols()`）も付ける。

確認用は **確認欄側** にルールを付けて、エラーも確認欄の下に出す。

```php
'new_password' => [
    'bail',
    'required',
    'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/',
    'min:8',
    'max:64',
    Password::min(8)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols(),
],
'new_password_confirmation' => ['required', 'same:new_password'],
```

- `required` … 確認欄が空ならエラー（確認欄に表示）
- `same:new_password` … 新しいパスワードと一致しなければエラー（確認欄に表示）

`confirmed` だとエラーが `new_password` 側に付くため、今回は使わない。

---

### 3-3. DB 更新・セッション再生成・リダイレクト

参考:

- [認証済みユーザーの取得](https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user)
- [Eloquent — 更新](https://readouble.com/laravel/12.x/ja/eloquent.html#updates)
- [認証 — ログイン（session regenerate）](https://readouble.com/laravel/12.x/ja/authentication.html#authenticating-users)

```php
$request->user()->update([
    'password' => $validated['new_password'],
]);

$request->session()->regenerate();

return redirect()->route('account');
```

注意:

- User モデルに `'password' => 'hashed'` があるため、**`Hash::make` は書かない**（二重ハッシュになる）
- `$request->user()` … ログイン中ユーザー
- `$validated['new_password']` … チェック済みの新しいパスワード

---

### 3-4. エラー表示と日本語メッセージ

#### Blade

各欄のエラーを `@error` で出す。

```blade
<input type="password"
       class="form-control @error('current_password') is-invalid @enderror"
       name="current_password">

@error('current_password')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('current_password') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

`new_password` / `new_password_confirmation` も同様。

#### コントローラー（メッセージの日本語化）

参考: [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
[
    'current_password.required' => '現在のパスワードを入力してください。',
    'current_password.current_password' => '現在のパスワードが正しくありません。',
    'new_password.required' => 'パスワードを入力してください。',
    'new_password.regex' => 'パスワードは半角英数字と記号で入力してください。',
    'new_password.min' => 'パスワードは8文字以上64文字以内で入力してください。',
    'new_password.max' => 'パスワードは8文字以上64文字以内で入力してください。',
    'new_password_confirmation.required' => 'パスワード（確認用）を入力してください。',
    'new_password_confirmation.same' => 'パスワードが一致していません。',
]
```

完成形のメソッド全体は `app/Http/Controllers/EditPasswordController.php` の `update` を参照。

---

## 4. テストケース

前提: ログイン済みで `/edit-password` を開けること。

### 正常系

- [ ] 正しい現在パスワード + 条件を満たす新しいパスワード（確認一致）→ account へ移動
- [ ] 変更後、新しいパスワードでログインできる
- [ ] 変更後、古いパスワードではログインできない
- [ ] キャンセル → 更新せず account へ

### 異常系

- [ ] 現在パスワードが空 → 「現在のパスワードを入力してください。」
- [ ] 現在パスワードが違う → 「現在のパスワードが正しくありません。」
- [ ] 新しいパスワードが空 → 「パスワードを入力してください。」
- [ ] 使えない文字のみ → 「パスワードは半角英数字と記号で入力してください。」
- [ ] 7文字以下 → 「パスワードは8文字以上64文字以内で入力してください。」
- [ ] 確認用が空 → 確認欄に「パスワード（確認用）を入力してください。」
- [ ] 確認用と不一致 → 確認欄に「パスワードが一致していません。」
- [ ] 英字のみなど（Password ルール未充足）→ 強さに関するエラー

### セキュリティ・画面

- [ ] ログアウト後に `/edit-password` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）
- [ ] `@csrf` がある（無いと 419）
- [ ] 確認欄の name が `new_password_confirmation`
