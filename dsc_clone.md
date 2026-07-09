# クローン後の環境構築手順

このリポジトリをクローンした後に、ローカル環境で動作させるための手順書です。

**前提条件:**
- MAMP がインストール済み（MySQL / Apache）
- PHP 8.3 以上
- Composer がインストール済み
- Node.js / npm がインストール済み（フロントエンドを使う場合）

---

## 目次

1. [全体の流れ](#1-全体の流れ)
2. [リポジトリのクローン](#2-リポジトリのクローン)
3. [依存関係のインストール](#3-依存関係のインストール)
4. [環境ファイルの作成](#4-環境ファイルの作成)
5. [アプリケーションキーの生成](#5-アプリケーションキーの生成)
6. [データベースの設定](#6-データベースの設定)
7. [マイグレーションの実行](#7-マイグレーションの実行)
8. [ストレージリンクの作成](#8-ストレージリンクの作成)
9. [動作確認](#9-動作確認)
10. [チェックリスト](#10-チェックリスト)

---

## 1. 全体の流れ

```text
git clone
  ↓
composer install
  ↓
npm install
  ↓
cp .env.example .env
  ↓
php artisan key:generate
  ↓
.env を編集（DB設定など）
  ↓
データベース作成
  ↓
php artisan migrate
  ↓
php artisan storage:link
  ↓
php artisan serve
```

---

## 2. リポジトリのクローン

```bash
git clone <リポジトリURL>
cd login-laravel
```

---

## 3. 依存関係のインストール

### 3.1 PHP の依存関係

```bash
composer install
```

`vendor/` ディレクトリが作成され、Laravel のフレームワークと必要なパッケージがインストールされます。

### 3.2 JavaScript の依存関係（フロントエンド使用時）

```bash
npm install
```

`node_modules/` ディレクトリが作成されます。

---

## 4. 環境ファイルの作成

`.env` ファイルは `.gitignore` で除外されているため、手動で作成します。

```bash
cp .env.example .env
```

または手動で作成：

```bash
nano .env
```

---

## 5. アプリケーションキーの生成

セッション暗号化・CSRF トークンなどに必要なキーを生成します。

```bash
php artisan key:generate
```

`.env` の `APP_KEY` が自動で設定されます。

---

## 6. データベースの設定

### 6.1 MAMP の起動と確認

1. MAMP を起動し **Start** をクリック
2. **Preferences → Ports** で MySQL のポートを確認（デフォルト: `8889`）

### 6.2 .env の編集

`.env` ファイルを開き、データベース情報を **自分の環境に合わせて** 編集します。

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889                    # MAMP の設定に合わせる
DB_DATABASE=login_db_laravel    # 任意のDB名
DB_USERNAME=root                # MAMP のユーザー名
DB_PASSWORD=root                # MAMP のパスワード
```

### 6.3 データベースの作成

**方法 A: phpMyAdmin で作成（簡単）**

1. ブラウザで phpMyAdmin を開く
   ```
   http://localhost:8888/phpMyAdmin/
   ```

2. 「データベース」タブをクリック
3. データベース名 `login_db_laravel` を入力
4. 照合順序 `utf8mb4_unicode_ci` を選択
5. 「作成」をクリック

**方法 B: MySQL コマンドで作成**

```bash
/Applications/MAMP/Library/bin/mysql -u root -p -P 8889

CREATE DATABASE IF NOT EXISTS login_db_laravel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

exit;
```

### 6.4 接続確認

```bash
php artisan migrate:status
```

`Migration table not found.` と表示されれば、データベース接続は成功しています。

---

## 7. マイグレーションの実行

データベースにテーブルを作成します。

```bash
php artisan migrate
```

以下のテーブルが作成されます:
- `users` - ユーザー情報
- `sessions` - セッション管理
- `cache` - キャッシュ
- `jobs` - キュー（使用時）
- その他

---

## 8. ストレージリンクの作成

アイコン画像などのアップロードファイルをブラウザからアクセスできるようにします。

```bash
php artisan storage:link
```

`public/storage` → `storage/app/public` へのシンボリックリンクが作成されます。

---

## 9. 動作確認

### 9.1 開発サーバーの起動

```bash
php artisan serve
```

### 9.2 ブラウザで確認

```
http://localhost:8000
```

Laravel のウェルカム画面が表示されれば成功です！

### 9.3 ヘルスチェック

```bash
curl http://localhost:8000/up
# {"status":"ok"} が返ればOK
```

---

## 10. チェックリスト

クローン後、以下をすべて完了させてください。

- [ ] `git clone` 完了
- [ ] `composer install` 実行済み
- [ ] `npm install` 実行済み（フロントエンド使用時）
- [ ] `.env` ファイル作成済み（`.env.example` からコピー）
- [ ] `php artisan key:generate` 実行済み
- [ ] `.env` の DB 設定を自分の環境に合わせて編集済み
- [ ] phpMyAdmin または MySQL で `login_db_laravel` データベース作成済み
- [ ] `php artisan migrate` 成功
- [ ] `php artisan storage:link` 実行済み
- [ ] `php artisan serve` でウェルカム画面が表示される

---

## トラブルシューティング

### エラー: "No application encryption key has been specified"

```bash
php artisan key:generate
```

### エラー: "SQLSTATE[HY000] [2002] Connection refused"

- MAMP の MySQL が起動しているか確認
- `.env` の `DB_PORT` が MAMP の設定と一致しているか確認
- `DB_HOST` を `127.0.0.1` に変更してみる（`localhost` だと失敗することがある）

### エラー: "Class 'XXXXX' not found"

```bash
composer install
# または
composer dump-autoload
```

### vendor/ や node_modules/ がない

`.gitignore` で除外されているため、クローン後に必ず：
```bash
composer install
npm install
```

---

## Git に含まれないファイル（理由）

| ファイル/ディレクトリ | 理由 | 復元方法 |
|-------------------|------|---------|
| `.env` | パスワード・秘密鍵が含まれる | `.env.example` をコピー |
| `vendor/` | 容量が大きい（数百MB） | `composer install` |
| `node_modules/` | 容量が大きい（数百MB） | `npm install` |
| `public/storage` | シンボリックリンク（環境ごとに作成） | `php artisan storage:link` |
| データベース | 環境ごとに異なる | マイグレーション実行 |

これらは `composer.json`, `package.json`, マイグレーションファイルから再生成できるため、Git には含めません。

---

## 参考

詳細な初期設定手順は [first-step.md](./first-step.md) を参照してください。

**作成日:** 2026-06-16
