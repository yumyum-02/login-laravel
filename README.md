# login-laravel

## GitHub Codespaces での起動

MySQL のインストール、データベース作成、`.env` の作成、`composer install`、表の作成は、Codespace を作るときに自動で行われます。同じ Codespace を開き直したときも、MySQL と開発サーバーは自動で起動します。手作業で毎回やる必要はありません。

GitHub のリポジトリページで **Code** を押し、**Codespaces** タブから Codespace を作ります。リポジトリは非公開のままでよく、ポートもプライベートのままで自分だけ開けます。作成が終わるまで待ちます。

作成が終わったら、**Ports** の `8000` にある `https://～.app.github.dev` を開きます。`http://127.0.0.1:8000` や `localhost` は、自分のパソコンを指すため開きません。

画面が出ないときは、ターミナルで次を実行してから、もう一度 Ports の URL を開きます。

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

この設定を入れる前に作った Codespace には適用されません。その Codespace は作り直してください。
