# キャリア決済（都度課金）

## フロー

リダイレクト型。career_typeで各キャリアのページへ誘導。

```
process_payment() → 申込（100）→ OpenID取得（104、d払い/auは事前取得）
→ redirect_url または redirect_html でキャリア認証ページへ
→ コールバック → woocommerce_thankyou_ → 結果確認
```

## process_payment() の核心

```php
$telegram_kind = '100'; // キャリア決済申込

$send_data = array(
    'trading_id'     => $this->prefix_order ? $this->prefix_order . $order_id : 'wc_' . $order_id,
    'amount'         => $order->get_total(), // ← payment_amount ではなく amount
    'career_type'    => $career_type, // 4=au, 5=d払い, 6=SB(B)/Y!mobile
    'return_url'     => $return_url,
    'cancel_url'     => $order->get_cancel_order_url_raw(),
    'pc_mobile_type' => '4', // スマートフォン
);
```

## career_type の判定

チェックアウトフォームの POST から取得：

```php
$post_career_type = $this->get_post( 'career_type' ); // '04'/'05'/'06' の文字列
if ( isset( $post_career_type ) ) {
    $send_data['career_type'] = intval( $post_career_type );
} else {
    // 保存されたキャリア種別から復元
    $career_type = $order->get_meta( '_career_type', true ); // 'au'/'docomo'/'sb'
    $send_data['career_type'] = $this->set_career_type_num( $career_type );
}
```

## career_type マッピング

| 値 | キャリア | '_career_type' メタ値 |
|---|---|---|
| `4` | auかんたん決済 | `'au'` |
| `5` | d払い（ドコモ） | `'docomo'` |
| `6` | SB(B)・Y!mobile | `'sb'` |

## 取消（102）— 全キャリア共通

都度課金の取消は `career_type` に関わらず **telegram_kind=102** で統一。  
（旧コードの 510/511/512 という分岐は存在しない）

```php
$telegram_kind = '102'; // 携帯キャリア決済取消要求
// 要求電文は共通ヘッダのみ、データ部不要
```

## 売上（101）— d払い・auかんたん決済等

オーソリOKの決済を消込済にする売上確定処理。

```php
$telegram_kind = '101'; // 携帯キャリア決済売上要求
// 要求電文は共通ヘッダのみ、データ部不要
```

## キャリア取消可能ステータス

| キャリア | オーソリOK(20) | オーソリ完了(21) | 消込済(40) |
|---|---|---|---|
| d払い | ○ | ○ | ○ |
| auかんたん決済 | ○ | - | ○ |
| まとめてau支払い | ○ | - | - |
| SB(B)/Y!mobile | ○ | ○ | ○ |

## ドキュメント参照

都度課金:
- `2025docs/携帯キャリア決済（都度課金）/02_PG外部インターフェース仕様説明書（別紙：携帯キャリア決済）.pdf`
- `2025docs/携帯キャリア決済（都度課金）/導入補足資料（携帯キャリア決済）/`各キャリア別資料
