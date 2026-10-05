# アイコン変更実装メモ

Laravel 13 での実装。参考: [Laravel 13.x 日本語ドキュメント](https://readouble.com/laravel/13.x/ja)

---

## Laravel に任せて書かなくてよくなったこと

| 元で自分でやっていたこと | 楽になったこと |
|--------------------------|----------------|
| 毎回のログインチェック | ルートに認証を付けるだけ |
| 毎回の CSRF 確認 | フォームにトークンを置くだけ |
| アップロードファイルの取り出しと失敗判定 | リクエストからファイルを取る。失敗は Laravel が弾く |
| 画像の種類・サイズ・幅高さの独自チェックと、失敗時の画面戻り | バリデーション1回。失敗すると自動で編集画面へ戻り、エラー文が出る |
| フォルダ作成やファイル名の組み立て、ファイルの移動 | ストレージの保存・削除 |
| セッションからユーザーを取る | ログイン中ユーザーをそのまま使う |
| セッション上のアイコン情報を手で書き換える | DB を更新すれば、次の表示は DB を見る |
| try-catch（システムエラー） | 例外は Laravel のエラー画面。保存・移動の失敗は `withErrors` で編集画面へ |

---

## 編集画面表示

- ルート: `routes/web.php` の GET `edit-icon`。画面を出す
- コントローラー: `EditIconController.php` の `edit`
- Blade: `resources/views/edit-icon.blade.php`。仮があれば仮、なければ本番、どちらも無ければデフォルトのアイコンを表示。チェックに失敗した文も出す。ヘッダーとアカウント画面はデフォルトか本番

---

## アップロード（仮保存）

画像をクリックすると送る。仮も本番も非公開ディスク（`local` = `storage/app/private`）。画面は期限つき URL で出す。

- ルート: `routes/web.php` の POST `edit-icon/upload`
- コントローラー: `EditIconController.php` の `upload`。画像をチェックする。前の仮があれば消す。新しい仮を保存し、パスをセッションに残して編集画面へ戻る。保存に失敗したら `withErrors(['icon' => 'アイコンのアップロードに失敗しました'])` で編集画面へ戻る
- Blade: `edit-icon.blade.php` のアップロード用フォーム。戻ったあとは仮をプレビューする。エラーは `$errors->all()` で出す（欄名は `icon`）

---

## キャンセル

本番のアイコンは変えない。

- ルート: `routes/web.php` の POST `edit-icon/cancel`
- コントローラー: `EditIconController.php` の `cancel`。仮があればファイルとセッションを消す。アカウント画面へ戻る
- Blade: `edit-icon.blade.php` のキャンセルボタン

---

## デフォルトに戻す

仮と本番は別々に「あれば消す」。

`User.php` でアイコンを更新できるようにした（そうしないと DB が空にならない）。

- ルート: `routes/web.php` の POST `edit-icon/reset`
- コントローラー: `EditIconController.php` の `reset`。本番があればファイルを消して DB を空にする。仮があればファイルとセッションを消す。編集画面へ戻る
- Blade: `edit-icon.blade.php` のデフォルトに戻すボタン。戻ったあとはデフォルト画像

---

## 変更を保存

仮が無ければ `withErrors(['icon' => '画像がアップロードされていません'])` で編集画面へ戻る。
あれば仮を `{id}_{日時}.拡張子` に移し、DB にそのパスを入れてアカウント画面へ戻る。
移せなければ DB は更新せず `withErrors(['icon' => 'アイコンの更新に失敗しました'])` で編集画面へ戻る。
古い本番ファイルは消さない。

欄名を `icon` にする。`['文章']` だけだと 0 番のエラーになり、入力チェックの `icon.required` と袋が揃わない。画面は `$errors->all()` のままで出る。

- ルート: `routes/web.php` の POST `edit-icon/update`
- コントローラー: `EditIconController.php` の `update`
- Blade: `edit-icon.blade.php` の変更を保存ボタン
