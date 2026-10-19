# テーブル定義書

## productsテーブル

|カラム名　　　|データ型　　　　　　|制約　　　　　　　　　　　　|説明
|ID 　　　　　|BIGINT　　　　　　　|AUTO INCREMENT,PRIMARY KEY|商品1個ずつ区別
|NAME 　　　　|VARCHAR(20)　　　　　|NOT NULL　　　　　　　　　|商品名
|PRICE　　　　|INT　　　　　　　　　|NOT NULL　　　　　　　　　|商品の値段
|category　　|enum('drink','food')|not null　　　　　　　　　|ドリンクとフード区別
|is_available|boolean　　　　　　　|NOT NULL,DEFAULT TRUE　　|その日に売れるかどうか、売れきれたら　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　FALSE
|created_at　|timestamp　　　　　　|-　　　　　　　　　　　　|登録した日時
|updated_at　|timestamp　　　　　　|-　　　　　　　　　　　　|編集した日時

## ordersテーブル

|カラム名　　　|データ型　　|制約　　　　　　　　　　　　　|説明
|id　　　　　　|bigint　　　|primary key,auto_increment|注文1件ずつ区別
|order_number|BIGINT　　　|not null, unique　　　　　　|注文番号、5桁の番号
|total_amount|int　　　　　| not null　　　　　　　　　　|注文の合計金額
|status　　　|varchar(100)|not null　　　　　　　　　　　|注文成功の表示
|order_at　　|datetime　　|not null　　　　　　　　　　　|注文の時の日時
|created_at　|timestamp　|-　　　　　　　　　　　　　　　　|注文登録の日時
|updated_at　|timestamp　|-　　　　　　　　　　　　　　　　|注文編集の日時

## order_itemsテーブル

|カラム名　　|データ型 | 制約　　　　　 | 説明
|id |bigint |primary key, auto increment|各注文詳細の区別
|product_id|bigint |foreign key , not null |どの商品か
|order_id |bigint |foreign key, not null |どの注文の詳細か
|quantity |int |not null |商品個数
|unit_price|int |not null |単価
|created_at|timestamp|- |登録した日時
|updated_at|timestamp|- |編集した日時
