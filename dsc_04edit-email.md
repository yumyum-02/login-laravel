# メールアドレス変更実装メモ

メールアドレス変更（画面表示・更新処理）を実装した流れをまとめたもの。

前提: [dsc_03edit-accout-username.md](./dsc_03edit-accout-username.md) のアカウント情報・ユーザー名変更が動いていること。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

```
GET  /edit-email  → クロージャ + auth     → メールアドレス変更フォーム表示
POST /edit-email  → EditEmailController@update
                  → バリデーション → DB更新 → /account へ
```

| 画面 / 処理 | URL | 名前 | 備考 |
|-------------|-----|------|------|
| メール変更（表示） | `GET /edit-email` | `edit-email` | 元 PHP の `edit-email` 相当 |
| メール変更（更新） | `POST /edit-email` | `update-email` | ルート名は GET と分ける |

アカウント情報画面（`/account`）の「変更」リンクから `edit-email` へ遷移する。

---

## 1. アカウント情報画面からのリンク

### 1-1. Blade（`resources/views/account.blade.php`）

メールアドレスの変更ボタンを `route('edit-email')` へリンクする。

```blade
<a href="{{ route('edit-email') }}" class="btn btn-outline-secondary btn-sm">
  <i class="bi bi-pencil me-1"></i>変更
</a>
```

### 対象コミット

