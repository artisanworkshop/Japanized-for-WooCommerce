# インストール・はじめに

[← ドキュメントトップ](index.md)

---

## 動作要件

| 項目 | 要件 |
|------|------|
| PHP | 8.1 以上 |
| WordPress | 6.7 以上 |
| WooCommerce | 8.0 以上 |

---

## インストール方法

### WordPress 管理画面からインストール

1. WordPress 管理画面の **プラグイン → 新規追加** を開きます。
2. 検索欄に「Japanized for WooCommerce」と入力します。
3. 表示されたプラグインの **今すぐインストール** をクリックします。
4. インストール完了後、**有効化** をクリックします。

### 手動インストール

1. [WordPress.org のプラグインページ](https://ja.wordpress.org/plugins/woocommerce-for-japan/) から ZIP ファイルをダウンロードします。
2. WordPress 管理画面の **プラグイン → 新規追加 → プラグインのアップロード** を選択します。
3. ダウンロードした ZIP ファイルをアップロードし、**インストール** をクリックします。
4. インストール完了後、**有効化** をクリックします。

---

## 設定画面へのアクセス

プラグインを有効化すると、WordPress 管理画面の **WooCommerce** メニューに **JP4WC Settings** が追加されます。

設定画面は以下のタブで構成されています。

| タブ名 | 内容 |
|--------|------|
| 一般設定 (General) | 住所フォーム・読み仮名・郵便番号自動入力など |
| 配送設定 (Shipment) | 配送日時指定・配送不可曜日・時間帯など |
| 決済設定 (Payment) | 各種決済方法の有効化・設定 |
| 特商法設定 (Law) | 特定商取引法表記の内容管理 |
| アフィリエイト (Affiliate) | A8.net・Felmat の設定 |

---

## WooCommerce ブロックチェックアウトへの対応

WooCommerce 8.3 以降でブロックチェックアウトを使用している場合も、読み仮名フィールドや配送日時フィールドはブロックチェックアウト内に自動で表示されます。追加の設定は必要ありません。

> **注意**: ブロックチェックアウトのフィールド表示には WooCommerce 9.3 以上が必要です。

---

## アップデート時の注意

- アップデートはステージングサイトで事前に動作確認を行ってください。
- アップデート後に問題が発生した場合は、[GitHub Issues](https://github.com/shoheitanaka/woocommerce-for-japan/issues) または [WordPress.org サポートフォーラム](https://wordpress.org/support/plugin/woocommerce-for-japan/) へご報告ください。

---

[← ドキュメントトップ](index.md)
