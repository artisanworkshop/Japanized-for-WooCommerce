---
name: paygent-bank
description: >
  Paygent銀行・コンビニ系決済スキル。仮想口座決済（ATM）・銀行ネット決済・口座振替・
  コンビニ決済（番号方式・チケット発券）・電子マネー（WebMoney）をカバー。
  「paygent_atm」「WC_Gateway_Paygent_ATM」「paygent_cs」「WC_Gateway_Paygent_CS」
  「paygent_bn」「WC_Gateway_Paygent_BN」「仮想口座」「コンビニ」「銀行ネット」「口座振替」
  などのキーワードで発動。
compatibility: >
  WooCommerce 9.0+ / WordPress 6.7+ / PHP 8.2+。
---

# Paygent 銀行・コンビニ系決済

## このスキルを使う場面

- コンビニ決済の実装・修正（`class-wc-gateway-paygent-cs.php`）
- 仮想口座（ATM）決済の実装・修正（`class-wc-gateway-paygent-atm.php`）
- 銀行ネット決済の実装・修正（`class-wc-gateway-paygent-bn.php`）
- 支払い期限・支払い番号の表示
- 入金確認Webhook処理
- 口座振替（direct debit）の実装

## ファイル構成

```
includes/gateways/paygent/
├── class-wc-gateway-paygent-cs.php   ← コンビニ決済
├── class-wc-gateway-paygent-atm.php  ← 仮想口座（ATM）
└── class-wc-gateway-paygent-bn.php   ← 銀行ネット
```

## ゲートウェイID

| 決済手段 | ID | クラス |
|---|---|---|
| コンビニ決済 | `paygent_cs` | `WC_Gateway_Paygent_CS` |
| 仮想口座（ATM） | `paygent_atm` | `WC_Gateway_Paygent_ATM` |
| 銀行ネット | `paygent_bn` | `WC_Gateway_Paygent_BN` |

## 主要 telegram_kind（PDF仕様書準拠）

| コード | 決済 | 内容 |
|---|---|---|
| `010` | 仮想口座（ATM） | 申込 |
| `030` | コンビニ | 申込 |
| `040` | 銀行ネット | 申込 |
| `060` | 口座振替 | 申込 |
| `070` | 電子マネー（WebMoney） | 申込 |
| `094` | 全決済共通 | 照会 |

## ATM（010）申込 主要パラメータ

| パラメータ名 | 必須 | 内容 |
|---|---|---|
| `payment_amount` | ○ | 決済金額 |
| `payment_detail` | ○ | 口座名義（SJIS変換必要） |
| `payment_detail_kana` | ▲ | 口座名義カナ |
| `payment_limit_date` | ○ | 支払い期限（Ymd形式、0〜60日、デフォルト30日） |
| `site_id` | ▲ | サイトID |

応答に口座番号（`bank_code`, `branch_code`, `bank_account`）が含まれる。

## コンビニ（030）申込 主要パラメータ

| パラメータ名 | 必須 | 内容 |
|---|---|---|
| `payment_amount` | ○ | 決済金額 |
| `cs_type` | ○ | コンビニ種別 |
| `payment_limit_date` | ○ | 支払い期限（Ymd形式） |

### 対応コンビニ（cs_type）

| コード | コンビニ名 |
|---|---|
| `00` | セブン-イレブン |
| `10` | ローソン / ミニストップ |
| `21` | ファミリーマート |
| `31` | デイリーヤマザキ等 |

## 共通特徴

- 後払い型：決済申込後、顧客が後日コンビニ/ATM/銀行で支払い
- WooCommerceのオーダーステータスは申込後「保留（on-hold）」
- 入金完了はWebhook（`/wp-json/paygent/v1/check`）で受信
- `payment_status=30`（売上計上済）受信時に`payment_complete()`を呼び出し

## 入金確認Webhook

```
POST /wp-json/paygent/v1/check
{
    "trading_id": "wc_123",
    "payment_id": "xxx",
    "payment_status": "30"
}
```

`WC_Paygent_Endpoint::paygent_check_webhook()` がオーダーを特定してステータス更新。

詳細は [convenience-store.md](references/convenience-store.md) と [bank-payments.md](references/bank-payments.md) を参照。
