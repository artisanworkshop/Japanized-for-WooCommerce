# ショートコードチェックアウト配送日時必須項目問題 - 調査と対策レポート

## 問題の概要

ショートコードチェックアウトにおいて、配送日や配送時間帯を必須設定にしている場合でも、それらが入力されずに注文が完了してしまうケースが報告されています。

## 原因分析

### 考えられる原因

#### 1. **バリデーションがスキップされる場合**

##### ノンス検証の失敗
```php
if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
    ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
) {
    return; // バリデーション全体がスキップされる
}
```

**影響**: ノンスが存在しないか検証に失敗すると、配送日時のバリデーションが全く実行されない

##### 仮想商品のみのカート
- カートに仮想商品のみが含まれる場合、配送フィールド自体が表示されない
- しかし、設定によっては配送日時が必須とマークされたまま

#### 2. **フィールドデータが正しく渡されない場合**

##### $fields配列の問題
```php
// validate_date_time_checkout_field() メソッド
if ( empty( $fields['wc4jp_delivery_date'] ) ) {
    $errors->add(...);
}
```

**問題点**:
- `$fields` 配列は `woocommerce_checkout_posted_data` フィルターを通して作成される
- `jp4wc_delivery_posted_data()` メソッドが実行されない、または正しく動作しない場合、キーが存在しない
- `empty()` は `isset()` もチェックするため、キーが存在しない場合も空と判定される

##### フィルターの実行順序
- `woocommerce_checkout_posted_data` フィルターが他のプラグインによって変更される可能性
- フィルターの優先度によっては、データが上書きされる可能性

## 実装した対策

### 1. **バリデーションの強化**

#### 二重チェックの実装
```php
// $fields配列とPOSTデータの両方をチェック
$date_value = isset( $fields['wc4jp_delivery_date'] ) ? $fields['wc4jp_delivery_date'] : '';

// Fallback to POST if not in fields array
if ( empty( $date_value ) && isset( $_POST['wc4jp_delivery_date'] ) ) {
    $date_value = sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) );
}

if ( empty( $date_value ) || '0' === $date_value ) {
    $errors->add( 'wc4jp_delivery_date_required', ... );
}
```

**効果**: `$fields` 配列にデータが含まれていない場合でも、直接POSTデータをチェックして検証

#### 仮想商品チェックの追加
```php
// Check if we're dealing with virtual products only.
$has_physical = false;
if ( WC()->cart ) {
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        if ( ! $product->is_virtual() ) {
            $has_physical = true;
            break;
        }
    }
}

// Skip validation if only virtual products.
if ( ! $has_physical ) {
    return;
}
```

**効果**: 仮想商品のみの場合は配送情報不要なため、バリデーションをスキップ

### 2. **詳細なログ出力**

#### フィールド表示時のログ
```php
// delivery_date_designation() メソッド内
if ( function_exists( 'wc_get_logger' ) ) {
    $logger = wc_get_logger();
    $logger->info(
        sprintf(
            'Delivery fields will be displayed. Physical products: %d, Virtual products: %d',
            $product_cnt - $virtual_cnt,
            $virtual_cnt
        ),
        array( 'source' => 'jp4wc_delivery_display' )
    );
}
```

#### POSTデータキャプチャ時のログ
```php
// jp4wc_delivery_posted_data() メソッド内
$logger->info(
    sprintf(
        'Posted data captured - Date: %s, Time: %s',
        ! empty( $date_value ) ? $date_value : '(empty)',
        ! empty( $time_value ) ? $time_value : '(empty)'
    ),
    array( 'source' => 'jp4wc_delivery_posted_data' )
);
```

#### バリデーション実行時のログ
```php
// ノンス検証失敗時
$logger->warning(
    'Delivery validation skipped: Nonce verification failed',
    array( 'source' => 'jp4wc_delivery_validation' )
);

// 仮想商品のみの場合
$logger->info(
    'Delivery validation skipped: Virtual products only',
    array( 'source' => 'jp4wc_delivery_validation' )
);

// バリデーション失敗時
$logger->warning(
    sprintf(
        'Delivery date validation failed. Fields array: %s, POST data: %s',
        isset( $fields['wc4jp_delivery_date'] ) ? 'exists' : 'missing',
        isset( $_POST['wc4jp_delivery_date'] ) ? 'exists' : 'missing'
    ),
    array( 'source' => 'jp4wc_delivery_validation' )
);
```

### 3. **メール通知への詳細な診断情報追加**

メールには以下のセクションが含まれます:

#### チェックアウトタイプ
- ブロックチェックアウト vs ショートコードチェックアウトの判定
- チェックアウトページがブロックを使用しているかの確認

#### 配送データ
- ブロック用メタキー (`_wc_other/jp4wc/delivery-date`, `_wc_other/jp4wc/delivery-time`)
- ショートコード用メタキー (`wc4jp-delivery-date`, `wc4jp-delivery-time-zone`)
- 各メタキーの実際の値

#### プラグイン設定
- 配送日が有効/必須か
- 配送時間が有効/必須か

#### POSTデータ（ショートコードの場合）
- `wc4jp_delivery_date` の値
- `wc4jp_delivery_time_zone` の値
- ノンスの存在確認

#### カート情報
- 仮想商品の数
- 物理商品の数
- 配送フィールドを表示すべきか

#### 環境情報
- WordPress/WooCommerce/PHPバージョン
- ユーザーエージェント
- REST APIリクエストかどうか

