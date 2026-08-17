# アイコン変更実装メモ

アイコン変更（画面表示・更新処理）を実装した流れをまとめたもの。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

```
GET  /edit-icon  → クロージャ + auth              → アイコン変更フォーム表示
POST /edit-icon  → EditIconController@update
                     → バリデーション → DB更新 → セッション再生成 → /account へ
```

| 画面 / 処理 | URL | 名前 | 備考 |
|-------------|-----|------|------|
| アイコン変更（表示） | `GET /edit-icon` | `edit-icon` | 元 PHP の `edit-icon` 相当 |
| アイコン変更（更新） | `POST /edit-icon` | `update-icon` | ルート名は GET と分ける |

アカウント情報画面（`/account`）の「変更」リンクから `edit-icon` へ遷移する。

---

## 1. アカウント情報画面からのリンク

### 1-1. Blade（`resources/views/account.blade.php`）

アイコンの変更ボタンを `route('edit-icon')` へリンクする。

```blade
<a href="{{ route('edit-icon') }}" class="btn btn-outline-secondary btn-sm">
  <i class="bi bi-pencil me-1"></i>変更
</a>
```

### 対象コミット

- 変更画面・コントローラー用意: [4d17c27](https://github.com/yumyum-02/login-laravel/commit/4d17c27f24fc3f3fe8c9abd0f6fda65a356ce07d)

---

## 2. アイコン変更画面（表示）

### 2-1. ルート（`routes/web.php`）

```php
Route::get('edit-icon', function () {
    $user = Auth::user();
    return view('edit-icon', ['user' => $user]);
})->name('edit-icon')->middleware('auth');
```

### 2-2. Blade（`resources/views/edit-icon.blade.php`）

CSS と共通パーツ:

```blade
<link href="{{ asset('css/style.css') }}" rel="stylesheet">

<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

フォームの送信先は **更新用ルート名** にする（画面表示用の `edit-icon` ではない）。

```blade
<form action="{{ route('update-icon') }}" method="post">
  @csrf
  ...
</form>
```

ここまでの変更：https://github.com/yumyum-02/login-laravel/commit/2cc308dd398068144fb701fd34095192836ebe4e








### 2-3. コントローラーの作成

```bash
php artisan make:controller EditIconController
```

更新処理用ルート:

```php
Route::post('edit-icon', [EditIconController::class, 'update'])
    ->name('update-icon')
    ->middleware('auth');
```

- URL（パス）は GET と同じ `edit-icon` でよい
- `name` は衝突するため `update-icon` と分ける
- 先頭で `use App\Http\Controllers\EditIconController;` を忘れない

### 対象コミット

- 変更画面・コントローラー用意: [4d17c27](https://github.com/yumyum-02/login-laravel/commit/4d17c27f24fc3f3fe8c9abd0f6fda65a356ce07d)

---

## 3. アイコン変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-icon.php`（[yumyum-02/login](https://github.com/yumyum-02/login)）

### 3-1. 元 PHP との対応

#### コントローラーに書くこと

| 元 PHP | Laravel |
|--------|---------|
| `getCurrenticonErrors` | `current_icon` ルール |
| `geticonValidationErrors` | `required` / `regex` / `min` / `max` / `icon::...` |
| `geticonCheck` | `new_icon_confirmation` に `required` + `same:new_icon` |
| `icon_hash` + `updateUser` | `$request->user()->update(['icon' => ...])`（モデルの `hashed` キャストでハッシュ化） |
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
- [current_icon](https://readouble.com/laravel/12.x/ja/validation.html#rule-current-icon)
- [same](https://readouble.com/laravel/12.x/ja/validation.html#rule-same)
- [icon ルールオブジェクト](https://readouble.com/laravel/12.x/ja/validation.html#validating-icons)

#### 現在のアイコン

元 PHP の `icon_verify` 相当。`authentication.html#icon-confirmation`（別画面での再入力）とは別物。

```php
'current_icon' => ['required', 'current_icon'],
```

#### 新しいアイコン

元 PHP のルール:

- 必須
- 使える文字: 半角英数字と記号 `!@#$%^&*()-_+=`
- 8文字以上 64文字以内
- 確認用と一致

Laravel 版では、上に加えて会員登録と同じ強さ（`icon::min(8)->letters()->mixedCase()->numbers()->symbols()`）も付ける。

確認用は **確認欄側** にルールを付けて、エラーも確認欄の下に出す。

```php
'new_icon' => [
    'bail',
    'required',
    'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/',
    'min:8',
    'max:64',
    icon::min(8)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols(),
],
'new_icon_confirmation' => ['required', 'same:new_icon'],
```

- `required` … 確認欄が空ならエラー（確認欄に表示）
- `same:new_icon` … 新しいアイコンと一致しなければエラー（確認欄に表示）

`confirmed` だとエラーが `new_icon` 側に付くため、今回は使わない。

---

### 3-3. DB 更新・セッション再生成・リダイレクト

参考:

- [認証済みユーザーの取得](https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user)
- [Eloquent — 更新](https://readouble.com/laravel/12.x/ja/eloquent.html#updates)
- [認証 — ログイン（session regenerate）](https://readouble.com/laravel/12.x/ja/authentication.html#authenticating-users)

```php
$request->user()->update([
    'icon' => $validated['new_icon'],
]);

$request->session()->regenerate();

return redirect()->route('account');
```

注意:

- User モデルに `'icon' => 'hashed'` があるため、**`Hash::make` は書かない**（二重ハッシュになる）
- `$request->user()` … ログイン中ユーザー
- `$validated['new_icon']` … チェック済みの新しいアイコン

---

### 3-4. エラー表示と日本語メッセージ

#### Blade

各欄のエラーを `@error` で出す。

```blade
<input type="icon"
       class="form-control @error('current_icon') is-invalid @enderror"
       name="current_icon">

@error('current_icon')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('current_icon') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

`new_icon` / `new_icon_confirmation` も同様。

#### コントローラー（メッセージの日本語化）

参考: [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
[
    'current_icon.required' => '現在のアイコンを入力してください。',
    'current_icon.current_icon' => '現在のアイコンが正しくありません。',
    'new_icon.required' => 'アイコンを入力してください。',
    'new_icon.regex' => 'アイコンは半角英数字と記号で入力してください。',
    'new_icon.min' => 'アイコンは8文字以上64文字以内で入力してください。',
    'new_icon.max' => 'アイコンは8文字以上64文字以内で入力してください。',
    'new_icon_confirmation.required' => 'アイコン（確認用）を入力してください。',
    'new_icon_confirmation.same' => 'アイコンが一致していません。',
]
```

完成形のメソッド全体は `app/Http/Controllers/EditIconController.php` の `update` を参照。

---

## 4. テストケース

前提: ログイン済みで `/edit-icon` を開けること。

### 正常系

- [ ] 正しい現在アイコン + 条件を満たす新しいアイコン（確認一致）→ account へ移動
- [ ] 変更後、新しいアイコンでログインできる
- [ ] 変更後、古いアイコンではログインできない
- [ ] キャンセル → 更新せず account へ

### 異常系

- [ ] 現在アイコンが空 → 「現在のアイコンを入力してください。」
- [ ] 現在アイコンが違う → 「現在のアイコンが正しくありません。」
- [ ] 新しいアイコンが空 → 「アイコンを入力してください。」
- [ ] 使えない文字のみ → 「アイコンは半角英数字と記号で入力してください。」
- [ ] 7文字以下 → 「アイコンは8文字以上64文字以内で入力してください。」
- [ ] 確認用が空 → 確認欄に「アイコン（確認用）を入力してください。」
- [ ] 確認用と不一致 → 確認欄に「アイコンが一致していません。」
- [ ] 英字のみなど（icon ルール未充足）→ 強さに関するエラー

### セキュリティ・画面

- [ ] ログアウト後に `/edit-icon` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）
- [ ] `@csrf` がある（無いと 419）
- [ ] 確認欄の name が `new_icon_confirmation`
