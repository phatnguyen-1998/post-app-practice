# POSTSテーブル定義書

｜カラム名　　｜データ型　　｜制約　　　　　　　　　　　　　　｜説明
ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
｜ID　　　　　｜BIGINT　　　｜PRIMARY　KEY、AUTO　INCREMENT｜投稿を1件ずつ区別番号
｜user_id　　｜BIGINT　　　｜FOREIGN　KEY,NOT　NULL　　　　｜投稿したユーザ
｜category_id｜BIGINT　　　｜FOREIGN KEY, NOT NULL　　　　｜その投稿のカテゴリ
｜title　　　｜VARCHAR(255)｜NOT NULL　　　　　　　　　　　｜投稿のタイトル
｜content　　｜TEXT　　　　　｜NOT NULL　　　　　　　　　　　｜投稿のコンテンツ
｜created_at｜timestamp　　｜ー　　　　　　　　　　　　　　｜投稿作成日時
｜updated_at｜TIMESTAMP　　｜ー　　　　　　　　　　　　　　　｜投稿編集日時
