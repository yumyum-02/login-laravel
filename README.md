# login-laravel

## GitHub Codespaces での起動

`codespaces` ブランチを選んで **新しく Codespace を作ったとき** に、MySQL、`.env`、`composer install`、表の作成、開発サーバーの起動まで自動で行われます。

`localhost` や `http://127.0.0.1:8000` は自分のパソコンを指すので、そこは開きません。
画面が出ないときは、ターミナルで次を実行してから、もう一度 Ports の URL を開きます。

```bash
php artisan serve --host=0.0.0.0 --port=8000
```
