# Laravel インストール直後の初期設定

`composer create-project` やリポジトリ clone 直後に行う設定の手順書です。  
[make-login.md](./make-login.md) でログイン機能を実装する **前** に完了させてください。

**本プロジェクトは MAMP を使ったローカル開発を前提としています。**

---

## 目次

1. [全体の流れ](#1-全体の流れ)
2. [依存関係のインストール](#2-依存関係のインストール)
3. [環境ファイル（.env）の準備](#3-環境ファイルenvの準備)
4. [アプリケーションキーの生成](#4-アプリケーションキーの生成)
5. [日本語化](#5-日本語化)
6. [タイムゾーンの設定](#6-タイムゾーンの設定)
7. [データベースの設定（MAMP）](#7-データベースの設定mamp)
8. [マイグレーションの実行](#8-マイグレーションの実行)
9. [ストレージの公開リンク](#9-ストレージの公開リンク)
10. [開発サーバーの起動確認](#10-開発サーバーの起動確認)
11. [フロントエンド（CSS）の方針](#11-フロントエンドcssの方針)
12. [Git で管理しないファイル](#12-git-で管理しないファイル)
13. [任意: Docker で MySQL を使う](#13-任意-docker-で-mysql-を使う)
14. [チェックリスト](#14-チェックリスト)

---

## 1. 全体の流れ

```text
composer install
  ↓
.env を作成・編集
  ↓
php artisan key:generate
  ↓
日本語化（locale / 翻訳ファイル）
  ↓
DB 接続設定
  ↓
php artisan migrate
  ↓
動作確認（artisan serve または MAMP Apache）
```

---

## 2. 依存関係のインストール

### 2.1 MAMP の PHP を使う

Laravel 13 は **PHP 8.3 以上** が必要です。ターミナルで `php -v` を実行し、バージョンを確認してください。

MAMP 付属の PHP を使う場合（パスは MAMP のバージョンにより異なります）:

```bash
php -v   # PHP 8.4.x と表示されることを確認
```

MAMP アプリの **Preferences → PHP** で使用するバージョンを 8.3 以上に設定してください。

### 2.2 Composer

```bash
composer install
```

フロントエンド（Vite / Tailwind）も使う場合:

```bash
npm install
npm run build   # 本番ビルド
# または開発時
npm run dev
```

---

## 3. 環境ファイル（.env）の準備

`.env` を手動で作成
```
APP_NAME="Login Laravel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP
APP_TIMEZONE=Asia/Tokyo
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=login_db_laravel
DB_USERNAME=root
DB_PASSWORD=root
```
### .envで最低限変更する項目

| 変数 | 推奨値 | 説明 |
|------|--------|------|
| `APP_NAME` | `Login Laravel` | アプリ名 |
| `APP_ENV` | `local` | 開発環境 |
| `APP_DEBUG` | `true` | 開発中はエラー詳細を表示 |
| `APP_URL` | `http://localhost:8000` または `http://localhost:8888` | 後述の起動方法に合わせる |
| `APP_LOCALE` | `ja` | デフォルト言語（後述） |
| `APP_FALLBACK_LOCALE` | `ja` | 翻訳がない場合のフォールバック |
| `APP_FAKER_LOCALE` | `ja_JP` | Seeder / Factory 用 |
| `APP_TIMEZONE` | `Asia/Tokyo` | タイムゾーン（後述） |

### 元 PHP システムとの対応

| 元システム | Laravel（MAMP） |
|-----------|----------------|
| DB 名 `login_db` | `login_db_laravel`（README.md 準拠） |
| MySQL root / secret | MAMP デフォルトは `root` / `root`（要確認） |
| ポート 8080（PHP 組み込みサーバー） | MAMP Apache: `8888` / `artisan serve`: `8000` |

---

## 4. アプリケーションキーの生成

セッション暗号化・CSRF などに必要です。

```bash
php artisan key:generate
```

`.env` の `APP_KEY` が自動で設定されます。  
**`APP_KEY` が空のままではアプリは正常に動きません。**

---

## 5. 日本語化

元の PHP システムは画面・メッセージがすべて日本語です。Laravel でも同様にしておくと、バリデーションエラーや認証メッセージが英語にならず済みます。

### 5.1 `.env` でロケールを指定

```env
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP
```

`config/app.php` は `env('APP_LOCALE', 'en')` を参照するため、`.env` の変更だけで基本設定は完了します。

### 5.2 翻訳ファイルの用意

Laravel 13 では標準で `lang/` ディレクトリは **含まれていません**。英語の翻訳ファイルを公開するには:

```bash
php artisan lang:publish
```

`lang/en/` に `auth.php`, `validation.php` などが作成されます。

### 5.3 日本語翻訳の追加（2 つの方法）

#### 方法 A: パッケージを使う（推奨・手軽）

バリデーション・認証メッセージなどをまとめて日本語化できます。

**Laravel-Lang（翻訳ファイルのみ）:**

```bash
composer require laravel-lang/lang laravel-lang/publisher --dev
php artisan lang:add ja
```

**breezejp（設定変更も自動）:今回はこちらを使用**

```bash
composer require askdkc/breezejp --dev
php artisan breezejp
```

Breeze 等のスターターキットは使わなくても、バリデーション日本語化だけ利用できます。

#### 方法 B: 手動で `lang/ja/` を作成

パッケージを入れたくない場合は、`lang/en/` を `lang/ja/` にコピーし、各ファイルを日本語に書き換えます。  
最低限あるとよいファイル:

| ファイル | 用途 |
|----------|------|
| `lang/ja/validation.php` | フォームバリデーションエラー |
| `lang/ja/auth.php` | 認証失敗メッセージ |
| `lang/ja/passwords.php` | パスワードリセット（将来用） |
| `lang/ja/pagination.php` | ページネーション（将来用） |

### 5.4 アプリ固有のメッセージ

ログイン画面の「ログイン情報が正しくありません。」など、元システム固有の文言は翻訳ファイルではなく **コントローラや Blade に直接書く** か、`lang/ja/messages.php` を自作して `__('messages.login_failed')` のように呼び出します。

```php
// lang/ja/messages.php（自作例）
return [
    'login_failed' => 'ログイン情報が正しくありません。',
    'logout' => 'ログアウトしました。',
    'login_required' => 'ログインしてください。',
];
```

### 5.5 日本語化の確認

```bash
php artisan tinker
>>> __('validation.required', ['attribute' => 'メールアドレス'])
```

日本語メッセージが返れば OK です。

---

## 6. タイムゾーンの設定

Laravel 11 以降、タイムゾーンは `config/app.php` ではなく `.env` で指定します。

```env
APP_TIMEZONE=Asia/Tokyo
```

`config/app.php` の `'timezone' => 'UTC'` はそのままで問題ありません（`.env` が優先されます）。

---

## 7. データベースの設定（MAMP）

### 7.1 MAMP を起動する

1. MAMP を起動し **Start** をクリック
2. **Preferences → Web Server** で Apache のポートを確認（デフォルト: `8888`）
3. **Preferences → Ports** で MySQL のポートを確認（デフォルト: `8889`）

> MAMP PRO や設定変更によりポートが `80` / `3306` になっている場合もあります。必ず MAMP の画面で実際の値を確認してください。

### 7.2 `.env` の DB 設定（MAMP）

MAMP のデフォルト設定を使う場合の例:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=login_db_laravel
DB_USERNAME=root
DB_PASSWORD=root
```

| 項目 | MAMP デフォルト | 備考 |
|------|----------------|------|
| ホスト | `127.0.0.1` | `localhost` だとソケット接続になり失敗することがある |
| ポート | `8889` | MAMP の MySQL ポート（要確認） |
| ユーザー | `root` | |
| パスワード | `root` | MAMP 初期設定。変更している場合はその値 |

ソケット接続が必要な場合のみ、以下を追加します（通常はポート指定で足ります）:

```env
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
```

### 7.3 データベースの作成
#### SQL で作成する場合:

```sql
/Applications/MAMP/Library/bin/mysql -u root -p -P 8889

CREATE DATABASE IF NOT EXISTS login_db_laravel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

##### （phpMyAdmin で作成する場合）:
MAMP 起動後、ブラウザで phpMyAdmin を開きます。

```
http://localhost:8888/phpMyAdmin/
```

（Apache のポートが `8888` でない場合は読み替えてください）

1. 左メニューまたは「データベース」タブを開く
2. データベース名 `login_db_laravel` を入力
3. 照合順序 `utf8mb4_unicode_ci` を選択
4. 作成

### 7.4 接続確認

```bash
php artisan migrate:status
```

まだマイグレーションしていないのでMigration table not found. が出ていればOK

接続エラーが出る場合は以下を確認してください。

- MAMP の MySQL が起動しているか
- `.env` の `DB_PORT` が MAMP の設定と一致しているか
- `DB_PASSWORD` が MAMP で設定した値と一致しているか
- ターミナルの `php` が MAMP の PHP 8.3 以上か（`php -v`）

### 7.5 セッション

Laravel 13 のデフォルトは `SESSION_DRIVER=database` です。  
`migrate` 実行時に `sessions` テーブルも作成されるため、追加設定は不要です。

---

## 8. マイグレーションの実行

Laravelインストール時にデフォルトで存在しているマイグレーションを実行する
```bash
php artisan migrate
```

作成される主なテーブル:

| テーブル | 用途 |
|----------|------|
| `users` | ユーザー（ログイン認証） |
| `sessions` | セッション（デフォルト driver が database の場合） |
| `cache` | キャッシュ（デフォルトが database の場合） |
| `jobs` | キュー（使用時） |

`users` テーブルには README の `icon` カラムがまだないため、[make-login.md](./make-login.md) の手順で追加マイグレーションを作成します。

---

## 9. ストレージの公開リンク

アイコン画像など `storage/app/public` 配下のファイルを公開 URL で参照する場合に必要です。  
アカウント編集（アイコン変更）を実装する段階で実行すれば十分ですが、先に済ませておいても問題ありません。

```bash
php artisan storage:link
```

`public/storage` → `storage/app/public` のシンボリックリンクが作成されます。

---

## 10. 開発サーバーの起動確認

MAMP 環境では **2 通り** の起動方法があります。どちらか一方で構いません。

### 10.1 方法 A: `php artisan serve`（手軽）

```bash
php artisan serve
```

| 項目 | 値 |
|------|-----|
| URL | `http://localhost:8000` |
| `.env` の `APP_URL` | `http://localhost:8000` |

ブラウザで `http://localhost:8000` を開き、Laravel のウェルカム画面が表示されれば OK です。

### 10.2 方法 B: MAMP の Apache を使う

MAMP のドキュメントルートを Laravel の `public` ディレクトリに向けます。

**手順:**

1. MAMP → **Preferences → Web Server**
2. **Document Root** をプロジェクトの `public` フォルダに設定  
   例: `/Users/mq/Desktop/git027/login-laravel/public`
3. MAMP を再起動
4. ブラウザで `http://localhost:8888` を開く

| 項目 | 値 |
|------|-----|
| URL | `http://localhost:8888`（Apache ポートは要確認） |
| `.env` の `APP_URL` | `http://localhost:8888` |

> `public` ではなくプロジェクトルートを指定するとセキュリティ上問題があるため、必ず `public` を指定してください。

### 10.3 ヘルスチェック

```bash
curl http://localhost:8000/up
# または MAMP Apache の場合
curl http://localhost:8888/up
# {"status":"ok"} が返れば OK
```

### 10.4 複数プロセスをまとめて起動（任意）

`composer.json` の `dev` スクリプトでサーバー・キュー・ログ・Vite を同時起動できます。

```bash
composer run dev
```

---


## 12. Git で管理しないファイル

`.gitignore` の作成

```
/.phpunit.cache
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
```

| ファイル / ディレクトリ | 理由 |
|------------------------|------|
| `.env` | DB パスワード・APP_KEY など機密情報 |
| `vendor/` | `composer install` で再生成 |
| `node_modules/` | `npm install` で再生成 |
| `storage/*.key` | 暗号化キー |

`.env.example` はテンプレートとしてリポジトリに含め、値は空またはダミーにしておきます。

---

## 13. 任意: Docker で MySQL を使う

MAMP の MySQL の代わりに Docker で MySQL を立てる場合の参考です。  
**MAMP を使っている場合はこのセクションは不要です。**

```yaml
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: login_db_laravel
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

```bash
docker compose up -d
```

`.env` の `DB_HOST=127.0.0.1` のまま接続できます。

---

## 14. チェックリスト

実装開始前に以下を確認してください。

- [ ] MAMP 起動済み（Apache / MySQL）
- [ ] ターミナルの `php -v` が 8.3 以上（MAMP の PHP を使用）
- [ ] `composer install` 完了
- [ ] `.env` 作成済み
- [ ] `php artisan key:generate` 実行済み（`APP_KEY` が設定されている）
- [ ] `APP_LOCALE=ja` / `APP_TIMEZONE=Asia/Tokyo` 設定済み
- [ ] 日本語翻訳ファイル（`lang/ja/`）準備済み
- [ ] `.env` の `DB_PORT=8889` など MAMP の設定と一致
- [ ] phpMyAdmin で `login_db_laravel` データベース作成済み
- [ ] `php artisan migrate` 成功
- [ ] `php artisan serve` または MAMP Apache でウェルカム画面が表示される
- [ ] （任意）`php artisan storage:link` 実行

すべて完了したら、[make-login.md](./make-login.md) のログイン機能実装に進んでください。

---

**作成日:** 2026-06-15