#### メール例:
```
Order Number: #12345
Order Date: 2026年01月03日 14:30
Customer Name: 山田 太郎
Customer Email: customer@example.com

The requested delivery date is a required field but has not been entered.
Please review the order details and contact the customer.

Order Details Page: https://example.com/wp-admin/...

========================
Diagnostic Information
========================

--- Checkout Type ---
Type: Shortcode Checkout
Page Using Blocks: No

--- Delivery Data ---
Block Date Meta (_wc_other/jp4wc/delivery-date): (empty)
Block Time Meta (_wc_other/jp4wc/delivery-time): (empty)
Shortcode Date Meta (wc4jp-delivery-date): (empty)
Shortcode Time Meta (wc4jp-delivery-time-zone): (empty)

--- Plugin Settings ---
Delivery Date Enabled: Yes
Delivery Date Required: Yes
Delivery Time Enabled: Yes
Delivery Time Required: Yes

--- POST Data ---
wc4jp_delivery_date: (not set)
wc4jp_delivery_time_zone: (not set)
Nonce Present: Yes

--- Cart Information ---
Virtual Products: 0
Physical Products: 2
Should Show Delivery Fields: Yes

--- Environment ---
User Agent: Mozilla/5.0...
WordPress Version: 6.7
WooCommerce Version: 9.4.0
PHP Version: 8.2.0
REST Request: No

--- Note ---
Please check WooCommerce logs (jp4wc_delivery_validation) for detailed validation information.
```

## 問題特定の流れ

メール通知とログを確認することで、以下の流れで問題を特定できます:

### ステップ1: チェックアウトタイプの確認
- メールの「Checkout Type」セクションを確認
- "Shortcode Checkout" であることを確認

### ステップ2: フィールド表示の確認
- ログソース `jp4wc_delivery_display` を確認
- フィールドが表示されたかログに記録されているか

### ステップ3: データキャプチャの確認
- ログソース `jp4wc_delivery_posted_data` を確認
- POSTデータが正しくキャプチャされたか

### ステップ4: バリデーション実行の確認
- ログソース `jp4wc_delivery_validation` を確認
- バリデーションがスキップされた理由（ノンス失敗、仮想商品のみなど）
- または、バリデーションが失敗したか

### ステップ5: メールの診断情報で詳細確認
- POSTデータセクション: データが送信されたか
- カート情報: 物理商品が含まれているか
- 環境情報: 特定のブラウザや環境での問題か

## 監視すべきログファイル

### WooCommerceログの確認方法
1. WordPress管理画面 → WooCommerce → ステータス → ログ
2. 以下のソースでフィルタリング:
   - `jp4wc_delivery_display` - フィールド表示
   - `jp4wc_delivery_posted_data` - データキャプチャ
   - `jp4wc_delivery_validation` - バリデーション

### 正常な流れのログ例
```
[INFO] Delivery fields will be displayed. Physical products: 2, Virtual products: 0 (jp4wc_delivery_display)
[INFO] Posted data captured - Date: 2026-01-10, Time: 14:00-16:00 (jp4wc_delivery_posted_data)
```

### 問題があるケースのログ例

#### ケース1: ノンス検証失敗
```
[WARNING] Delivery validation skipped: Nonce verification failed (jp4wc_delivery_validation)
```
→ **原因**: キャッシュプラグインやCDNがノンスをキャッシュしている可能性

#### ケース2: データが送信されていない
```
[INFO] Delivery fields will be displayed. Physical products: 1, Virtual products: 0 (jp4wc_delivery_display)
[INFO] Posted data captured - Date: (empty), Time: (empty) (jp4wc_delivery_posted_data)
[WARNING] Delivery date validation failed. Fields array: missing, POST data: missing (jp4wc_delivery_validation)
```
→ **原因**: JavaScriptエラー、フォームの問題、ブラウザの自動入力の問題

#### ケース3: フィールドが表示されていない
```
[INFO] Delivery validation skipped: Virtual products only (jp4wc_delivery_validation)
```
→ **原因**: 誤って仮想商品と判定されている、または設定ミス

## テスト推奨項目

### 1. 基本的なテスト
- [ ] 配送日のみ必須: 選択せずに注文
- [ ] 配送時間のみ必須: 選択せずに注文
- [ ] 両方必須: 片方のみ選択して注文
- [ ] 両方必須: 両方選択せずに注文

### 2. 商品タイプのテスト
- [ ] 物理商品のみ
- [ ] 仮想商品のみ
- [ ] 物理商品と仮想商品の混在

### 3. キャッシュプラグインのテスト
- [ ] キャッシュプラグイン有効時
- [ ] キャッシュプラグイン無効時
- [ ] チェックアウトページをキャッシュ除外設定

### 4. ブラウザのテスト
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] モバイルブラウザ

### 5. 決済方法のテスト
- [ ] 代金引換
- [ ] クレジットカード
- [ ] その他の決済方法

## 追加の推奨事項

### キャッシュ設定
チェックアウトページとカートページをキャッシュから除外:
- WP Super Cache: `/checkout/*`, `/cart/*`
- W3 Total Cache: Never cache URLsに追加
- LiteSpeed Cache: Exclude設定に追加

### デバッグモードの有効化
```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

## 関連ファイル

- [includes/class-jp4wc-delivery.php](includes/class-jp4wc-delivery.php) - メインファイル
  - `delivery_date_designation()` - フィールド表示 (行63-118)
  - `jp4wc_delivery_posted_data()` - データキャプチャ (行378-406)
  - `validate_date_time_checkout_field()` - バリデーション (行408-499)
  - `send_admin_notification_email()` - メール通知 (行961-1124)
