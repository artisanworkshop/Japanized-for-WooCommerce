---
name: paygent-cc
description: >
  Paygentクレジットカード決済スキル。通常カード決済（トークン決済）、EMV 3Dセキュア 2.0、
  継続課金（WooCommerce Subscriptions連携）、多通貨決済、デビット・プリペイド判定、
  カード情報保存（トークナイゼーション）をカバー。
  「paygent_cc」「WC_Gateway_Paygent_CC」「tds2」「3Dセキュア」「継続課金」「トークン決済」
  「クレジットカード」「paygent_mccc」「多通貨」「addon-cc」などのキーワードで発動。
compatibility: >
  WooCommerce 9.0+ / WordPress 6.7+ / PHP 8.2+。
  継続課金はWooCommerce Subscriptions必須。
  EMV 3DS2はPaygent側でオプション契約が必要。
---

# Paygent クレジットカード決済

## このスキルを使う場面

- クレジットカード決済フローの実装・修正（`class-wc-gateway-paygent-cc.php`）
- EMV 3Dセキュア 2.0 の実装（`tds2_check`、`paygent_3ds2_redirect_order()`）
- カード情報保存・削除（WooCommerceトークナイゼーション）
- WooCommerce Subscriptions 連携（継続課金）
- 多通貨カード決済（`class-wc-gateway-paygent-mccc.php`）
- デビット・プリペイド判定
- 支払い方法の設定（1回払い/分割/ボーナス/リボ）

## ファイル構成

```
includes/gateways/paygent/
├── class-wc-gateway-paygent-cc.php       ← メインクレジットカード
├── class-wc-gateway-paygent-mccc.php     ← 多通貨クレジットカード
└── class-wc-gateway-paygent-addon-cc.php ← 継続課金アドオン
```

## ゲートウェイID

| クラス | ID |
|---|---|
| `WC_Gateway_Paygent_CC` | `paygent_cc` |
| `WC_Gateway_Paygent_MCCC` | `paygent_mccc` |

## 主要 telegram_kind（仕様書 v2.8.21 準拠）

| コード | 内容 |
|---|---|
| `020` | CC オーソリ申込（メイン決済電文） |
| `022` | CC 売上（出荷売上、キャプチャ） |
| `023` | CC オーソリ取消 |
| `025` | カード情報登録 |
| `026` | カード情報変更 |
| `027` | カード情報削除 |
| `028` | CC 売上取消（返金） |
| `029` | CC 補正（金額変更） |
| `094` | 決済情報照会 |

## 020 オーソリ申込 — 主要パラメータ

| パラメータ名 | 必須 | 内容 |
|---|---|---|
| `card_token` | △ | JSトークンライブラリで生成したカードトークン |
| `stock_card_mode` | ▲ | 0=通常, 1=カード情報保存 |
| `customer_id` | △ | 保存カード使用時の顧客ID（25byte） |
| `customer_card_id` | △ | 保存カードID（18byte） |
| `3dsecure_use_type` | ▲ | 1=3DS1, 2=EMV3DS2.0 |
| `3ds_auth_id` | △ | EMV3DS認証ID（36byte） |
| `sales_mode` | ▲ | 0=オーソリのみ, 1=即時売上 |
| `payment_class` | ▲ | 10=1回払い, 23=ボーナス, 61=分割, 80=リボ |
| `payment_amount` | ○ | 決済金額 |

**重要**: 2017年4月以降契約の加盟店はカード番号の直接送信不可。必ずトークン（card_token）または保存カード（customer_card_id）を使用。

## 020 オーソリ応答 — 主要フィールド

| フィールド | 内容 |
|---|---|
| `result` | 0=正常, 1=異常, 7=3Dオーソリ必要 |
| `payment_id` | 決済ID |
| `fingerprint` | 継続課金用フィンガープリント |
| `masked_card_number` | マスクされたカード番号 |
| `3dsecure_message_version` | 3DSメッセージバージョン |

result=7 の場合は EMV 3DS2.0フローへ移行。

## 支払い方法コード（payment_class）

| コード | 支払い方法 |
|---|---|
| `10` | 1回払い |
| `61` | 分割払い |
| `23` | ボーナス一括 |
| `80` | リボルビング |

## supports（クレジットカード）

```php
$this->supports = array(
    'subscriptions',
    'products',
    'subscription_cancellation',
    'subscription_reactivation',
    'subscription_suspension',
    'subscription_amount_changes',
    'subscription_payment_method_change_customer',
    'subscription_payment_method_change_admin',
    'subscription_date_changes',
    'multiple_subscriptions',
    'tokenization',
    'refunds',
    'default_credit_card_form',
);
```

詳細は各referencesファイルを参照：
- [token-payment.md](references/token-payment.md) — トークン決済フロー
- [3ds2.md](references/3ds2.md) — EMV 3Dセキュア 2.0
- [subscription.md](references/subscription.md) — 継続課金
- [multi-currency.md](references/multi-currency.md) — 多通貨・デビット判定
