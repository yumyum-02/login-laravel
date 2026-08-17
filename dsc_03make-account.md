アカウント情報画面と、それぞれの変更ボタンを押したら変更画面が開くようにする

1.web.phpでアカウント情報画面の表示
```
Route::get('account' , function() {
    return view('account');
})->middleware('auth');
```

2.account.blade.php
2-1.CSSと共通パーツ
```
<link href="{{ asset('/css/style.css')}}" rel="stylesheet">

<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

2-2.ユーザー名の表示
参考：
https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user
https://readouble.com/laravel/12.x/ja/blade.html#displaying-data
web.php
```
use Illuminate\Support\Facades\Auth;

Route::get('account' , function() {
    $user = Auth::user();
    return view('account',['user' => $user]);
})->middleware('auth');
```
ここまでのコミット：https://github.com/yumyum-02/login-laravel/commit/77fd9178b9960405d4a2511c18c4fbc3adee57ab

---

3.ユーザー名変更
3-1.web.phpでユーザー名変更画面を表示する
元のphpではedit-profileというファイル名だが、わかりづらいので今回はedit-usernameに変更
```
Route::get('edit-username' , function(){
    $user = Auth::user();
    return view('edit-username',['user' => $user]);
})->name('edit-username')->middleware('auth');
```

3-2.edit-username.blade.php
```
// インプットに現在のユーザーネームか、エラー前のユーザー名を表示
<input value="{{ old('name') ?? $user->name}}">

// エラーメッセージの表示
@error('name')
  <div class="invalid-feedback d-block">
    @foreach ($errors as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

# 3-3.変更処理のコントローラーを用意
```
php artisan make:controller EditUsernameController
```
ここまでのコミット：https://github.com/yumyum-02/login-laravel/commit/77e303644f40abf1d2a39609fb7fc1c85c50483b

# 3-4.コントローラーに処理を記述
元phpファイル public/account-edit/exec_edit-profile.php
### 元phpでやっていること
今回対応が必要なこと（コントローラーに書く）
- バリデーションエラー getUserNameValidationErrors($name)
  - $request->validate([...]) で対応
  - バリデーションエラー発生で更新処理を中止してedit-usernameページに戻るのはLaravelが自動でやるので不要
- 更新成功時の処理
  - DB更新、成功時にaccountページに移動

今回対応不要なこと（Laravel / 他ファイルが担当）
- ログインチェック（未ログインの場合はloginページにリダイレクト）
  - 今回はweb.phpの->middleware('auth')で対応
- CSRFトークン
  - 今回は@csrfで対応
- 前後の空白削除 getTrimmedPostValue('name')
  - Laravel は入力値の前後空白を自動で削除
- try catch（一部）
  以下のため今回は不要
  - Laravelではバリデーションエラーの時点で更新を止め、edit-username 画面に戻すことができる。old('name') と @error('name') を使えるようにすることもできるため
  - システムやDBエラー時は Laravel の例外処理が起こるため自作が不要

## 3-4-1 ユーザー名変更：バリデーションの追加
参考：https://readouble.com/laravel/12.x/ja/validation.html
今回は一旦バリデーションの共通化はなしで進める

nameのバリデーション
- 必須、使用できない文字、3文字以上16字以内
文字はpreg_match('/^[a-zA-Z0-9 \x{3041}-\x{3096}\x{30A1}-\x{30FC}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]+$/u')
```
public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
        ]);
    }
```

## 3-4-2 ユーザー名変更：名前の更新

$request->user()で認証済みのユーザーを取得
https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user

->updateで情報の更新
https://readouble.com/laravel/12.x/ja/eloquent.html#updates

ここまでの変更：https://github.com/yumyum-02/login-laravel/commit/6f86c6aa72574675382ac518df3e80e33fc0c8c7


バリデーションに通った値（$validated）のみ更新に使う
参照：https://readouble.com/laravel/12.x/ja/validation.html#working-with-validated-input

```
$validated = $request->validate([
    'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
]);

// ユーザー名更新
$request->user()->update([
    'name' => $validated['name'],
]);
```

## 3-4-3 エラーの表示
```
@error('name')
  <div class="invalid-feedback d-block">
    @foreach ($errors->get('name') as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@enderror
```

エラーメッセージの日本語定義
参考：https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages

```
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