# 管理者用のメニューを作る
・新規ユーザーはデフォルトでuserになる
・管理者はadminになる
・テストとしてtinkerでadminをユーザーに付与する

## 1.フロント
userのroleがadminの場合のみ表示する
```
@if(Auth::user()->role === 'admin')
<li class="nav-item">
  <a class="nav-link" href="/admin">
    <i class="bi bi-people-fill"></i>
    ユーザー一覧
  </a>
</li>
@endif
```

## 2.バックエンド

### 2-1.マイグレーションファイルを作成する
usersテーブルにroleカラムを追加する
・ 名前を add_（列名）_to_（表名）_table とする
・ テーブル名を指定する --table=users
```
php artisan make:migration add_role_to_users_table --table=users
```

### 2-2.マイグレーションファイルの中身
```
upメソッド
$table->string('role')->default('user'); // デフォルト値をuserに設定

downメソッド
$table->dropColumn('role');
```
・すでにusersに人がいる状態で初期値なしの必須項目を追加する場合は、デフォルト値を設定する

### 2-3.マイグレーションファイルを実行する
```
php artisan migrate
```

## 3.シーダー
tinkerで試す場合
(一度だけ手元で直すなら tinker、何度でも同じ初期データを作り直すならシーダー)

```
php artisan tinker

$user = \App\Models\User::where('email', '自分のメール@example.com')->first();
$user->role = 'admin';
$user->save();
```

一旦これで管理者を作成しておく。
あとでadminユーザーについて考える
・そもそもユーザーの削除は本人で行えば良いのでは？
・adminは作ってみたかっただけなのでedit/sidebarの変更自体不要かも
・もし管理者権限とか付けるならWordPressみたいにする？