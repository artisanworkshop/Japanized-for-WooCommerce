---
name: paygent-core
description: >
  Paygent決済ゲートウェイの共通コア処理スキル。PaygentB2BModule経由のAPI通信、
  認証情報管理、ハッシュチェック、エラーハンドリング、注文IDマッピング、Webhook受信、
  デバッグログ、テスト環境設定など全決済手段で共有される基盤処理をカバー。
  「paygent API」「telegram_kind」「PaygentB2BModule」「send_paygent_request」
  「wc-paygent-mid」「ハッシュチェック」「WC_Gateway_Paygent_Request」などのキーワードで発動。
compatibility: >
  WooCommerce 9.0+ / WordPress 6.7+ / PHP 8.2+。
  PaygentB2BModuleはvendor-wc/paygent/connect/に内包。
  JP4WCフレームワーク v2.0.13を使用。
---

# Paygent Core — 共通処理

## このスキルを使う場面

- `WC_Gateway_Paygent_Request` クラスの実装・修正
- PaygentB2BModule を使ったAPI送受信処理
- ハッシュチェック（`hc`パラメータ）の実装
- 認証情報（merchant_id / connect_id / connect_password）管理
- 注文IDマッピング（`trading_id` / `_paygent_order_id`）
- Webhook受信エンドポイント（`/wp-json/paygent/v1/check`）
- テスト環境 / 本番環境の切り替え
- エラーコード処理・デバッグログ
- 全決済手段に共通するリファンド処理

## ファイル構成（コア関連）

```
includes/gateways/paygent/
├── includes/class-wc-gateway-paygent-request.php  ← コアAPIクライアント
├── class-wc-paygent-endpoint.php                  ← WP REST API Webhook
vendor-wc/paygent/connect/src/paygent_module/
├── System/PaygentB2BModule.php                    ← Paygent公式モジュール
├── modenv_properties.php                          ← 本番環境設定
└── sandbox_modenv_properties.php                  ← テスト環境設定
```

## 共通ヘッダパラメータ（全電文 No.1〜7）

| No | 項目名 | パラメータ名 | サイズ | 必須 |
|---|---|---|---|---|
| 1 | マーチャントID | merchant_id | 9byte | ○ |
| 2 | 接続ID | connect_id | 32byte | ○ |
| 3 | 接続パスワード | connect_password | 32byte | ○ |
| 4 | 電文種別ID | telegram_kind | 3byte | ○ |
| 5 | 電文バージョン番号 | telegram_version | 6byte | ○ |
| 6 | 決済ID | payment_id | 18byte | △ |
| 7 | マーチャント取引ID | trading_id | 25byte | △ |

**trading_id制約**: 半角英数字とアンダーバーのみ（その他記号不可）、最大25byte、Paygent側では重複チェックなし。

## 文字コード

**全Paygent API通信はShift_JIS (SJIS-win / Windows-31J)**。

```php
// リクエスト: UTF-8 → SJIS
$data = mb_convert_encoding( $data, 'SJIS', 'UTF-8' );

// レスポンス: SJIS → UTF-8
$value = mb_convert_encoding( $val, 'UTF-8', 'SJIS' );
```

## 認証情報（WordPress options）

| option key | 説明 |
|---|---|
| `wc-paygent-mid` | 本番 merchant_id |
| `wc-paygent-cid` | 本番 connect_id |
| `wc-paygent-cpass` | 本番 connect_password |
| `wc-paygent-test-mid` | テスト merchant_id |
| `wc-paygent-test-cid` | テスト connect_id |
| `wc-paygent-test-cpass` | テスト connect_password |
| `wc-paygent-sid` | site_id（マルチサイト向け） |
| `wc-paygent-prefix_order` | trading_idプレフィックス |
| `wc-paygent-testmode` | テストモード（'1'=ON） |
| `wc-paygent-hash_check` | ハッシュチェック有効化 |
| `wc-paygent-hash_code` | 本番ハッシュコード |
| `wc-paygent-test-hash_code` | テストハッシュコード |

## クイックリファレンス

```php
// API送信
$response = $this->paygent_request->send_paygent_request(
    $this->test_mode, $order, $telegram_kind, $send_data, $this->debug
);

// 結果判定
if ( '0' === $response['result'] ) {
    $payment_id = $response['result_array'][0]['payment_id'];
} else {
    // エラー: response_code / response_detail
    $this->paygent_request->error_response( $response, $order );
}
```

## 応答 result コード

| result | 意味 |
|---|---|
| `0` | 正常 |
| `1` | 異常 |
| `7` | 3Dオーソリ必要（クレジットカード020のみ） |
| `2` | 受付完了（一部電文、例: PayPay増額売上） |

## 決済情報照会 (094)

全決済手段共通の照会電文。payment_id または trading_id を指定して決済ステータスを取得。

詳細は [api-communication.md](references/api-communication.md) と [authentication.md](references/authentication.md) を参照。
