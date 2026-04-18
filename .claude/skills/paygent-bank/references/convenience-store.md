# コンビニ決済

## 概要

番号方式のコンビニ決済。申込完了後に顧客に支払い番号を発行し、コンビニで支払う。

## クラスの主要プロパティ

```php
class WC_Gateway_Paygent_CS extends WC_Payment_Gateway {
    public $cs_stores;                    // 利用可能コンビニ配列
    public $cs_slip_label;                // 支払い番号のラベル名
    public $cs_description_to_customer;  // 顧客向け説明文
    public $payment_limit_date;          // 支払い期限（日数）
}
```

## 対応コンビニ

| コード | コンビニ名 |
|---|---|
| `00` | セブン-イレブン |
| `10` | ローソン / ミニストップ |
| `21` | ファミリーマート |
| `31` | デイリーヤマザキ等 |

## process_payment() の基本フロー

```php
$telegram_kind = '030'; // コンビニ申込（030が正しいコード）

$send_data = array(
    'trading_id'         => 'wc_' . $order_id,
    'payment_amount'     => $order->get_total(),
    'payment_limit_date' => date( 'Ymd', strtotime( '+' . $this->payment_limit_date . ' days' ) ),
    'cs_type'            => $cs_type, // コンビニ種別
);

$response = $this->paygent_request->send_paygent_request( ... );

if ( '0' === $response['result'] ) {
    // 支払い番号・バーコード情報をオーダーメタに保存
    $order->add_meta_data( '_paygent_cs_payment_no', $response['result_array'][0]['payment_no'] );
    $order->add_meta_data( '_paygent_cs_conf_no',    $response['result_array'][0]['conf_no'] );
    $order->update_status( 'on-hold' ); // 入金待ち
}
```

## 支払い番号の表示

```php
// woocommerce_thankyou_ / woocommerce_view_order で表示
$payment_no = $order->get_meta( '_paygent_cs_payment_no' );
$conf_no    = $order->get_meta( '_paygent_cs_conf_no' );
```

## コンビニチケット発券

`2025docs/コンビニ決済+番号方式+コンビニチケット発券サービス.pdf`
バーコード形式での支払い番号提供（追加オプション）。

## ドキュメント参照

`2025docs/コンビニ決済+番号方式.pdf`
