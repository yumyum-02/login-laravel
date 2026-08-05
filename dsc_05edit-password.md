# パスワード変更実装メモ

パスワード変更（画面表示・更新処理）を実装した流れをまとめたもの。

前提: [dsc_03edit-accout-username.md](./dsc_03edit-accout-username.md) のアカウント情報・ユーザー名変更が動いていること。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

```
GET  /edit-password  → クロージャ + auth     → パスワード変更フォーム表示
POST /edit-password  → EditpasswordController@update
                  → バリデーション → DB更新 → /account へ
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

---

## 2. パスワード変更画面（表示）

### 2-1. ルート（`routes/web.php`）

```php
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

<form action="{{ route('update-password') }}" method="post">
  @csrf
  ...
</form>
```

### 2-3. コントローラーの作成

```bash
php artisan make:controller EditpasswordController
```

更新処理用ルート:

```php
use App\Http\Controllers\EditpasswordController;

Route::post('edit-password', [EditpasswordController::class, 'update'])
    ->name('update-password')
    ->middleware('auth');
```

- URL（パス）は GET と同じ `edit-password` でよい
- `name` は衝突するため `update-password` と分ける
- 先頭で `use App\Http\Controllers\EditpasswordController;` を忘れない

### 対象コミット

- 変更画面・コントローラー用意: 

---

## 3. パスワード変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-password.php`（[yumyum-02/login](https://github.com/yumyum-02/login)）

### 3-1. 元 PHP との対応

#### コントローラーに書くこと

| 元 PHP | Laravel |
|--------|---------|
| パスワード形式チェック（`filter_var`）・必須・文字数・重複 | `$request->validate([...])` |
| エラー時に edit-password へ戻す | `validate()` 失敗時に Laravel が自動で戻す |
| パスワードの更新 | `$request->user()->update([...])` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |

#### コントローラーに書かなくてよいこと

| 元 PHP | Laravel での担当 |
|--------|------------------|
| ログイン必須チェック | ルートの `middleware('auth')` |
| CSRF 検証 | Blade の `@csrf` |
| 入力の前後空白削除 | Laravel が自動削除 |
| セッションの `password` 更新 | 不要（DB 更新後、表示時に最新を読む） |
| try-catch（システム / DB エラー） | Laravel の例外処理に任せる（今回は自作しない） |

---

### 3-2. バリデーション

参考:

- [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)
- [password](https://readouble.com/laravel/12.x/ja/validation.html#rule-password)
- [unique](https://readouble.com/laravel/12.x/ja/validation.html#rule-unique)

ルール（順番どおり）:

- 必須
- パスワードの形式
- 255文字以内
- 他ユーザーと重複していないこと

元 PHP の形式チェック:

```text
return filter_var($password, FILTER_VALIDATE_password) !== false;
```

Laravel 版では `password` ルールでよい。

- `password` … RFC に沿った判定（Laravel らしい書き方）
- `password:filter` … 元 PHP の `filter_var` に近い（今回は使わない）

重複チェック:

```text
unique:users,password,{自分のユーザーID}
```

末尾の ID は「自分の今のパスワードはそのままOK」にするため。

```php
'password' => ['required', 'password', 'max:255', 'unique:users,password,' . $request->user()->id],
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
    'password' => $validated['password'],
]);

return redirect()->route('account');
```

- `$request->user()` … ログイン中ユーザー（= 元の `$_SESSION['user']`）
- `$validated['password']` … チェック済みの値（`$request->password` より意図がはっきりする）

### 対象コミット

- バリデーション・更新・エラー表示: [921ed1e](https://github.com/yumyum-02/login-laravel/commit/921ed1eeb7da86a53efd571c49fb0157b9a74f6a)

---

### 3-4. エラー表示と日本語メッセージ

#### Blade

`password` のエラーをすべて出す。入力欄の赤枠も `@error` で付ける。

```blade
<input type="text"
       class="form-control @error('password') is-invalid @enderror"
       name="password"
       value="{{ old('password') ?? $user->password }}">

@error('password')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('password') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

- `@error('password')` … `password` にエラーがあるときだけ囲む
- `$errors->get('password')` … **`password` だけ**のメッセージ配列（`$errors` 全体ではない）

#### コントローラー（メッセージの日本語化）

参考: [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
$validated = $request->validate(
    [
        'password' => ['required', 'password', 'max:255', 'unique:users,password,' . $request->user()->id],
    ],
    [
        'password.required' => 'パスワードは必須です。',
        'password.password' => 'パスワードの形式が不正です。',
        'password.max' => 'パスワードは255文字以内です。',
        'password.unique' => 'そのパスワードはすでに使用されています。',
    ]
);
```

完成形のメソッド全体は `app/Http/Controllers/EditpasswordController.php` の `update` を参照。

### 対象コミット

- バリデーション・更新・エラー表示: [921ed1e](https://github.com/yumyum-02/login-laravel/commit/921ed1eeb7da86a53efd571c49fb0157b9a74f6a)

---

## 4. テストケース

前提: ログイン済みで `/edit-password` を開けること。

### 正常系

- [ ] 新しい未使用パスワードで保存 → account に移動し、パスワードが更新されている
- [ ] 自分の今のパスワードのまま保存 → 成功（自分自身は重複扱いにしない）
- [ ] キャンセル → 更新せず account へ

### 異常系

- [ ] （空欄）→ 「パスワードは必須です。」
- [ ] `not-an-password` → 「パスワードの形式が不正です。」
- [ ] 256文字以上 → 「パスワードは255文字以内です。」
- [ ] 他ユーザーが使っているパスワード → 「そのパスワードはすでに使用されています。」
- [ ] エラー後 → 入力欄にさっきの値が残っている

### セキュリティ・画面

- [ ] ログアウト後に `/edit-password` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）
- [ ] 変更後の account → 新しいパスワードが表示される
