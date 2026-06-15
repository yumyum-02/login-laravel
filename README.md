# login-laravel

[元の PHP システム](https://github.com/yumyum-02/login) を Laravel で再現するプロジェクトです。

## 目次

- [元のシステム](#元のシステム)
  - [機能一覧](#機能一覧)
  - [サーバーサイド](#サーバーサイド)
  - [フロントエンド](#フロントエンド)
  - [安全性](#安全性)

---

## 元のシステム

### 機能一覧

| 機能 | 内容 |
|------|------|
| ログイン | ユーザー名・パスワード |
| アカウント作成 | メールアドレス・ユーザー名・パスワード |
| アカウント情報編集 | ユーザー名・アイコン・メールアドレス・パスワード |
| アカウント削除 | — |
| ユーザー一覧 | 登録ユーザーの一覧表示 |

---

### ■サーバーサイド

#### DB

- 定義ファイル: `src/db_data.sql`
- テーブル名: `login_db_laravel`

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  icon VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### バリデーション

- 定義ファイル: `src/functions/validation.php`
全画面で使用

---

### ■フロントエンド

テンプレートは `src/template/` に配置されています。
各画面は共通で `bootstrap.php` を読み込みます。

#### ・共通パーツ

| パーツ | ファイル |
|--------|----------|
| ナビゲーション | `components/navbar.php` |
| サイドバー | `components/sidebar.php` |

#### ・画面一覧

| 画面 | テンプレート | 使用カラム | 備考 |
|------|--------------|------------|------|
| ログイン | `login_template.php` | email, password | バリデーション使用 |
| アカウント登録 | `regist_template.php` | name, email, password | バリデーション使用 |
| ダッシュボード | `dashboard_template.php` | name | 「ようこそ、ユーザー名さん ログインに成功しました」を表示 |
| アカウント情報 | `account_template.php` | — | ログイン中ユーザーの情報表示。各項目の変更画面へ遷移 |
| ユーザー一覧・削除 | `admin_template.php` | email, name, id | バリデーション使用 |

#### ・アカウント情報の編集画面

`account_template.php` から各編集画面へ遷移します。
キャンセル時はいずれも `admin/account.php` 経由でアカウント情報ページに戻ります。

| 画面 | テンプレート | 使用カラム | 変更処理 | 補足 |
|------|--------------|------------|----------|------|
| ユーザー名変更 | `edit-profile_template.php` | name | — | エラー時は `old_input['name']` を表示 |
| アイコン変更 | `edit-icon_template.php` | icon | `exec_edit-icon.php` | プレビュー・デフォルト戻しあり。1MB 以下 / 400px 以下 / PNG・JPEG |
| メールアドレス変更 | `edit-email_template.php` | email | `exec_edit-email.php` | エラー時は `old_input['email']` を表示 |
| パスワード変更 | `edit-password_template.php` | password | — | 現在・新規・確認の 3 項目。エラー時は旧パスワードを表示しない |

---

### 安全性

`bootstrap.php` で以下を読み込み、セキュリティ対策を行います。

| ファイル | 役割 |
|----------|------|
| `bootstrap.php` | `session_regenerate_id()` でセッション ID を毎回再生成 |
| `Auth/AuthUser.php` | ユーザー認証 |
| `functions/csrf.php` | CSRF 対策 |
| `functions/validation-error.php` | バリデーションエラー処理 |
| `functions/redirect.php` | リダイレクト |
| `functions/sanitize.php` | XSS 対策（エスケープ） |
| `functions/logout.php` | ログアウト |
| `functions/session-message.php` | セッションメッセージ取得 |
| `functions/icon-file.php` | アイコンファイル操作 |
| `functions/display_icon.php` | アイコン表示 |
