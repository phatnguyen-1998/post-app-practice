# post-app

投稿を一覧・編集・削除できる、小さな Laravel アプリです。Tutorial 13 から 15 まで、このアプリ 1 本を題材に設計・実装・仕組み化を進めます。

## できること

- ユーザー登録・ログイン・ログアウト
- 投稿の一覧表示（投稿者の名前とカテゴリつき）
- 投稿の編集（タイトル・カテゴリ・本文）と削除
- 自分の投稿だけを編集・削除（他人の投稿は編集・削除ボタンが出ず、URL を直接開くと 403 になります）

## フォルダについて

`answers/` は Tutorial 13 の答え合わせ用で、アプリのコードとは関係ありません。設計書の解答例が入っているだけなので、アプリの動きには影響しません。詳しくは `answers/README.md` を見てください。

自分で作る設計書は `docs/` に置きます（Tutorial 13 の中で作ります）。

## 使用技術

| 項目 | 内容 |
|:-----|:-----|
| フレームワーク | Laravel 10 |
| 言語 | PHP |
| データベース | MySQL |
| 実行環境 | Laravel Sail（Docker） |
| 認証 | Laravel Fortify |

PHP と MySQL のバージョンは Sail のコンテナが決めます。手元で確かめるときは `./vendor/bin/sail php -v` と `./vendor/bin/sail mysql --version` を実行してください。

## セットアップ

Docker Desktop（または Docker Engine）を起動してから実行してください。Windows の方は WSL（Ubuntu）のターミナルで実行します。

```bash
# パッケージをインストールする
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install

# 環境ファイルを作る
cp .env.example .env

# Sail を起動する
./vendor/bin/sail up -d

# アプリケーションキーを作る
./vendor/bin/sail artisan key:generate

# テーブルを作り、練習用データを入れる
./vendor/bin/sail artisan migrate --seed
```

ブラウザで `http://localhost` を開くと、トップページが表示されます。

初回起動時は `migrate` で「Connection refused」が出ることがあります。MySQL の起動が終わっていないだけなので、少し待ってからもう一度実行してください。

## 練習用のアカウント

`migrate --seed` で、カテゴリ 3 件（お知らせ・技術メモ・雑記）と、次の 2 人、それぞれの投稿 2 件（計 4 件）が入ります。

| メールアドレス | パスワード | 投稿 |
|:---------------|:-----------|:-----|
| `usera@example.com` | `password` | `/posts/1`・`/posts/2` |
| `userb@example.com` | `password` | `/posts/3`・`/posts/4` |

本物の値は入っていません。すべて練習用のダミーです。

## テスト

```bash
./vendor/bin/sail artisan test
```

## 停止

```bash
./vendor/bin/sail down
```

このリポジトリは Tutorial 14 でも使います

## 設計のメモ

リプライ機能を「なぜそう作ったのか」のメモです。

### 作らないと決めたもの

- リプライへの返信（入れ子）
- リプライの編集
- 一覧画面へのリプライ件数の表示
- リプライの入力エラーの日本語化（文言は Laravel 標準の英語のまま。ポスト編集のタイトルなどと同じ扱いで、日本語にするかは別の課題として残した）

### 選ばなかった作り方

- 入力欄に `required` 属性を付けない。付けるとブラウザが先に止めて、サーバー側のエラー表示を確かめられなくなるため。
- 送信・削除のための専用画面は作らない。どちらも詳細画面から行い、終わったら詳細画面に戻る。
- 入力チェックに FormRequest は使わない。`PostController` と同じく、コントローラー内の `$request->validate()` で行う。
- 削除できる人の判定を独自に作らない。`PostPolicy` と同じやり方（`$user->id === $reply->user_id`）で `ReplyPolicy` に置く。
- ビルドした CSS / JavaScript は使わない。見た目は既存の画面に合わせ、ビューの中に直接書いた CSS だけで組む。

### 知らずに変えると壊れる決めごと

- 一覧画面（`/posts`）は、詳細画面へのリンクを足しただけ。表示するデータと取得のしかた（`with(['user', 'category'])`）は変えない。
- `PostPolicy` の判定（本人だけが update / delete できる）、既存のルート名と URL（`posts.index` / `posts.edit` / `posts.update` / `posts.destroy`）、`posts` / `categories` / `users` テーブルの構造、`PostController@update` の入力チェックは変えない。
- `replies` の `user_id` と `post_id` は `cascadeOnDelete`。ポストを消すとそのリプライも消え、ユーザーを消してもそのユーザーのリプライが消える。
- リプライの本文は 280 字まで。上限は DB ではなく入力チェックで守っている。
- 3つのルート（詳細・送信・削除）は `auth` ミドルウェアのグループの中に置く。外すと未ログインでも届いてしまう。
- 削除ボタンは `@can('delete', $reply)` で出し分けている。ボタンを隠すだけでなく、`ReplyPolicy` で 403 にすることが本体。
- 検証用アカウント `usera@example.com` / `userb@example.com` のメールとパスワード、およびシードの二人のリプライのやりとりは変えない。ブラウザでの確認と「自分のは消せる・他人のは消せない」の見比べがこれに頼っている。

### 設計書のありか

- 設計書: `docs/reply.md`（実装した時点の記録で、そのあとは更新していない）
- Issue: [#1 リプライ機能を追加する](https://github.com/phatnguyen-1998/post-app-practice/issues/1)
- テスト: `tests/Feature/ReplyTest.php`（観点の一覧は設計書の「テスト観点」）
