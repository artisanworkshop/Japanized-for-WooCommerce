# 仮想口座・銀行ネット・口座振替

## 仮想口座決済（ATM）

顧客専用の仮想口座番号を発行し、ATMや銀行振込で支払う方式。

```php
class WC_Gateway_Paygent_ATM extends WC_Payment_Gateway {
    public $payment_detail;       // 口座名義（カナ）
    public $payment_detail_kana;  // 口座名義（カナ）
    public $payment_limit_date;   // 支払い期限（日数）
}
```

```php
// 申込 telegram_kind: '300'
$send_data = array(
    'trading_id'         => 'wc_' . $order_id,
    'payment_amount'     => $order->get_total(),
    'payment_limit_date' => date( 'Ymd', strtotime( '+' . $this->payment_limit_date . ' days' ) ),
    'payment_detail'     => mb_convert_encoding( $this->payment_detail, 'SJIS', 'UTF-8' ),
);

// レスポンスに口座番号が含まれる
$bank_code     = $response['result_array'][0]['bank_code'];
$branch_code   = $response['result_array'][0]['branch_code'];
$bank_account  = $response['result_array'][0]['bank_account'];
```

### ドキュメント

`2025docs/仮想口座決済/導入補足資料（仮想口座決済）_包括契約.pdf`
`2025docs/仮想口座決済/導入補足資料（仮想口座決済）_直接契約.pdf`

---

## 銀行ネット決済

ネットバンキング経由の即時決済。リダイレクト型。

```php
// telegram_kind: '320' 銀行ネット申込
// → リダイレクト → 銀行ネットバンキング画面 → コールバック
```

### ドキュメント

`2025docs/銀行ネット決済.pdf`

---

## 口座振替決済

顧客の銀行口座から定期的に引き落とす方式。

- モジュールタイプ: `2025docs/口座振替決済/02_PG外部インターフェース仕様説明書（別紙：口座振替）.pdf`
- リンクタイプ: `2025docs/口座振替決済/02_リンクタイプインターフェース仕様説明書（別紙：口座振替受付）.pdf`
- 導入: `2025docs/口座振替決済/導入補足資料（口座振替決済）.pdf`

---

## 電子マネー（WebMoney）

`2025docs/電子マネー決済（WebMoney）.pdf`

---

## Webhook経由の入金確認

後払い系（コンビニ・仮想口座）は入金後にPaygentからWebhookが送信される：

```
POST /wp-json/paygent/v1/check
{
    "trading_id": "wc_123",
    "payment_id": "xxx",
    "payment_status": "30"  // 売上計上済み
}
```

`WC_Paygent_Endpoint::paygent_check_webhook()` でオーダーを特定し、
`payment_complete()` を呼び出してステータスを更新する。
