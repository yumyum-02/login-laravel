# メールアドレス変更
## 1. メールアドレス変更画面

### 1-1. ルート（`routes/web.php`）
```
// メールアドレス変更画面表示
Route::get('edit-email' , function(){
    $user = Auth::user();
    return view('edit-email',['user' => $user]);
})->name('edit-email')->middleware('auth');
// メールアドレス変更 update
Route::post('edit-email' , [EditEmailController::class, 'update'])->name('update-email')->middleware('auth');
```

### 1-2. アカウント編集画面からメアド編集画面へリンク（`resources/views/account.blade.php`）
```
<a href="{{ route('edit-email') }}">
  <i></i>変更
</a>
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
https://github.com/yumyum-02/login-laravel/commit/acae0926b64da0ad4c6da390dd751d971bb242ba

---

## 3. ユーザー名変更処理（コントローラー）

元ファイル: `public/account-edit/exec_edit-email.php`

#### 3-1.コントローラーに書くこと
・バリデーション（必須、形式、文字数、すでに使われているアドレスか）
・エラー時にedit-emailへ戻す
・メールアドレスのアップデート
・成功時に../admin/account.phpにリダイレクト

---

### 3-2. バリデーション

参考: [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)

ルール:

- 必須
- アドレスの重複
- メールアドレスの形式
- 255文字以内

元 PHP のメールアドレスの形式:

```text
return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
```

Laravel 版では emailでOK
https://readouble.com/laravel/12.x/ja/validation.html#rule-email
※email：RFCに沿った判定をする
※元のPHPと一緒なのはemail:filterだが、Laravelらしい書き方ならemailで十分

重複の確認
https://readouble.com/laravel/12.x/ja/validation.html#rule-unique
'unique:users,email,' . $request->user()->id

```php
public function update(Request $request): RedirectResponse
{
    // バリデーション
    $validated = $request->validate(
        [
            'email' => ['required','email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ],
        [
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が不正です。',
            'email.max' => 'メールアドレスは255文字以内です。',
            'email.unique' => 'そのメールアドレスはすでに使用されています。',
        ]
    );
}
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
- `$validated['name']` … チェック済みの値（`$request->name` より意図がはっきりする）

---

### 3-4. エラー表示

#### Blade

`email` のエラーをすべて出す。

```blade
@error('email')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('email') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

### 3-5. フォームのリンク先修正
```
<form action="{{ route('update-email') }}" method="post">
@csrf
```

### 対象コミット

- バリデーション、更新、エラーメッセージ表示：https://github.com/yumyum-02/login-laravel/commit/921ed1eeb7da86a53efd571c49fb0157b9a74f6a

---

## 4. テストケース