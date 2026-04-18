# 返金・キャンセル処理

## paygent_process_refund() の構造

全決済共通の返金処理。情報照会→ステータス確認→取消/返金の2ステップ構成。

```php
$this->paygent_request->paygent_process_refund(
    $order_id,
    $amount,
    $telegram_array,    // ['auth_cancel'=>'XXX', 'sale_cancel'=>'XXX', 'auth_change'=>'XXX', 'sale_change'=>'XXX']
    $permit_statuses,   // ステータス条件配列
    $send_data_refund,  // 追加パラメータ
    $payment            // ゲートウェイオブジェクト
);
```

## telegram_array の例（クレジットカード）

```php
$telegram_array = array(
    'auth_cancel'  => '024', // 与信取消
    'sale_cancel'  => '025', // 売上取消
    'auth_change'  => '028', // 与信変更（部分返金）
    'sale_change'  => '028', // 売上変更（部分返金）
);
```

## 全額返金 vs 部分返金

- `$amount === $order_total`: 取消（auth_cancel または sale_cancel）
- `$amount < $order_total`: 金額変更（auth_change または sale_change）

部分返金成功時は新しい`payment_id`が返却され、オーダーのtransaction_idを更新する。

## order_paygent_status_completed()

WooCommerceのオーダーステータスが「完了」になった時に与信→売上計上を行う。

```php
// 「完了」ステータスへの遷移時に売上計上する場合
add_action( 'woocommerce_order_status_completed', array( $this, 'order_paygent_cc_status_completed' ) );

public function order_paygent_cc_status_completed( $order_id ) {
    $telegram_kind = '022'; // 売上計上
    $this->paygent_request->order_paygent_status_completed(
        $order_id,
        $telegram_kind,
        $this
    );
}
```

`paymentaction === 'auth'`（与信のみ）の設定時のみ売上計上が実行される。
`paymentaction === 'sale'`の場合は決済時に即売上計上済みのため実行不要。
