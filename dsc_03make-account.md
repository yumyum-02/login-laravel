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