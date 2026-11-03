# login-laravel

## GitHub Codespaces での起動

`codespaces` ブランチを選んで **新しく Codespace を作ったとき** に、MySQL、`.env`、`composer install`、表の作成、開発サーバーの起動まで自動で行われます。

すでに開いている Codespace の中で、このブランチへ切り替えても、環境は切り替わりません。`sudo service mysql start` が `unrecognized service` になるのは、その Codespace に MySQL が入っていないためです。

作り直す手順:

1. GitHub のリポジトリページで **Code** を押す
2. **Codespaces** タブで、ブランチ **codespaces** を選んで新しく作る
3. 作成が終わるまで待つ
4. **Ports** の `8000` にある `https://～.app.github.dev` を開く

`localhost` や `http://127.0.0.1:8000` は自分のパソコンを指すので、そこは開きません。

画面が出ないときは、ターミナルで次を実行してから、もう一度 Ports の URL を開きます。

```bash
php artisan serve --host=0.0.0.0 --port=8000
```