- 変更画面・コントローラー用意: [acae092](https://github.com/yumyum-02/login-laravel/commit/acae0926b64da0ad4c6da390dd751d971bb242ba)

---

## 2. メールアドレス変更画面（表示）

### 2-1. ルート（`routes/web.php`）

```php
use App\Http\Controllers\EditEmailController;
use Illuminate\Support\Facades\Auth;

Route::get('edit-email', function () {
    $user = Auth::user();
    return view('edit-email', ['user' => $user]);
})->name('edit-email')->middleware('auth');
```

### 2-2. Blade（`resources/views/edit-email.blade.php`）

CSS と共通パーツ:

```blade
<link href="{{ asset('css/style.css') }}" rel="stylesheet">

<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

入力欄には「エラー前の入力」または「現在のメールアドレス」を出す。

```blade
<input value="{{ old('email') ?? $user->email }}">
```

フォームの送信先は **更新用ルート名** にする（画面表示用の `edit-email` ではない）。

```blade
<form action="{{ route('update-email') }}" method="post">
  @csrf
  ...
</form>
```

### 2-3. コントローラーの作成

```bash
php artisan make:controller EditEmailController
```

更新処理用ルート:

```php
Route::post('edit-email', [EditEmailController::class, 'update'])
    ->name('update-email')
    ->middleware('auth');
```

- URL（パス）は GET と同じ `edit-email` でよい
- `name` は衝突するため `update-email` と分ける
- 先頭で `use App\Http\Controllers\EditEmailController;` を忘れない

### 対象コミット

- 変更画面・コントローラー用意: [acae092](https://github.com/yumyum-02/login-laravel/commit/acae0926b64da0ad4c6da390dd751d971bb242ba)

---

## 3. メールアドレス変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-email.php`（[yumyum-02/login](https://github.com/yumyum-02/login)）

### 3-1. 元 PHP との対応

#### コントローラーに書くこと

| 元 PHP | Laravel |
|--------|---------|
| メール形式チェック（`filter_var`）・必須・文字数・重複 | `$request->validate([...])` |
| エラー時に edit-email へ戻す | `validate()` 失敗時に Laravel が自動で戻す |
| メールアドレスの更新 | `$request->user()->update([...])` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |

#### コントローラーに書かなくてよいこと

| 元 PHP | Laravel での担当 |
|--------|------------------|
| ログイン必須チェック | ルートの `middleware('auth')` |
| CSRF 検証 | Blade の `@csrf` |
| 入力の前後空白削除 | Laravel が自動削除 |
| セッションの `email` 更新 | 不要（DB 更新後、表示時に最新を読む） |
| try-catch（システム / DB エラー） | Laravel の例外処理に任せる（今回は自作しない） |

---

### 3-2. バリデーション

参考:

- [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)
- [email](https://readouble.com/laravel/12.x/ja/validation.html#rule-email)
- [unique](https://readouble.com/laravel/12.x/ja/validation.html#rule-unique)

ルール（順番どおり）:

- 必須
- メールアドレスの形式
- 255文字以内
- 他ユーザーと重複していないこと

元 PHP の形式チェック:

```text
return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
```

Laravel 版では `email` ルールでよい。

- `email` … RFC に沿った判定（Laravel らしい書き方）
- `email:filter` … 元 PHP の `filter_var` に近い（今回は使わない）

重複チェック:

```text
unique:users,email,{自分のユーザーID}
```

末尾の ID は「自分の今のメールはそのままOK」にするため。

```php
'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
```

---

### 3-3. DB 更新とリダイレクト

参考:

- [認証済みユーザーの取得](https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user)
- [Eloquent — 更新](https://readouble.com/laravel/12.x/ja/eloquent.html#updates)
- [バリデーション済み入力値の取得](https://readouble.com/laravel/12.x/ja/validation.html#working-with-validated-input)

チェックに通った値だけを更新に使う。

```php
$validated = $request->validate([/* ルール */], [/* メッセージ */]);

$request->user()->update([
    'email' => $validated['email'],
]);

return redirect()->route('account');
```

- `$request->user()` … ログイン中ユーザー（= 元の `$_SESSION['user']`）
- `$validated['email']` … チェック済みの値（`$request->email` より意図がはっきりする）

### 対象コミット

- バリデーション・更新・エラー表示: [921ed1e](https://github.com/yumyum-02/login-laravel/commit/921ed1eeb7da86a53efd571c49fb0157b9a74f6a)

---

### 3-4. エラー表示と日本語メッセージ

#### Blade

`email` のエラーをすべて出す。入力欄の赤枠も `@error` で付ける。

```blade
<input type="text"
       class="form-control @error('email') is-invalid @enderror"
       name="email"
       value="{{ old('email') ?? $user->email }}">

@error('email')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('email') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

- `@error('email')` … `email` にエラーがあるときだけ囲む
- `$errors->get('email')` … **`email` だけ**のメッセージ配列（`$errors` 全体ではない）

#### コントローラー（メッセージの日本語化）

参考: [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
$validated = $request->validate(
    [
        'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
    ],
    [
        'email.required' => 'メールアドレスは必須です。',
        'email.email' => 'メールアドレスの形式が不正です。',
        'email.max' => 'メールアドレスは255文字以内です。',
        'email.unique' => 'そのメールアドレスはすでに使用されています。',
    ]
);
```

完成形のメソッド全体は `app/Http/Controllers/EditEmailController.php` の `update` を参照。

### 対象コミット

- バリデーション・更新・エラー表示: [921ed1e](https://github.com/yumyum-02/login-laravel/commit/921ed1eeb7da86a53efd571c49fb0157b9a74f6a)

---

## 4. テストケース

前提: ログイン済みで `/edit-email` を開けること。

### 正常系

- [ ] 新しい未使用メールで保存 → account に移動し、メールが更新されている
- [ ] 自分の今のメールのまま保存 → 成功（自分自身は重複扱いにしない）
- [ ] キャンセル → 更新せず account へ

### 異常系

- [ ] （空欄）→ 「メールアドレスは必須です。」
- [ ] `not-an-email` → 「メールアドレスの形式が不正です。」
- [ ] 256文字以上 → 「メールアドレスは255文字以内です。」
- [ ] 他ユーザーが使っているメール → 「そのメールアドレスはすでに使用されています。」
- [ ] エラー後 → 入力欄にさっきの値が残っている

### セキュリティ・画面

- [ ] ログアウト後に `/edit-email` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）
- [ ] 変更後の account → 新しいメールが表示される
