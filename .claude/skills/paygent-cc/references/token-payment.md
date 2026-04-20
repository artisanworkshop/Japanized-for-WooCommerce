# クレジットカード トークン決済フロー

## 概要

カード番号をPaygentのJavaScriptトークンライブラリで直接Paygentサーバーへ送信し、
マーチャントサイトはカード番号に触れずにトークンのみを受け取る方式（PCI DSS簡素化）。

## トークン取得（フロントエンド）

```php
// paygent_token_scripts_method() でJSを読み込み
public function paygent_token_scripts_method() {
    if ( is_checkout() ) {
        // Paygentのトークンライブラリを enqueue
        wp_enqueue_script( 'paygent-token', ... );
    }
}
```

フォームからのカード情報送信をインターセプトし、PaygentのAPIでトークン化してhidden fieldに格納する。

## process_payment() の基本フロー

```php
public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );

    // telegram_kind: '020' = トークン与信, '031' = 3DS2与信
    $telegram_kind = '020';

    $send_data = array(
        'trading_id'      => 'wc_' . $order_id,
        'payment_amount'  => $order->get_total(),
        'payment_class'   => $this->payment_method, // 10=1回払い等
        'token'           => sanitize_text_field( $_POST['paygent_token'] ),
        'out_appr_amount' => $order->get_total(),
    );

    // カード保存時
    if ( isset( $_POST['wc-paygent_cc-new-payment-method'] ) ) {
        $send_data['stock_card_flg'] = '1'; // カード情報保存
    }

    $response = $this->paygent_request->send_paygent_request(
        $this->test_mode, $order, $telegram_kind, $send_data, $this->debug
    );

    if ( '0' === $response['result'] ) {
        $order->set_transaction_id( $response['result_array'][0]['payment_id'] );
        $order->payment_complete();
        return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
    }

    $this->paygent_request->error_response( $response, $order );
    return array( 'result' => 'failure', 'redirect' => wc_get_checkout_url() );
}
```

## カード保存（トークナイゼーション）

```php
// カード登録 telegram_kind: '092'
// カード削除 telegram_kind: '093'

// カード削除フック
add_action( 'woocommerce_payment_token_deleted', array( $this, 'paygent_delete_card' ), 10, 2 );

public function paygent_delete_card( $token_id, $token ) {
    if ( 'paygent_cc' === $token->get_gateway_id() ) {
        $send_data = array(
            'trading_id' => 'wc_del_' . $token_id,
            'seq_merchant_id' => $token->get_token(), // Paygentのシーケンス番号
        );
        $this->paygent_request->send_paygent_request(
            $this->test_mode, null, '093', $send_data, $this->debug
        );
    }
}
```

## セキュリティ注意事項

- カード番号・CVVはサーバーに届かない（PCI DSS SAQ A対応）
- `$_POST['paygent_token']` は必ず `sanitize_text_field()` でサニタイズ
- デバッグログにトークン値が記録されないよう注意
- `store_card_info` 設定でカード保存機能を加盟店側でON/OFF可能
