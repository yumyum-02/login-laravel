# Feature テスト実装メモ

[dsc_01make-regist.md](./dsc_01make-regist.md) 〜 [dsc_06edit-icon.md](./dsc_06edit-icon.md) のチェックリストを、ブラウザ手動ではなく Laravel の HTTP テストで確認する。

前提: 会員登録〜アイコン変更まで動いていること。

参考:

- [HTTP テスト](https://readouble.com/laravel/12.x/ja/http-tests.html)
- [データベースのテスト](https://readouble.com/laravel/12.x/ja/database-testing.html)
- [ファイルアップロードのテスト](https://readouble.com/laravel/12.x/ja/http-tests.html#testing-file-uploads)
- [ファイルストレージのテスト](https://readouble.com/laravel/12.x/ja/filesystem.html#testing)

---

## 全体の流れ

```
php artisan make:test MdChecklistTest
  → tests/Feature/MdChecklistTest.php にチェック項目を書く
  → php artisan test
```

Feature テストはブラウザを開かず、Laravel に GET / POST を投げて応答を確かめる。CSRF は HTTP テストでは自動で外れる。

テスト中の DB は `phpunit.xml` の sqlite メモリ（`:memory:`）。本番の MAMP / MySQL は触らない。

---

## 1. テストの作成と実行

```bash
php artisan make:test MdChecklistTest
php artisan test
php artisan test --filter=MdChecklistTest
```

| コマンド | 意味 |
|----------|------|
| `php artisan test` | `tests/` 以下を全部実行 |
| `--filter=MdChecklistTest` | このクラスだけ実行 |

---

## 2. 今回使う Laravel の仕組み

参考: [利用可能なアサーション](https://readouble.com/laravel/12.x/ja/http-tests.html#available-assertions) / [認証済みのテスト](https://readouble.com/laravel/12.x/ja/http-tests.html#authentication)

```php
use RefreshDatabase;   // テストごとに migrate → 終わったら消す
$this->actingAs($user); // ログインした状態にする
$this->from('/edit-username')->post(...); // 失敗時の戻り先
assertRedirect(...)
assertSessionHasErrors(['name' => 'ユーザー名は必須です。'])
assertSessionHasInput('name', 'user@name')
Storage::fake('local'); // 本物の storage にファイルを置かない
UploadedFile::fake()->image('icon.png', 100, 100)
```

ロケールは日本語にして、エラー文を `lang/ja` の文言と照合する。

```php
protected function setUp(): void
{
    parent::setUp();
    $this->app->setLocale('ja');
    config(['app.locale' => 'ja']);
}
```

パスワードの正常系は `Passw0rd!`（大文字・小文字・数字・記号、8文字以上）。

---

## 3. テストファイル

実体は `tests/Feature/MdChecklistTest.php`。dsc ごとの対応は次のとおり。

| dsc | メソッド |
|-----|----------|
| [dsc_01make-regist.md](./dsc_01make-regist.md) | `test_register_*` |
| [dsc_02-1make-login.md](./dsc_02-1make-login.md) | `test_registered_user_can_login_*` など |
| [dsc_02-2make-logout.md](./dsc_02-2make-logout.md) | `test_logout_*` |
| [dsc_03edit-accout-username.md](./dsc_03edit-accout-username.md) | `test_username_*` |
| [dsc_04edit-email.md](./dsc_04edit-email.md) | `test_email_*` |
| [dsc_05edit-password.md](./dsc_05edit-password.md) | `test_password_*` |
| [dsc_06edit-icon.md](./dsc_06edit-icon.md) | `test_icon_*` |

書き方の例（ログイン失敗）:

```php
public function test_wrong_password_shows_login_error(): void
{
    $this->makeUser();

    $this->from('/')->post('/', [
        'email' => 'tester@example.com',
        'password' => 'WrongPass1!',
    ])->assertSessionHasErrors([
        'email' => 'ログイン情報が正しくありません。',
    ]);
}
```

アイコンは仮ファイルを `Storage::fake('local')` に置く。保存後に GET `/account` と GET `/edit-icon` も叩き、本番プレビュー（`temporaryUrl`）で画面が落ちないことを見る。

---

## 4. チェックリスト

Feature テストで確認済みの項目は `[x]`。ブラウザでも登録〜再ログインまで通した。

### 4-1. 会員登録（dsc_01）

- [x] 正しい入力で登録 → `/` へ飛び、緑の「会員登録が完了しました。ログインしてください。」
- [x] メールは小文字で保存される（`New.User@example.com` → `new.user@example.com`）
- [x] 空欄 → 「ユーザー名を入力してください。」「メールアドレスを入力してください。」「パスワードを入力してください。」
- [x] 使えない文字 / 形式不正 → 「使用できない文字が含まれています。」「メールアドレスの形式が正しくありません。」「パスワードは半角英数字と記号で入力してください。」
- [x] ユーザー名 2文字 / 17文字 → 「ユーザー名は3文字以上16文字以内で入力してください。」
- [x] 既存メールと大文字小文字だけ違う → 「そのメールアドレスはすでに使用されています。」

正常系の名前は `山田太郎`（4文字）。`min:3` のため 2文字の漢字だけでは登録できない。

### 4-2. ログイン（dsc_02-1）

- [x] 登録済みユーザーでログイン → `/dashboard`
- [x] パスワード違い → 「ログイン情報が正しくありません。」
- [x] 未ログインで `/dashboard` → ログイン画面へ、「ログインしてください」
- [x] フォーム送信先が `/`（`route('login')`）

### 4-3. ログアウト（dsc_02-2）

- [x] ログアウト → `/` へ戻る
- [x] ログアウト後に `/dashboard` → ログイン画面へ
- [x] ログアウト後にもう一度ログインできる
- [x] 未ログインで `POST /logout` → ログイン画面へ
- [x] `GET /logout` は 405（POST 専用）

### 4-4. ユーザー名変更（dsc_03）

前提: ログイン済み。

#### 正常系

- [x] `user123` で保存 → account に移動し、名前が更新される
- [x] `abc` で保存 → 成功（最小3文字）
- [x] `1234567890123456` で保存 → 成功（最大16文字）
- [x] キャンセル → 更新せず account へ
- [x] 変更後の account に新しい名前が出る

#### 異常系

- [x] （空欄）→ 「ユーザー名は必須です。」
- [x] `ab` → 「ユーザー名は3文字以上です。」
- [x] `太郎` → 「ユーザー名は3文字以上です。」（2文字。dsc_03 では正常系に書いてあるが、実装の `min:3` は文字数）
- [x] 17文字以上 → 「ユーザー名は16文字以内です。」
- [x] `user@name` → 「半角英数字、ひらがな、カタカナ、漢字のみ…」
- [x] エラー後 → 入力欄にさっきの値が残っている

#### セキュリティ

- [x] ログアウト後に `/edit-username` → ログイン画面へ
- [x] ログアウト後に POST のみ → ログイン画面へ（`update` は動かない）

### 4-5. メール変更（dsc_04）

前提: ログイン済み。

#### 正常系

- [x] 新しい未使用メールで保存 → account に移動し、メールが更新されている
- [x] 自分の今のメールのまま保存 → 成功（自分自身は重複扱いにしない）
- [x] キャンセル → 更新せず account へ
- [x] `User@example.com` で保存 → account には `user@example.com`

#### 異常系

- [x] （空欄）→ 「メールアドレスは必須です。」
- [x] `not-an-email` → 「メールアドレスの形式が不正です。」
- [x] 256文字以上 → 「メールアドレスは255文字以内です。」
- [x] 他ユーザーが使っているメール → 「そのメールアドレスはすでに使用されています。」
- [x] 他ユーザーのメールと大文字小文字だけ違う → 同じメッセージ
- [x] エラー後 → 入力欄にさっきの値が残っている

#### セキュリティ

- [x] ログアウト後に `/edit-email` → ログイン画面へ
- [x] ログアウト後に POST のみ → ログイン画面へ

### 4-6. パスワード変更（dsc_05）

前提: ログイン済み。

#### 正常系

- [x] 正しい現在パスワード + 条件を満たす新しいパスワード（確認一致）→ account へ
- [x] 変更後、新しいパスワードでログインできる
- [x] 変更後、古いパスワードではログインできない
- [x] キャンセル → 更新せず account へ

#### 異常系

- [x] 現在パスワードが空 → 「現在のパスワードを入力してください。」
- [x] 現在パスワードが違う → 「現在のパスワードが正しくありません。」
- [x] 新しいパスワードが空 → 「パスワードを入力してください。」
- [x] 使えない文字のみ → 「パスワードは半角英数字と記号で入力してください。」
- [x] 7文字以下 → 「パスワードは8文字以上64文字以内で入力してください。」
- [x] 確認用が空 → 「パスワード（確認用）を入力してください。」
- [x] 確認用と不一致 → 「パスワードが一致していません。」
- [x] 英字のみなど（Password ルール未充足）→ 強さに関するエラー

#### セキュリティ・画面

- [x] ログアウト後に `/edit-password` → ログイン画面へ
- [x] ログアウト後に POST のみ → ログイン画面へ
- [x] `@csrf` がある
- [x] 確認欄の name が `new_password_confirmation`

### 4-7. アイコン変更（dsc_06）

前提: ログイン済み。仮も本番も `local` ディスク（`storage/app/private`）。画面は期限つき URL。

#### 正常系

- [x] GET `/edit-icon` → 編集画面が出る
- [x] PNG / JPEG（1MB以下、400px以下）をアップロード → 仮保存して編集画面へ。プレビューは仮
- [x] 変更を保存 → 仮を `{id}_{日時}.拡張子` に移し、DB を更新して account へ
- [x] 保存後の account / ナビ / 編集画面 → 本番アイコンが出る（ヘッダーは仮を出さない）
- [x] キャンセル → 仮ファイルとセッションを消し、本番は変えない。account へ
- [x] デフォルトに戻す → 本番と仮を消し、DB を空にする。編集画面へ

#### 異常系

- [x] 仮が無い状態で保存 → 「画像がアップロードされていません」で編集画面へ
- [x] ファイルなしでアップロード → 「画像がアップロードされていません」
- [x] PDF など → 「PNG または JPEG 形式の画像をアップロードしてください」
- [x] 401px 以上 → 「画像サイズは400px × 400px以下にしてください」

#### セキュリティ

- [x] 未ログインで GET / POST（upload, update, cancel, reset）→ ログイン画面へ

---

## 5. 注意: `太郎` と `min:3`

[dsc_03edit-accout-username.md](./dsc_03edit-accout-username.md) の正常系は `太郎` だが、ルールは `min:3`。Laravel の `min` は **文字数**（`mb_strlen`）なので、漢字2文字は足りない。

| 入力 | 文字数 | 結果 |
|------|--------|------|
| `太郎` | 2 | 「ユーザー名は3文字以上です。」 |
| `abc` | 3 | 成功 |
| `山田太郎` | 4 | 成功（登録の正常系） |

dsc_03 のチェックは実装に合わせて、`太郎` を異常系としてテストしている。

---

## 変更ファイル

- `tests/Feature/MdChecklistTest.php`
- `database/migrations/2026_09_22_185329_add_icon_to_users_table.php`（sqlite でも `icon` 列を足す。`after('password')` は付けない）
