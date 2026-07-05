1.routes/web.phpに以下を追加
```
Route::get('/', function () {
    return view('login');
});

Route::get('/regist', function(){
    return view('regist');
});
```

2.resources/views/login.blade.phpとresources/views/regist.blade.phpを追加
元のPHPは全て削除

3.public/css/style.cssを追加