# 認証・ハッシュチェック

## ハッシュチェック（不正リクエスト防止）

ハッシュチェックが有効な場合、リクエストに `hc`（ハッシュ値）と `request_date` を付与する。

```php
if ( get_option( 'wc-paygent-hash_check' ) ) {
    $hash_code = $test_mode ? get_option( 'wc-paygent-test-hash_code' )
                            : get_option( 'wc-paygent-hash_code' );

    $hash_data = array(
        'merchant_id'      => $merchant_id,
        'connect_id'       => $connect_id,
        'connect_password' => $connect_password,
        'telegram_kind'    => $telegram_kind,
        'telegram_version' => '1.0',
        'trading_id'       => $send_data['trading_id'],
    );
    if ( isset( $send_data['payment_id'] ) ) {
        $hash_data['payment_id'] = $send_data['payment_id'];
    }
    if ( isset( $send_data['payment_amount'] ) ) {
        $hash_data['payment_amount'] = $send_data['payment_amount'];
    } elseif ( isset( $send_data['amount'] ) ) {
        $hash_data['payment_amount'] = $send_data['amount'];
    }
    $hash_data['request_date'] = date_i18n( 'YmdHis' );
    $send_data['request_date'] = date_i18n( 'YmdHis' );
    $send_data['hc']           = $this->make_hash_data( $hash_data, $hash_code );
}
```

## make_hash_data() の実装

```php
public function make_hash_data( array $hash_data, string $hash_code ): string {
    $header_text = '';
    foreach ( $hash_data as $value ) {
        if ( isset( $value ) ) {
            $header_text .= $value;
        }
    }
    $header_text .= $hash_code;
    return hash( 'sha256', $header_text );
}
```

値を結合した文字列の末尾にハッシュコードを付加し、SHA-256でハッシュ化する。
配列の順序が重要（merchant_id → connect_id → connect_password → telegram_kind → telegram_version → trading_id → [payment_id] → [payment_amount] → request_date）。

## テスト/本番切り替え

```php
// テストモードの判定
$test_mode = get_option( 'wc-paygent-testmode' ); // '1' = テスト

// merchant_data() で認証情報を切り替え
public function merchant_data( $test_mode ): array {
    if ( '1' === $test_mode ) {
        return array(
            'merchant_id'      => $this->merchant_test_id,
            'connect_id'       => $this->connect_test_id,
            'connect_password' => $this->connect_test_password,
        );
    }
    return array(
        'merchant_id'      => $this->merchant_id,
        'connect_id'       => $this->connect_id,
        'connect_password' => $this->connect_password,
    );
}
```

## Webhook認証（REST API）

```php
// POST /wp-json/paygent/v1/check
// WC_Paygent_Endpoint::paygent_permission_callback()
// Paygentからの通知を受信し、payment_idでオーダーを特定して処理

// 重要：permission_callbackはIPホワイトリスト等で保護を検討すること
// Paygentの送信元IPを確認し、外部アクセスをブロックする
```
