# デジタル決済 取消・返金

## PayPay 返金

```php
// process_refund() 内での telegram_array
// 421: PayPay取消返金電文（申込取消・返金共通）
$telegram_array = array(
    'auth_cancel'  => '421', // PayPay 取消返金
    'sale_cancel'  => '421',
    'auth_change'  => '421',
    'sale_change'  => '421',
);
// repayment_amount: 返金金額（省略時は全額）
```

## 楽天ペイ 返金

楽天ペイは `woocommerce_order_status_completed` で売上計上が必要：

```php
add_action( 'woocommerce_order_status_completed', array( $this, 'order_rakuten_pay_status_completed' ) );
```

## Paidy 特殊処理

Paidyの返金時はtrading_idに2つのパターンがある（`_paygent_order_id`メタまたは`$order_id`）。
`order_paygent_status_completed()` 内でPaidy専用の再試行ロジックがある。

## payment_status の主要値

| 値 | 意味 |
|---|---|
| `10` | 申込中 |
| `20` | 決済完了 |
| `30` | 売上計上済み |
| `40` | 取消済み |
| `50` | 返金済み |

`094`（情報照会）のレスポンスで確認できる。
