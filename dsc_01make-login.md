1.routes/web.phpに以下を追加
```
Route::get('/', function () {
    return view('login');
});

Route::get('/regist', function(){
    return view('regist');
});

Route::get('/dashboard', function() {
    return view('dashboard');
});
```

2.以下のbledeファイルを追加
・ログイン画面：resources/views/auth/login.blade.php
・会員登録画面：resources/views/auth/regist.blade.php
・ダッシュボード：resources/views/dashboard.blade.php
※元のPHPはエラーを出さないために一旦全て削除
※ auth/ は認証フロー専用のディレクトリ。登録やログイン、パスワードリセットなどを入れる

3.コンポーネント（共通パーツ）の作成
参考：https://readouble.com/laravel/12.x/ja/blade.html#components
3-1.views/components/を作成
3-2.sidebar.blede.phpとnavbar.phpを追加
3-3.以下で呼び出し
```
<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

4.public/css/style.cssを追加
```<link href="{{ asset('/css/style')}}" rel="stylesheet">```で呼び出し