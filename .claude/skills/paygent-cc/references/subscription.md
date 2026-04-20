# 継続課金（WooCommerce Subscriptions）

## 概要

WooCommerce Subscriptions プラグインと連携する継続課金。
`class-wc-gateway-paygent-addon-cc.php` がサブスクリプション固有のフックを処理。
メインの `WC_Gateway_Paygent_CC` で `subscriptions` supports を宣言済み。

## 主要 supports

```php
$this->supports = array(
    'subscriptions',
    'subscription_cancellation',
    'subscription_reactivation',
    'subscription_suspension',
    'subscription_amount_changes',
    'subscription_payment_method_change_customer',
    'subscription_payment_method_change_admin',
    'subscription_date_changes',
    'multiple_subscriptions',
);
```

## アドオンクラス（class-wc-gateway-paygent-addon-cc.php）

継続課金の定期決済・ステータス管理を担当するフックを登録。

```php
// 定期決済実行
add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id,
    array( $this, 'scheduled_subscription_payment' ), 10, 2 );

// サブスクリプション取消
add_action( 'woocommerce_subscription_cancelled_' . $this->id,
    array( $this, 'cancel_subscription' ) );
```

## telegram_kind（継続課金）

| コード | 内容 |
|---|---|
| `551` | 継続課金登録 |
| `020` | 定期決済（トークン決済と同じ） |
| `025` | 定期決済取消 |

## wcs_order_contains_subscription() の使用

返金処理では、サブスクリプション注文の場合に`payment_id`をunsetする特別処理がある：

```php
if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id ) ) {
    unset( $send_data_refund['payment_id'] );
}
```

## ドキュメント参照

`2025docs/creditcard/モジュール/02_PG外部インターフェース仕様説明書（別紙：クレジットカード決済継続課金）.pdf`
`2025docs/creditcard/モジュール・リンク共通/導入補足資料（クレジットカード決済継続課金）.pdf`
