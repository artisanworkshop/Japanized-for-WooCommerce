# artws-security-hub

wc.artws.info で動かす WordPress プラグイン。Japanized for WooCommerce（JP4WC）のセキュリティ診断（Epic #256）のサーバー側を受け持つ。JP4WC の配布物には含めない（`plugins/README.md`）。

| フェーズ | 内容 | 状態 |
|---|---|---|
| S0（#261） | 土台（lint・テスト・CI）と JSON 形式の確定 | このディレクトリ |
| S1（#262） | Wordfence Intelligence V3 の取り込みと `/feed/v1/` の配信 | 未着手 |
| S2（#263） | REST `artws-sec/v1`（`POST /scan`・`GET /scan/{token}`・`DELETE /site`） | 未着手 |

JP4WC とやり取りする JSON の形式は、リポジトリ直下の `tests/fixtures/security-feed/`（README と JSON Schema）で定める。

## 秘密情報

このリポジトリは公開されている。API キー・署名の共通鍵・DB 情報はコードにもテストにも書かず、wc.artws.info の `wp-config.php` の定数（または環境変数）から読む。

```php
define( 'ARTWS_SEC_WORDFENCE_API_KEY', '...' ); // S1: Wordfence Intelligence V3 の API キー
```

レート制限などの乱用対策は、コードを読まれても成り立つ作りにする。

## 開発

```bash
cd plugins/artws-security-hub
composer install
composer lint     # phpcs（WordPress Coding Standards、prefix artws_sec_ / ARTWS_SEC_）
composer test     # phpunit
```

CI は `.github/workflows/artws-security-hub.yml`。このディレクトリか `tests/fixtures/security-feed/` を変える PR でだけ走る。

## wc.artws.info への反映

JP4WC のタグやリリースとは連動しない。手動で ZIP を作ってアップロードする。

```bash
cd plugins/artws-security-hub
rm -rf /tmp/artws-security-hub && mkdir -p /tmp/artws-security-hub
rsync -a --exclude-from=.distignore ./ /tmp/artws-security-hub/
(cd /tmp && rm -f artws-security-hub.zip && zip -r artws-security-hub.zip artws-security-hub)
```

1. wc.artws.info の管理画面 > プラグイン > 新規追加 > プラグインのアップロードで `/tmp/artws-security-hub.zip` を選び、「現在のものをアップロードしたもので置き換える」で更新する
2. 有効化し、必要な定数が `wp-config.php` にあることを確認する

`Update URI: false` を指定しているので、WordPress.org の同名プラグインから更新が届くことはない。実行時に Composer の依存は使わない（`vendor/` はテスト用で、ZIP には入れない）。
