# 多通貨・デビット/プリペイド判定

## 多通貨クレジットカード（MCCC）

`WC_Gateway_Paygent_MCCC`（`class-wc-gateway-paygent-mccc.php`）
ゲートウェイID: `paygent_mccc`

通常の `paygent_cc` と同様のフローだが、通貨コードを追加送信する。

```php
// 多通貨対応の追加パラメータ
$send_data['currency_code'] = get_woocommerce_currency(); // 例: 'USD', 'EUR'
```

## ドキュメント参照

`2025docs/多通貨クレジットカード決済/02_PG外部インターフェース仕様説明書（別紙：カード決済（多通貨））.pdf`
`2025docs/多通貨クレジットカード決済/導入補足資料（カード決済_多通貨）.pdf`

## デビット・プリペイド判定

`2025docs/creditcard/モジュール/02_PG外部インターフェース仕様説明書（別紙：デビット・プリペイド判定）.pdf`

レスポンスの `card_type` フィールドでカード種別を判定可能：
- `0`: クレジットカード
- `1`: デビットカード  
- `2`: プリペイドカード

加盟店側でデビット/プリペイドカードの受付可否を制御できる。

## カード情報更新（洗替）

`2025docs/creditcard/モジュール・リンク共通/導入補足資料（カード情報更新（洗替））.pdf`

保存済みカードの有効期限切れ時に自動更新する機能。
`delete_expired_cards` 設定で期限切れカードの自動削除が可能。
`class-jp4wc-card-expiry-notifier.php` で期限切れ通知を実装。
