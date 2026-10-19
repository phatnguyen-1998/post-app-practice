# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## このリポジトリについて

Tutorial 13〜15 の題材となる小さな Laravel 10 の投稿アプリ（post-app）。Tutorial 13 では設計書を `docs/` に作り、Tutorial 14 以降でこのアプリに機能を足していく。ユーザーは学習者で、やりとりは日本語（ときどきベトナム語）。

## コマンド

ホストに `php` は入っていない。PHP / artisan / composer はすべて Sail（Docker）経由で実行する。

```bash
./vendor/bin/sail up -d                          # 起動
./vendor/bin/sail down                           # 停止
./vendor/bin/sail artisan migrate:fresh --seed   # DBを作り直してシード（既存データは全部消える）
./vendor/bin/sail artisan test                   # 全テスト
./vendor/bin/sail artisan test --filter=テスト名  # 単体のテスト
./vendor/bin/sail pint                           # コード整形（Laravel Pint）
./vendor/bin/sail artisan tinker --execute="..." # DBの中身を確認するとき
```

シーダーを変えたあとは `migrate` ではなく `migrate:fresh --seed` で反映する。テストは `DB_DATABASE=testing` を使う（`phpunit.xml`）。

## アーキテクチャ

- **認証は Laravel Fortify。** ログイン・登録のルートは Fortify が自動で登録するため `routes/web.php` には出てこない。画面は `FortifyServiceProvider` で `auth.login` / `auth.register` ビューに結びつけ、ユーザー作成は `app/Actions/Fortify/CreateNewUser.php`。ログイン後の遷移先は `/posts`（`config/fortify.php` の `home` と `RouteServiceProvider::HOME`）。
- **投稿のルートは `auth` ミドルウェアのグループ内**（一覧・編集・更新・削除のみ。新規作成と詳細画面はまだない）。
- **認可は `PostPolicy`。** 投稿者本人だけが update / delete できる。`PostController` は各アクションで `$this->authorize()` を呼び、ビューでは同じポリシーで編集・削除ボタンを出し分ける。他人の投稿の URL を直接開くと 403。
- **バリデーションはコントローラー内の `$request->validate()`**（FormRequest は未使用）。
- **データ:** `User` 1—多 `Post` 多—1 `Category`。一覧は `Post::with(['user', 'category'])->latest()` で N+1 を避けている。

## シードデータ

`database/seeders/DatabaseSeeder.php` にカテゴリ3件、ユーザー8人、投稿26件。検証用アカウント `usera@example.com` / `userb@example.com`（パスワード `password`）のメールとパスワードは変えない。投稿は古い順に配列で並べ、`created_at` を「現在から何時間前か」で固定している。新しい投稿を足すときはこの順番を守る。README のシード説明（2人・4件）は古いままになっている。

## ドキュメント用のフォルダ

- `docs/<章>/`: 学習者が自分で作る設計書（Markdown・draw.io・PNG）。
- `answers/<章>/`: 教材の解答例。アプリのコードとは関係ない。機能を実装するときも読まなくてよい。
- ファイル名の先頭が題材を表す。`post-` はこの投稿アプリ、`cafe-` は章末演習のカフェのモバイルオーダーアプリ、`register-` は会員登録のテスト設計。

## コミット

コミットメッセージは日本語で、章の作業なら `13-5: 〜を追加` のように章番号を先頭につける。内容ごとにコミットを分ける。
