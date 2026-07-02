参考：https://liginc.co.jp/668649

メモ：laravel/uiなどのパッケージで実装もできる
https://biz.addisteria.com/laravel_authentication/
→今回は学習のために未使用

# アカウント新規登録機能の実装

## 1.バリデーション

### 1-1クラスを作成してバリデーションルールを定義する。
```
php artisan make:request Auth/RegisterRequest
```

```php
return [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    'password' => ['required', 'confirmed', Password::defaults()],
];
```
- emailのunique:usersは「重複しないこと」をチェックしている。
- passwordのconfirmedは「確認用と一致しているか」をチェックし、Password::defaults()はアプリケーション全体で共通のパスワードルールを適用するための便利な機能。
→現時点ではルールの中身が決まっていないため、設定が必要

**【Laravel】パスワードのバリデーションには Password::defaults() が便利**
https://zenn.dev/asaokamasakazu/articles/724d688cd9e366
・```app\Providers\AppServiceProvider```にルールを書いて、```Password::defaults()```で呼び出す
・

### 1-2.パスワードルールの設定
PHP実装時のバリデーション
・8文字以上64文字以内
・半角英字（a-zA-Z）、数字（0-9）、記号（!@#$%^&*()-_+=）
・英字のみ、数字のみ、記号のみでもOK

今回ははより安全にしたいので以下にする
・12文字以上64文字以内
・文字を含む
・大文字と小文字を含む
・数字を含む
・記号を含む
・漏洩チェック

app\Providers\AppServiceProvider
```php
//app/Providers/AppServiceProvider
use Illuminate\Validation\Rules\Password;
public function boot(): void
    {
         Password::defaults(function () {
            return Password::min(12) // 最低8文字
                ->max(64)
                ->letters()      // 文字を含む
                ->mixedCase()    // 大文字と小文字のアルファベットを含むこと
                ->numbers()      // 数字を1文字以上含むこと
                ->symbols()      // 記号を1文字以上含むこと
                ->uncompromised(); // 漏洩したパスワードでないかチェック
        });
    }
```
参考：【使いやすく優れもの】Laravelのデータバリデーションの基本 https://kinsta.com/jp/blog/laravel-validation/

### 1-3.バリデーションの日本語化
```lang/ja/validation.php```に以下のように定義
```php
'min' => [
    'string' => ':attributeは、:min文字以上で入力してください。',
],
'max' => [
    'string' => ':attributeは、:max文字以下で入力してください。',
],
'password' => [
    'letters' => ':attributeは、少なくとも1つの文字が含まれていなければなりません。',
    'mixed' => ':attributeは、少なくとも大文字と小文字を1つずつ含める必要があります。',
    'numbers' => ':attributeは、少なくとも1つの数字が含まれていなければなりません。',
    'symbols' => ':attributeは、少なくとも1つの記号が含まれていなければなりません。',
    'uncompromised' => 'この:attributeは、過去に漏洩したことのある脆弱な:attributeです。別の:attributeを入力してください。',
],
```

---

## 2. ユーザー情報の登録処理（Controller）