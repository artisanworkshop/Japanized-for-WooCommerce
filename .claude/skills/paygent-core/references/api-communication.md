# Paygent API通信

## PaygentB2BModule の使い方

```php
use PaygentModule\System\PaygentB2BModule;

$process = new PaygentB2BModule();
$process->init();

// 必須パラメータ
$process->reqPut( 'merchant_id',      $merchant_id );
$process->reqPut( 'connect_id',       $connect_id );
$process->reqPut( 'connect_password', $connect_password );
$process->reqPut( 'telegram_kind',    $telegram_kind );
$process->reqPut( 'telegram_version', '1.0' );

// 決済固有パラメータを追加
foreach ( $send_data as $key => $value ) {
    $process->reqPut( $key, $value );
}

$process->post();

// レスポンス取得
$res_array = array();
while ( $process->hasResNext() ) {
    $res_array[] = $process->resNext();
}

$result = $process->getResultStatus();     // '0'=成功, '1'=エラー, その他=通信異常
$code   = $process->getResponseCode();
$detail = $process->getResponseDetail();   // SJIS エンコード
```

## 文字コード注意

PaygentB2BModuleはSJISで通信する。レスポンス値をログや画面表示する際は必ず変換すること：

```php
mb_convert_encoding( $value, 'UTF-8', 'SJIS' )
```

send_dataのvalueも同様にSJIS→UTF-8変換が必要な場合がある（`mb_convert_encoding($value, 'UTF-8', 'SJIS')`）。

## WC_Gateway_Paygent_Request::send_paygent_request()

全決済クラスから呼ぶ共通ラッパー。ハッシュチェック付与・デバッグログ保存を自動処理する。

```php
$response = $this->paygent_request->send_paygent_request(
    $this->test_mode,  // '1'=テスト, それ以外=本番
    $order,            // WC_Order or null
    $telegram_kind,    // 電文種別コード
    $send_data,        // リクエストパラメータ配列
    $this->debug       // 'yes'|'no'
);

// レスポンス構造
$response['result']         // '0'=成功
$response['responseCode']   // エラーコード
$response['responseDetail'] // エラー詳細（SJIS）
$response['result_array']   // レスポンスデータ配列（$res_array[0]が主データ）
```

## 主要 telegram_kind（電文種別）

| コード | 内容 | 決済手段 |
|---|---|---|
| `020` | クレジットカード 与信（トークン） | CC |
| `022` | クレジットカード 売上 | CC |
| `024` | クレジットカード 与信取消 | CC |
| `025` | クレジットカード 売上取消 | CC |
| `028` | クレジットカード 金額変更 | CC |
| `031` | クレジットカード 3DS2 与信 | CC |
| `092` | クレジットカード カード登録 | CC |
| `093` | クレジットカード カード削除 | CC |
| `094` | 情報照会（全決済共通） | 共通 |
| `200` | コンビニ決済 申込 | CS |
| `210` | コンビニ決済 取消 | CS |
| `300` | 仮想口座 申込 | ATM |
| `310` | 仮想口座 取消 | ATM |
| `320` | 銀行ネット 申込 | BN |
| `330` | 銀行ネット 取消 | BN |
| `420` | PayPay 申込 | PayPay |
| `430` | PayPay 取消 | PayPay |
| `440` | 楽天ペイ 申込 | Rakuten |
| `501` | キャリア決済 申込 | MB |
| `521` | Paidy 申込 | Paidy |
| `551` | 継続課金 登録 | MCCC/MB |

## trading_id の決定ロジック

```php
$paygent_order_id = $order->get_meta( '_paygent_order_id' );
if ( $paygent_order_id ) {
    $send_data['trading_id'] = $paygent_order_id;
} elseif ( $this->prefix_order ) {
    $send_data['trading_id'] = $this->prefix_order . $order->get_id();
} else {
    $send_data['trading_id'] = 'wc_' . $order_id;
}
```

`_paygent_order_id` は決済完了後にPaygentから返却されたtrading_idを保存したもの（初回申込時はWC側で生成）。
