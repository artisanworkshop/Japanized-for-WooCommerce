# デジタル決済フロー詳細

## PayPay フロー（telegram `420`）

```php
// process_payment() の核心部分
$telegram_kind           = '420'; // PayPay 申込
$send_data['trading_id'] = $prefix_order ? $prefix_order . $order_id : 'wc_' . $order_id;
$send_data['payment_id']     = '';
$send_data['payment_amount'] = $order->get_total();
$send_data['cancel_url']     = $order->get_cancel_order_url_raw();
$send_data['return_url']     = $this->get_return_url( $order );

$response = $this->paygent_request->send_paygent_request(
    $this->test_mode, $order, $telegram_kind, $send_data, $this->debug
);

if ( '0' === $response['result'] ) {
    $order->set_transaction_id( $response['result_array'][0]['payment_id'] );
    $order->add_meta_data( '_paygent_order_id', $response['result_array'][0]['trading_id'], true );
    // redirect_html を保存（改行コード正規化、不要行削除）
    $redirect_html       = str_replace( array( "\r\n", "\r" ), "\n", $response['result_array'][0]['redirect_html'] );
    $redirect_html_array = explode( "\n", $redirect_html );
    unset( $redirect_html_array[31] ); // セキュリティ上の不要スクリプト行を除去
    $order->add_meta_data( '_paygent_paypay_html', $redirect_html_array );
    $order->save();
}
// → get_checkout_payment_url(true) へリダイレクト（woocommerce_receipt_でHTML出力）
```

## woocommerce_receipt_ フックでのリダイレクト

```php
add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'paypay_redirect_order' ) );

public function paypay_redirect_order( $order_id ) {
    $order     = wc_get_order( $order_id );
    $html_data = $order->get_meta( '_paygent_paypay_html' );
    // 保存済みHTMLを出力してPayPayアプリ/ページへリダイレクト
    echo implode( "\n", array_map( 'wp_kses_post', $html_data ) );
}
```

## woocommerce_thankyou_ での結果確認

```php
add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'paygent_paypay_thankyou' ), 10, 1 );

public function paygent_paypay_thankyou( $order_id ) {
    // telegram_kind '094' で決済ステータス照会
    $status = $this->paygent_request->paygent_get_payment_status( $order, $this );
    if ( $status && in_array( $status['payment_status'], array( '20', '30' ) ) ) {
        $order->payment_complete();
    }
}
```

## Paidy フロー（telegram `340`/`341`）

PaidyはPayPay申込のような初回申込電文はなく、340（オーソリキャンセル）と341（売上確定）のみ。

```php
// 取消・返金時のtrading_id取得ロジックが追加
} elseif ( 'paygent_paidy' === $order_payment_method ) {
    // Paidy Payment. trading_id が order_id の場合もある
    $send_data['trading_id'] = $order_id;
    $response_again = $this->send_paygent_request( ... );
}
```

## 楽天ペイ フロー（telegram `270`）

PayPayと同様のリダイレクト型。`_paygent_rakuten_html`メタに保存。

## ドキュメント参照

- PayPay: `2025docs/PayPay/02_PG外部インターフェース仕様説明書（別紙：PayPay）.pdf`
- Paidy: `2025docs/Paidy/02_PG外部インターフェース仕様説明書（別紙：Paidy）.pdf`
- 楽天ペイ: `2025docs/楽天ペイ/02_PG外部インターフェース仕様説明書（別紙：楽天ペイ）.pdf`
- Apple Pay: `2025docs/ApplePay/02_PG外部インターフェース仕様説明書（別紙：Apple Pay）.pdf`
- Google Pay: `2025docs/GooglePay/02_PG外部インターフェース仕様説明書（別紙：Google Pay）.pdf`
- Alipay: `2025docs/Alipay国際決済/02_PG外部インターフェース仕様説明書（別紙：Alipay国際決済）.pdf`
- 銀聯: `2025docs/銀聯ネット決済/02_PG外部インターフェース仕様説明書（別紙：銀聯ネット決済）.pdf`
