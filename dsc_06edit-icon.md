# アイコン変更実装メモ

アイコン変更（画面表示・更新処理）の実装メモ。

参考サイト: [Laravel 12.x 日本語ドキュメント（readouble.com）](https://readouble.com/laravel/12.x/ja)

---

## 全体の流れ

同じ `POST` パスに upload と update は置けない。upload 用と確定用は **別のパス** にする。

```
GET  /edit-icon         → 画面表示（name: edit-icon）
POST /edit-icon/upload  → upload（name: upload）
POST /edit-icon         → update（name: update-icon）
POST /edit-icon/reset   → reset（name: reset-icon）
POST /edit-icon/cancel  → cancel（name: cancel-icon）
```

| 画面 / 処理 | URL | 名前 | 元 PHP |
|-------------|-----|------|--------|
| アイコン変更（表示） | `GET /edit-icon` | `edit-icon` | `edit-icon.php` |
| アップロード（一時保存） | `POST /edit-icon/upload` | `upload` | `exec_icon_upload.php` |
| 変更を保存 | `POST /edit-icon` | `update-icon` | `exec_edit-icon.php` |
| デフォルトに戻す | `POST /edit-icon/reset` | `reset-icon` | `exec_icon_reset.php` |
| キャンセル | `POST /edit-icon/cancel` | `cancel-icon` | `exec_icon_cancel.php` |

アカウント情報画面（`/account`）の「変更」リンクから `edit-icon` へ遷移する。

---

## 0.アイコンの表示
一旦デフォルトのアイコンをヘッダーとアカウント情報ページに表示
acconut.blede.phpとnavbar.blede.phpに以下
```
<img src="{{ asset('images/icon/default-icon.png')}}">
```

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

アップロード用フォームは一時保存のルート名 `upload`。`enctype="multipart/form-data"` と `@csrf` が必要。

```blade
<form action="{{ route('upload') }}" method="post" enctype="multipart/form-data" id="uploadForm">
  @csrf
  ...
</form>
```

確定は `route('update-icon')`、リセットは `route('reset-icon')`、キャンセルは `route('cancel-icon')`。

ここまでの変更：https://github.com/yumyum-02/login-laravel/commit/2cc308dd398068144fb701fd34095192836ebe4e

### 2-3. コントローラーの作成

```bash
php artisan make:controller EditIconController
```

```php
use App\Http\Controllers\EditIconController;

Route::post('edit-icon/upload', [EditIconController::class, 'upload'])
    ->name('upload')
    ->middleware('auth');

Route::post('edit-icon', [EditIconController::class, 'update'])
    ->name('update-icon')
    ->middleware('auth');
```

- 先頭で `use App\Http\Controllers\EditIconController;` を忘れない
- Blade の `route('upload')` と `->name('upload')` を揃える
- upload と update は別パス（同じ `POST edit-icon` には置けない）

### 対象コミット

- コントローラー用意: https://github.com/yumyum-02/login-laravel/commit/b727069811fa0e6f531e9136e18507f9cfd4f0fa

---

## 3. アイコン変更処理（コントローラー）

### 3-1. 元 PHP との対応

元システムは処理ごとに PHP ファイルが分かれていた。Laravel では `EditIconController` のメソッドに対応する。
`src/functions/icon.php` / `icon-file.php` のファイル操作は、コントローラから [ファイルストレージ](https://readouble.com/laravel/12.x/ja/filesystem.html) を使う。

| 元 PHP | 役割 | Laravel |
|--------|------|---------|
| `edit-icon.php` | 画面表示 | `edit` |
| `exec_icon_upload.php` | 選択画像を一時保存してプレビュー | `upload` |
| `exec_icon_reset.php` | デフォルトに戻す | `reset` |
| `exec_icon_cancel.php` | 一時ファイルを捨ててアカウントへ戻る | `cancel` |
| `exec_edit-icon.php` | 一時ファイルを本番にして DB 更新 | `update` |

注意: PHP の `$_FILES['icon']['tmp_name']`（アップロード直後の OS 側の一時ファイル）と、元システムが作る `{id}_temp.jpg`（プレビュー用に自分で保存したファイル）は別物。Laravel では前者は `$request->file('icon')`、後者のファイル名は `session('temp_icon')` で覚える。

#### upload（`exec_icon_upload.php`）

元の流れ:

1. アップされたファイルを取得（`$_FILES['icon']`）
2. `getIconValidationErrors` でチェック
   - 画像がアップロードされているか
   - アップロードされたファイルが本当に画像か
   - 画像のアップロードに成功したか
   - ファイルサイズが 1MB 以下か
   - MIME タイプが PNG または JPEG か
   - 幅と高さが 400px × 400px 以下か
3. エラーがあればアイコン編集画面に戻る（Laravelは自動）
4. 問題なければ `saveTempIcon` で `{id}_temp.拡張子` として保存
5. `$_SESSION['temp_icon']` にそのファイル名を保存
6. アイコン編集画面へリダイレクト（プレビュー表示）
7. 一時保存に失敗したら「ファイルの保存に失敗しました」

| 元 PHP | Laravel |
|--------|---------|
| `$_FILES['icon']` | `$request->file('icon')` |
| 画像がアップロードされているか | `required` |
| 本当に画像か / MIME（PNG, JPEG） | `image` + `mimes:jpeg,png` |
| アップロード成功か（`error !== UPLOAD_ERR_OK`） | 失敗時は Laravel がエラーにするので自前チェックは不要 |
| 1MB 以下 | `max:1024`（単位はキロバイト） |
| 400px × 400px 以下 | `dimensions:max_width=400,max_height=400` |
| エラー時 `redirectWithErrors` | `validate` 失敗で自動的に元の画面へ戻る |
| `saveTempIcon`（`icon.php`） | 古い `{id}_temp.*` を消してから `storeAs('icons', '{id}_temp.{拡張子}')`。ブラウザから見える場所に置く |
| `$_SESSION['temp_icon']` | `$request->session()->put('temp_icon', $path)`（`$path` には `icons/` も入る） |
| `redirect('./edit-icon.php')` | `redirect()->route('edit-icon')` |
| 「ファイルの保存に失敗しました」 | 例外は Laravel のエラー画面に任せる |
| 戻った画面で仮画像を表示 | セッションに `temp_icon` があればそのパス、なければ DB のアイコン |

参考:

- [バリデーション — ファイル](https://readouble.com/laravel/12.x/ja/validation.html#validating-files)
- [dimensions](https://readouble.com/laravel/12.x/ja/validation.html#rule-dimensions)
- バリデーションエラー表示 https://readouble.com/laravel/12.x/ja/validation.html#quick-displaying-the-validation-errors
バリデーションエラーの場合は自動で元の画面に戻すので明示不要
- ファイルのアップロード（ファイル名の指定,ファイルパスと拡張子） https://readouble.com/laravel/12.x/ja/filesystem.html#file-uploads

#### reset（`exec_icon_reset.php`）

元の流れ:

1. プレビュー用ファイル（`{id}_temp.jpg` など）を削除（`deleteTempIconFile` / `deleteAllIconFiles`）
2. 本番アイコンを削除し、DB の `icon` を `NULL` にする（`resetIcon`）
3. `$_SESSION['user']['icon']` を更新し、`temp_icon` を消す
4. 成功したらアカウント情報画面へ
5. 失敗したらアイコン編集画面へ「アイコンのリセットに失敗しました」

| 元 PHP | Laravel |
|--------|---------|
| `deleteTempIconFile` / `deleteAllIconFiles` | `Storage::delete(...)` で一時・本番ファイルを消す |
| `resetIcon`（ファイル削除 + DB を NULL） | ファイル削除のあと `$request->user()->update(['icon' => null])` |
| `$_SESSION['user']['icon'] = null` | 不要。次回リクエストで DB から読み直す |
| `unset($_SESSION['temp_icon'])` | `$request->session()->forget('temp_icon')` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |
| 「アイコンのリセットに失敗しました」 | `back()->withErrors([...])` |

参考:

- [Eloquent — 更新](https://readouble.com/laravel/12.x/ja/eloquent.html#updates)
- [セッション — データの削除](https://readouble.com/laravel/12.x/ja/session.html#deleting-data)
- [リダイレクト](https://readouble.com/laravel/12.x/ja/responses.html#redirects)

#### cancel（`exec_icon_cancel.php`）

元の流れ:

1. セッションに一時ファイル名があれば、そのファイルを削除
2. セッションの一時ファイル名を削除
3. `/account` へリダイレクト（本番のアイコンは変えない）

| 元 PHP | Laravel |
|--------|---------|
| `!empty($_SESSION['temp_icon'])` なら `deleteTempIconFile` | セッションに `temp_icon` があれば `Storage::delete(...)` |
| `unset($_SESSION['temp_icon'])` | `$request->session()->forget('temp_icon')` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |

#### update（`exec_edit-icon.php`）

元の流れ:

1. セッションに一時ファイル名（`temp_icon`）があるかチェック
2. なければアイコン編集画面へ「画像がアップロードされていません」
3. あれば `confirmIcon` で一時ファイルを本番ファイルに変換（例: `123_temp.jpg` → `123.jpg`）
4. DB の `icon` をファイル名で更新
5. セッションのユーザー情報を更新し、`temp_icon` を消す
6. `/account` へリダイレクト
7. 失敗したらアイコン編集画面へ「アイコンの更新に失敗しました」

| 元 PHP | Laravel |
|--------|---------|
| `empty($_SESSION['temp_icon'])` | `!$request->session()->has('temp_icon')` |
| 「画像がアップロードされていません」 | `back()->withErrors([...])` |
| `confirmIcon`（リネームして本番化） | `Storage` で一時ファイルを本番パスへ移動 |
| `updateUserIcon` | `$request->user()->update(['icon' => $filename])` |
| `$_SESSION['user']['icon'] = $filename` | 不要。DB が正になる |
| `unset($_SESSION['temp_icon'])` | `$request->session()->forget('temp_icon')` |
| `redirect('../admin/account.php')` | `redirect()->route('account')` |
| 「アイコンの更新に失敗しました」 | `back()->withErrors([...])` |

参考:

- [セッション — データの取得](https://readouble.com/laravel/12.x/ja/session.html#retrieving-data)
- [認証済みユーザーの取得](https://readouble.com/laravel/12.x/ja/authentication.html#retrieving-the-authenticated-user)
- [Eloquent — 更新](https://readouble.com/laravel/12.x/ja/eloquent.html#updates)

#### コントローラーに書かなくてよいこと

| 元 PHP | Laravel での担当 |
|--------|------------------|
| `requireLogin(...)` | ルートの `middleware('auth')` |
| CSRF 検証 | Blade の `@csrf` |
| try-catch（システム / DB エラー） | Laravel の例外処理に任せる（今回は自作しない） |

---

### 3-2. バリデーション（upload で使う）

元 PHP の `getIconValidationErrors` は `upload` メソッドの `$request->validate()` にまとめる。パスワード変更のような「現在のアイコン」「確認用」欄はない。

参考:

- [バリデーション](https://readouble.com/laravel/12.x/ja/validation.html)
- [バリデーション — ファイル](https://readouble.com/laravel/12.x/ja/validation.html#validating-files)
- [mimes](https://readouble.com/laravel/12.x/ja/validation.html#rule-mimes)
- [max](https://readouble.com/laravel/12.x/ja/validation.html#rule-max)
- [dimensions](https://readouble.com/laravel/12.x/ja/validation.html#rule-dimensions)
- [エラーメッセージのカスタマイズ](https://readouble.com/laravel/12.x/ja/validation.html#customizing-the-error-messages)

```php
$validated = $request->validate(
    [
        'icon' => [
            'required',
            'image',
            'mimes:jpeg,png',
            'max:1024',
            'dimensions:max_width=400,max_height=400',
        ],
    ],
    [
        'icon.required' => '画像がアップロードされていません',
        'icon.mimes' => 'PNG または JPEG 形式の画像をアップロードしてください',
        'icon.max' => '容量は1MB以下の画像をアップロードしてください',
        'icon.dimensions' => '画像サイズは400px × 400px以下にしてください',
    ]
);
```

- `max:1024` の単位はキロバイト（1MB = 1024KB）
- 入力チェックに失敗すると、Laravel が自動でアイコン編集画面へ戻す
- メッセージのキーはルール名と揃える。ルールが `mimes` なら `icon.mimes`。`icon.mimetypes` だとカスタム文は出ない

---

### 3-3. ファイル保存・DB 更新・セッション

元 PHP の `saveTempIcon` / `confirmIcon` / `resetIcon` は [ファイルストレージ](https://readouble.com/laravel/12.x/ja/filesystem.html) と Eloquent の更新に置き換える。

| やりたいこと | Laravel |
|--------------|---------|
| プレビュー用に保存 | `Storage` で `{id}_temp.拡張子` を保存し、ファイル名を `session('temp_icon')` に入れる |
| 本番として確定 | 一時ファイルを `{id}.拡張子` に移し、`$request->user()->update(['icon' => $filename])` |
| デフォルトに戻す | ファイルを消し、`icon` を `null` にする |
| キャンセル | 一時ファイルと `temp_icon` だけ消す（DB は触らない） |

`$_SESSION['user']['icon']` の手更新と `session_regenerate` はアイコン変更では不要。ログイン中ユーザーは `$request->user()` で取り、次回表示は DB の `icon` を見る。

---

### 3-4. エラー表示

参考: [エラー表示（@error）](https://readouble.com/laravel/12.x/ja/validation.html#the-at-error-directive)

```blade
@error('icon')
  <div class="text-danger">{{ $message }}</div>
@enderror
```

「画像がアップロードされていません」「アイコンの更新に失敗しました」など、`validate` 以外のメッセージは `back()->withErrors(['icon' => '...'])` で渡し、同じ `@error('icon')` で出せる。

---

## 4. テストケース

前提: ログイン済みで `/edit-icon` を開けること。

### 正常系

- [ ] PNG/JPEG・1MB以下・400px以下を選ぶ → プレビューにその画像が出る
- [ ] プレビュー後「変更を保存」→ account へ。ヘッダーとアカウント情報のアイコンが変わる
- [ ] 「キャンセル」→ 本番アイコンは変わらず account へ。一時ファイルは残らない
- [ ] 「デフォルトに戻す」→ account へ。デフォルトアイコンになる

### 異常系

- [ ] ファイルを選ばずに保存 → 「画像がアップロードされていません」
- [ ] PNG/JPEG 以外 → 「PNG または JPEG 形式の画像をアップロードしてください」
- [ ] 1MB 超 → 「容量は1MB以下の画像をアップロードしてください」
- [ ] 401px 以上 → 「画像サイズは400px × 400px以下にしてください」

### セキュリティ・画面

- [ ] ログアウト後に `/edit-icon` → ログイン画面へ
- [ ] ログアウト後に POST のみ → ログイン画面へ
- [ ] 各フォームに `@csrf` がある（無いと 419）
- [ ] アップロード用フォームに `enctype="multipart/form-data"` がある
