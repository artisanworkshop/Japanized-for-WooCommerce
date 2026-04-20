---
name: paygent-carrier
description: >
  Paygentキャリア決済スキル。auかんたん決済・d払い・ソフトバンクまとめて支払い(B)・
  ワイモバイルまとめて支払い の都度課金と継続課金をカバー。
  「paygent_mb」「WC_Gateway_Paygent_MB」「キャリア決済」「auかんたん決済」「d払い」
  「ソフトバンク」「携帯決済」「career_type」「addon-mb」などのキーワードで発動。
compatibility: >
  WooCommerce 9.0+ / WordPress 6.7+ / PHP 8.2+。
  継続課金はWooCommerce Subscriptions必須。
  各キャリアとPaygent経由で個別に契約が必要。
---

# Paygent キャリア決済

## このスキルを使う場面

- キャリア決済の実装・修正（`class-wc-gateway-paygent-mb.php`）
- career_typeによるキャリア判定とステータス管理
- キャリア別の取消・売上ロジック
- 継続課金（定期購入）実装

## ファイル構成

```
includes/gateways/paygent/
└── class-wc-gateway-paygent-mb.php  ← キャリア決済（都度課金＋継続課金を1ファイルで管理）
```

## ゲートウェイID

| ID | 説明 |
|---|---|
| `paygent_mb` | キャリア決済（都度課金・継続課金共通） |

## career_type（キャリア種別）— PDF仕様書準拠

| 値 | キャリア |
|---|---|
| `4` | auかんたん決済 |
| `5` | d払い（ドコモ） |
| `6` | ソフトバンクまとめて支払い(B) / ワイモバイルまとめて支払い |

コード内マッピング: `'docomo'→5`, `'au'→4`, `'sb'→6`（`set_career_type_num()`）。  
ユーザー選択: チェックアウトフォームの `career_type` POSTパラメータ（`04`/`05`/`06`の文字列で送信）。

## 主要 telegram_kind — 都度課金（PDF仕様書 v1.43 準拠）

| コード | 内容 |
|---|---|
| `100` | 携帯キャリア決済申込 |
| `101` | 携帯キャリア決済売上要求（消込） |
| `102` | 携帯キャリア決済取消要求 |
| `103` | 携帯キャリア決済補正売上要求（減額） |
| `104` | 携帯キャリア決済ユーザ認証要求（OpenID取得） |
| `105` | 携帯キャリア決済OpenID解除要求 |
| `109` | 携帯キャリア決済dアカウント・ログアウト要求 |

## 主要 telegram_kind — 継続課金（PDF仕様書準拠）

| コード | 内容 |
|---|---|
| `120` | 携帯キャリア決済継続課金申込 |
| `121` | 携帯キャリア決済継続課金売上要求 |
| `122` | 携帯キャリア決済継続課金取消要求 |
| `124` | 携帯キャリア決済継続課金終了 |
| `125` | 携帯キャリア決済継続課金情報照会 |
| `126` | 携帯キャリア継続課金変更 |

## 100 申込 主要パラメータ

| パラメータ名 | 必須 | 内容 |
|---|---|---|
| `career_type` | ○ | キャリア種別（4/5/6） |
| `amount` | ○ | 請求金額（payment_amountではない） |
| `return_url` | ○ | オーソリ通知URL（512byte） |
| `cancel_url` | ○ | キャンセルURL（512byte） |
| `other_url` | ○(d払い) | d払い「購入画面へ戻る」URL |
| `pc_mobile_type` | ○ | 端末区分（0=PC, 4=スマートフォン等） |
| `open_id` | △ | OpenID（d払い/au必須条件あり） |
| `outline` | ▲ | 商品摘要（包括契約では不要） |

**注意**: キャリア決済申込の金額パラメータは `amount`（クレジット等の `payment_amount` ではない）。

## 100 申込 応答

- **redirect_url**: キャリア決済画面URL（au/d払い/SB(A)）
- **redirect_html**: キャリア決済POSTフォーム（d払い/SB等）

## 都度課金ステータス遷移

| payment_status | 内容 |
|---|---|
| `20` | オーソリOK |
| `21` | オーソリ完了 |
| `32` | オーソリ取消済 |
| `40` | 消込済 |
| `44` | 消込完了 |
| `60` | 売上取消済 |
| `62` | 取消完了 |

## 取消可能ステータス（キャリア別）

| キャリア | 取消可能なステータス |
|---|---|
| d払い | 20（オーソリOK）, 21（オーソリ完了）, 40（消込済） |
| auかんたん決済 | 20（オーソリOK）, 40（消込済） |
| SB(B)/Y!mobile | 20, 21（オーソリ系）, 40（消込済） |

## pc_mobile_type

| 値 | 端末 |
|---|---|
| `0` | PC |
| `1` | フィーチャーフォン（docomo） |
| `2` | フィーチャーフォン（au） |
| `3` | フィーチャーフォン（softbank） |
| `4` | スマートフォン |

詳細は [carrier-payment.md](references/carrier-payment.md) と [carrier-subscription.md](references/carrier-subscription.md) を参照。
