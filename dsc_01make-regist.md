# 会員登録・ログイン・ダッシュボード実装メモ

会員登録、ログイン、ダッシュボード画面への遷移を実装する流れをまとめたもの。

参考サイト：[Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 1. フロント準備

### 1-1. ルート定義（`routes/web.php`）

```php
Route::get('/', function () {
    return view('auth/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});
```

会員登録のルートは後述のコントローラー実装時に追加する。

### 1-2. Blade ファイルの追加

| 画面 | パス |
|------|------|
| ログイン | `resources/views/auth/login.blade.php` |
| 会員登録 | `resources/views/auth/regist.blade.php` |
| ダッシュボード | `resources/views/dashboard.blade.php` |

- 元の PHP コードはエラーを出さないよう一旦すべて削除
- `auth/` は認証フロー専用ディレクトリ（登録・ログイン・パスワードリセットなど）

### 1-3. コンポーネント（共通パーツ）の作成

参考：[Blade コンポーネント](https://readouble.com/laravel/12.x/ja/blade.html#components)

1. `resources/views/components/` を作成
2. `sidebar.blade.php` と `navbar.blade.php` を追加
3. 各画面から呼び出し

```blade
<x-navbar></x-navbar>
<x-sidebar></x-sidebar>
```

### 1-4. CSS の追加

`public/css/style.css` を追加し、各画面の `<head>` から読み込む。

```blade
<link rel="stylesheet" href="{{ asset('/css/style.css') }}">
```

### 対象コミット

- ログイン・登録画面・CSS：[c876586](https://github.com/yumyum-02/login-laravel/commit/c876586f2f650f389a0cbb4696847412b3ab6646)
- ダッシュボード画面と共通パーツ：[5abc219](https://github.com/yumyum-02/login-laravel/commit/5abc2199300eb8ab9b04132df64d642b5a473bc2)

---

## 2. 会員登録実装

### 全体の流れ

```
GET  /regist  → RegisterController@create  → 登録フォーム表示
POST /regist  → RegisterController@store   → バリデーション → DB保存 → ログイン画面へリダイレクト
```

### 元システムからの改善点

元システム（[yumyum-02/login](https://github.com/yumyum-02/login)）は素の PHP で `exec_register.php`・`functions/validation.php` などに処理が分散していた。Laravel 版では以下の点を改善した。

#### アーキテクチャ・保守性

| 項目 | 元システム | Laravel 版 |
|------|-----------|------------|
| 処理の分離 | `exec_register.php` に表示・検証・DB保存が混在 | ルート → コントローラー → モデルに分離 |
| DB 操作 | PDO + 手書き SQL | Eloquent ORM（`User::create()`） |
| 画面 | `regist_template.php` に HTML 直書き | Blade テンプレート |
| 共通パーツ | `include` で読み込み | Blade コンポーネント（`<x-navbar>` など） |
| ルーティング | ファイル名で直接アクセス | `routes/web.php` で一元管理 |

#### セキュリティ

| 項目 | 元システム | Laravel 版 |
|------|-----------|------------|
| CSRF | `<input type="hidden" name="csrf_token">` を手動実装 | `@csrf` + ミドルウェアが自動検証 |
| XSS | `functions/sanitize.php` で手動エスケープ | Blade の `{{ }}` で自動エスケープ |
| パスワード保存 | `password_hash()` を呼び忘れるリスクあり | User モデルの `hashed` cast で自動ハッシュ化 |
| SQL インジェクション | プリペアドステートメントで対応 | Eloquent によりクエリ組み立てをフレームワークに委譲 |

#### バリデーション・UX

| 項目 | 元システム | Laravel 版 |
|------|-----------|------------|
| ルール定義 | `functions/validation.php` に関数で記述 | コントローラーで宣言的に記述（`required`, `regex` など） |
| エラー処理 | `functions/validation-error.php` で手動振り分け | バリデーション失敗時に自動で元画面へ戻る |
| 入力値の保持 | `old_input['name']` を手動で渡す | `old('name')` ヘルパーで自動復元 |
| 完了メッセージ | `functions/session-message.php` | `redirect()->with('message', ...)` + `session('message')` |
| エラーメッセージ | 各関数内にハードコード | `lang/ja/validation.php` に集約（変更・再利用が容易） |

#### URL・リンク

| 項目 | 元システム | Laravel 版 |
|------|-----------|------------|
| フォーム送信先 | `./exec_register.php`（相対パス） | `/regist`（ルートに対応） |
| 画面間リンク | `./regist` など相対パス | `url('/regist')` ヘルパー（環境やサブディレクトリに依存しにくい） |

#### 仕様の見直し（意図的な変更）

元システムの仕様をそのまま移植するのではなく、DB 定義やセキュリティの観点から一部を見直した。

| 項目 | 元システム | Laravel 版 | 変更理由 |
|------|-----------|------------|----------|
| ユーザー名の使用可能文字 | `_`（アンダースコア）・`-`（ハイフン）可 | スペース可 | 表示名として自然な入力を許可 |
| メール最大長 | 320 文字 | 255 文字 | DB カラム（`VARCHAR(255)`）と Laravel 標準に合わせる |
| パスワード最小長 | 16 文字 | 8 文字 | 使いやすさとのバランス（下記の強度チェックで補完） |
| パスワード強度 | 半角英数字・記号の形式チェックのみ | 形式チェック + **大文字・小文字・数字・記号を各 1 文字以上** | `Password` ルールでより強固なパスワードを要求 |

### 2-1. フォームの CSRF 対策

参考：[CSRF 保護](https://readouble.com/laravel/12.x/ja/csrf.html)

`resources/views/auth/regist.blade.php` のフォームに `@csrf` を追加する。

```blade
<form action="/regist" method="post">
    @csrf
    ...
</form>
```

旧 PHP 版の `<input type="hidden" name="csrf_token">` は不要になる。

### 2-2. コントローラーの作成

参考：[コントローラー](https://readouble.com/laravel/12.x/ja/controllers.html)

`web.php` の Route クロージャに直接処理を書かず、コントローラーにまとめる。

```bash
php artisan make:controller RegisterController
```

`app/Http/Controllers/RegisterController.php` に以下を実装する。

```php
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// フォーム表示
public function create(): View
{
    return view('auth.regist');
}

// フォーム送信処理
public function store(Request $request): RedirectResponse
{
    // バリデーション
    $validated = $request->validate([
        'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
        'email' => ['required', 'email', 'max:255', 'string'],
        'password' => [
            'bail',
            'required', 'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/', 'min:8', 'max:64',
            Password::min(8)
                ->letters()      // 英字を1文字以上
                ->mixedCase()    // 大文字・小文字を両方
                ->numbers()      // 数字を1文字以上
                ->symbols(),     // 記号を1文字以上
        ],
    ]);

    // メールアドレスを小文字化して重複チェック
    $email = mb_strtolower($validated['email'], 'UTF-8');
    if (User::where('email', $email)->exists()) {
        throw ValidationException::withMessages([
            'email' => 'そのメールアドレスはすでに使用されています。',
        ]);
    }

    // ユーザー登録
    User::create([
        'name' => $validated['name'],
        'email' => $email,
        'password' => $validated['password'],
    ]);

    return redirect('/')->with('message', '会員登録が完了しました。ログインしてください。');
}
```

**ポイント**

- `create()` のビュー名は `auth.regist`（`auth/` ディレクトリ配下のため）
- メール重複チェックは `unique` ルールではなく、`mb_strtolower` 後に手動で行う（大文字・小文字を区別しないため）
- パスワードのハッシュ化は `Hash::make()` ではなく、User モデルの `casts` で自動処理（後述）
- パスワード強度は元システムより厳格化。Laravel の `Password` ルールで大文字・小文字・数字・記号を各 1 文字以上要求する

### 2-3. ルートの修正（`routes/web.php`）

参考：[クイックスタート - ルート定義](https://readouble.com/laravel/12.x/ja/validation.html#quick-defining-the-routes)

```php
use App\Http\Controllers\RegisterController;

Route::get('/regist', [RegisterController::class, 'create']);
Route::post('/regist', [RegisterController::class, 'store']);
```

### 2-4. バリデーションルール

参考：[バリデーション](https://readouble.com/laravel/12.x/ja/validation.html) / [利用可能なルール一覧](https://readouble.com/laravel/12.x/ja/validation.html#available-validation-rules)

元システムの仕様と、今回の Laravel 実装の対応関係。

#### ユーザー名（`name`）

| チェック | エラーメッセージ | 実装 | 元システムとの違い |
|----------|------------------|------|-------------------|
| 必須 | ユーザー名を入力してください。 | `required` | 同じ |
| 形式 | 使用できない文字が含まれています。 | `regex` | 元は `_`・`-` 可 → Laravel 版は**スペース**可 |
| 長さ | ユーザー名は3文字以上16文字以内で入力してください。 | `min:3`, `max:16` | 同じ |

使用可能文字：半角英数字（a-z, A-Z, 0-9）、スペース、日本語（ひらがな・カタカナ・漢字）

#### メールアドレス（`email`）

| チェック | エラーメッセージ | 実装 | 元システムとの違い |
|----------|------------------|------|-------------------|
| 必須 | メールアドレスを入力してください。 | `required` | 同じ |
| 形式 | メールアドレスの形式が正しくありません。 | `email` | 同じ |
| 長さ | メールアドレスは255文字以内で入力してください。 | `max:255` | 元は **320文字** → **255文字**に変更 |
| 正規化 | — | `mb_strtolower()` で小文字化 | 同じ |
| 重複 | そのメールアドレスはすでに使用されています。 | `User::where()->exists()` + `ValidationException` | 同じ（小文字化後に照合） |

#### パスワード（`password`）

| チェック | エラーメッセージ | 実装 | 元システムとの違い |
|----------|------------------|------|-------------------|
| 必須 | パスワードを入力してください。 | `required` | 同じ |
| 形式 | パスワードは半角英数字と記号で入力してください。 | `regex` | 元は ASCII 全般可、Laravel 版は記号を限定 |
| 長さ | 8文字以上64文字以内 | `min:8`, `max:64` | 元は **16文字以上** → **8文字以上**に緩和 |
| 大文字・小文字 | パスワードは大文字と小文字を両方含めてください。 | `Password::mixedCase()` | **新規追加**（元システムになし） |
| 数字 | パスワードは数字を1文字以上含めてください。 | `Password::numbers()` | **新規追加**（元システムになし） |
| 記号 | パスワードは記号を1文字以上含めてください。 | `Password::symbols()` | **新規追加**（元システムになし） |
| トリミング | — | しない | 同じ（空白も有効な文字として扱う） |

使用可能記号：`! @ # $ % ^ & * ( ) - _ + =`

`bail` を付けているため、最初のエラーで残りのルールチェックを打ち切り、エラーメッセージを1件ずつ表示する。

### 2-5. バリデーションメッセージの日本語化（`lang/ja/validation.php`）

Laravel 標準の `:attribute` 置換メッセージではなく、元システムと同じ文言にするため `custom` セクションに追加する。

```php
'custom' => [
    'name' => [
        'required' => 'ユーザー名を入力してください。',
        'regex' => '使用できない文字が含まれています。',
        'min' => 'ユーザー名は3文字以上16文字以内で入力してください。',
        'max' => 'ユーザー名は3文字以上16文字以内で入力してください。',
    ],
    'email' => [
        'required' => 'メールアドレスを入力してください。',
        'email' => 'メールアドレスの形式が正しくありません。',
        'max' => 'メールアドレスは255文字以内で入力してください。',
    ],
    'password' => [
        'required' => 'パスワードを入力してください。',
        'regex' => 'パスワードは半角英数字と記号で入力してください。',
        'mixed' => 'パスワードは大文字と小文字を両方含めてください。',
        'numbers' => 'パスワードは数字を1文字以上含めてください。',
        'symbols' => 'パスワードは記号を1文字以上含めてください。',
    ],
],
```

`attributes` セクションで `name` の表示名も変更する。

```php
'name' => 'ユーザー名',  // デフォルトの「名前」から変更
```

### 2-6. パスワードのハッシュ化

参考：[ハッシュ化](https://readouble.com/laravel/12.x/ja/hashing.html)

`User` モデルの `casts` に `'password' => 'hashed'` を設定しているため、`User::create()` 時に平文を渡すだけで自動的に bcrypt ハッシュ化される。コントローラーで `Hash::make()` を呼ぶ必要はない。

```php
// app/Models/User.php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

### 2-7. 会員登録画面のエラー表示（`regist.blade.php`）

参考：[@error ディレクティブ](https://readouble.com/laravel/12.x/ja/validation.html#the-at-error-directive) / [フォームへの再入力](https://readouble.com/laravel/12.x/ja/validation.html#repopulating-forms)

#### エラー時の入力値保持

```blade
value="{{ old('name') }}"
value="{{ old('email') }}"
```

パスワードはセキュリティのため `old()` で再表示しない。

#### エラーメッセージと入力欄のスタイル

`@error` ディレクティブの代わりに `$errors->has()` を使った実装。

```blade
<input type="text"
       class="form-control @if ($errors->has('name')) is-invalid @endif"
       name="name"
       value="{{ old('name') }}">

@if ($errors->has('name'))
<div class="invalid-feedback d-block">
    @foreach ($errors->get('name') as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif
```

`email`・`password` も同様のパターンで実装する。

#### バリデーションツールチップの文言修正

画面内のヒント表示も実装に合わせて更新した。

| 項目 | 元システム（画面ヒント） | Laravel 版 |
|------|------------------------|------------|
| ユーザー名 | 3〜32文字 | 3〜16文字 |
| メール | 320文字以内 | 255文字以内 |
| パスワード | 16〜64文字 | 8〜64文字 + 大文字・小文字・数字・記号を各1文字以上 |

#### リンクの修正

旧 PHP 版の相対パスを Laravel の `url()` ヘルパーに変更。

```blade
<a href="{{ url('/') }}">← ログイン画面へ戻る</a>
```

### 2-8. 登録完了メッセージの表示（`login.blade.php`）

参考：[セッション - データの取得](https://readouble.com/laravel/12.x/ja/session.html#retrieving-data) / [リダイレクト](https://readouble.com/laravel/12.x/ja/responses.html#redirects)

コントローラーの `redirect('/')->with('message', '...')` により、ログイン画面で成功メッセージを表示する。

```blade
@if (session('message'))
<div class="alert alert-success" role="alert">{{ session('message') }}</div>
@endif
```

会員登録画面へのリンクも修正。

```blade
<a href="{{ url('/regist') }}">会員登録はこちら →</a>
```

---

## 3. 認証（未実装）

参考：[認証](https://readouble.com/laravel/12.x/ja/authentication.html)

次のステップとして、ログイン処理・セッション管理・ダッシュボードへの認証ガードを実装する。

---

## おまけ：Form Request によるバリデーション分離

参考：[フォームリクエスト](https://readouble.com/laravel/12.x/ja/requests.html)

コントローラーに直接バリデーションを書いても動作するが、練習として専用クラスに分離することもできる。

```bash
php artisan make:request RegisterRequest
```

以下の場合は Form Request に分離しなくてもよい。

1. バリデーションがシンプル（3〜5項目程度、標準ルールのみ）
2. 1箇所でしか使わない（その画面専用のバリデーション）

今回の会員登録は上記に該当するため、コントローラー内に直接記述している。
