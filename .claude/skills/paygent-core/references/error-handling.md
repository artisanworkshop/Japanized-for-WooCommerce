# エラーハンドリング・デバッグ

## レスポンス結果の判定

```php
if ( '0' === $response['result'] ) {
    // 成功
} elseif ( '1' === $response['result'] ) {
    // システムエラー（responseCode, responseDetailに詳細）
    $this->paygent_request->error_response( $response, $order );
} else {
    // 通信異常・予期しないレスポンス
    $this->paygent_request->error_response( $response, $order );
}
```

## error_response() の動作

- `result === '1'`（システムエラー）: responseCodeとresponseDetailをオーダーノートに記録し、チェックアウト画面にエラー通知
- その他: 「決済サーバーからの応答なし」メッセージ
- responseDetailはSJISエンコードなので`mb_convert_encoding($detail, 'UTF-8', 'auto')`が必要

## 主要エラーコード（1G系 = イシュアーエラー）

| コード | 意味 |
|---|---|
| `1G02` | カードローン残高不足 |
| `1G06` | デビットカード残高不足 |
| `1G12` | カード使用不可 |
| `1G22` | 永久利用停止 |
| `1G30` | 有人判断待ち |
| `1G42` | PINコードエラー |
| `1G44` | カード確認番号誤り |
| `1G60` | 事故カード |
| `1G61` | 無効カード |
| `1G83` | 有効期限エラー |

完全なリストは `WC_Gateway_Paygent_Request::error_text()` を参照。

## デバッグログ

```php
// JP4WCフレームワーク経由でWooCommerceログに記録
$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'wc-paygent' );

// $debug = 'yes' の時のみ記録
// WooCommerce > ステータス > ログ で確認可能
// ログファイル名: wc-paygent-YYYY-MM-DD-{hash}.log
```

送信データと受信データは両方ともデバッグログに自動記録される（`send_paygent_request()`内）。
本番環境では`debug`設定を`no`にすること（PCI DSS要件：カード番号をログに記録しない）。

## WooCommerce通知

```php
// チェックアウト画面へのエラー通知
if ( is_checkout() ) {
    wc_add_notice( $message, 'error' );
}

// オーダーノートへの記録
$order->add_order_note( $message );
```
