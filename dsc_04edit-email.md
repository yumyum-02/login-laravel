# メールアドレス変更
## 1. メールアドレス変更画面

### 1-1. ルート（`routes/web.php`）
```
Route::post('edit-email' , [EditEmailController::class, 'update'])->name('update-email')->middleware('auth');
```

### 2-2. Blade（`resources/views/edit-email.blade.php`）

ヘッダーとサイドバー
```
<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

入力欄には「エラー前の入力」または「現在のメールアドレス」を出す。

```blade
<input value="{{ old('email') ?? $user->email }}">
```

フォームの送信先は **更新用ルート名** にする

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

### 対象コミット


---

## 3. ユーザー名変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-profile.php`（[yumyum-02/login](https://github.com/yumyum-02/login)）

### 3-1. 元 PHP との対応

#### コントローラーに書くこと

| 元 PHP | Laravel |
|--------|---------|
| `getUserNameValidationErrors($name)` | `$request->validate([...])` |
| エラー時に edit-profile へ戻す | `validate()` 失敗時に Laravel が自動で戻す |
| `updateUser(...)` | `$request->user()->update([...])` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |

#### コントローラーに書かなくてよいこと

| 元 PHP | Laravel での担当 |
|--------|------------------|
| `requireLogin(...)` | ルートの `middleware('auth')` |
| CSRF 検証 | Blade の `@csrf` |
| `getTrimmedPostValue('name')` | Laravel が入力の前後空白を自動削除 |
| セッションの `name` 更新 | 不要（DB 更新後、表示時に最新を読む） |
| try-catch（システム / DB エラー） | Laravel の例外処理に任せる（今回は自作しない） |

バリデーションの共通化（Form Request など）は、今回は行わない。メール・パスワード変更を足してから検討する。

---

### 3-2. バリデーション

参考: [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)

ルール:

- 必須
- 使用可能文字: 半角英数字・スペース・ひらがな・カタカナ・漢字
- 3文字以上 16文字以内

元 PHP の正規表現:

```text
/^[a-zA-Z0-9 \x{3041}-\x{3096}\x{30A1}-\x{30FC}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]+$/u
```

Laravel 版では Unicode プロパティで同じ意味に書いている。

```php
'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
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
    'name' => $validated['name'],
]);

return redirect()->route('account');
```

- `$request->user()` … ログイン中ユーザー（= 元の `$_SESSION['user']`）
- `$validated['name']` … チェック済みの値（`$request->name` より意図がはっきりする）

### 対象コミット

- バリデーション・更新: [6f86c6a](https://github.com/yumyum-02/login-laravel/commit/6f86c6aa72574675382ac518df3e80e33fc0c8c7)

---

### 3-4. エラー表示と日本語メッセージ

#### Blade

`name` のエラーをすべて出す。

```blade
@error('name')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('name') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

- `@error('name')` … `name` にエラーがあるときだけ囲む
- `$errors->get('name')` … **`name` だけ**のメッセージ配列（`$errors` 全体ではない）

#### コントローラー（メッセージの日本語化）

参考: [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
$validated = $request->validate(
    [
        'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
    ],
    [
        'name.required' => 'ユーザー名は必須です。',
        'name.regex' => 'ユーザー名は半角英数字、ひらがな、カタカナ、漢字のみ使用できます。',
        'name.min' => 'ユーザー名は3文字以上です。',
        'name.max' => 'ユーザー名は16文字以内です。',
    ]
);
```

完成形のメソッド全体は `app/Http/Controllers/EditUsernameController.php` の `update` を参照。

### 対象コミット

- エラー表示・日本語メッセージ: [c0c6785](https://github.com/yumyum-02/login-laravel/commit/c0c67857ecad2079936c51157a9c58d01a2efad9)

---

## 4. テストケース

前提: ログイン済みで `/edit-username` を開けること。

### 正常系

- [ ] `太郎` で保存 → account に移動し、ユーザー名が `太郎`
- [ ] `user123` で保存 → 成功（英数字）
- [ ] `abc` で保存 → 成功（最小3文字）
- [ ] `1234567890123456` で保存 → 成功（最大16文字）
- [ ] キャンセル → 更新せず account へ

### 異常系

- [ ] （空欄）→ 「ユーザー名は必須です。」
- [ ] `ab` → 「ユーザー名は3文字以上です。」
- [ ] 17文字以上 → 「ユーザー名は16文字以内です。」
- [ ] `user@name` → 「半角英数字、ひらがな、カタカナ、漢字のみ…」
- [ ] エラー後 → 入力欄にさっきの値が残っている

### セキュリティ・画面

- [ ] ログアウト後に `/edit-username` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）
- [ ] 変更後の account / ナビ → 新しい名前が表示される
