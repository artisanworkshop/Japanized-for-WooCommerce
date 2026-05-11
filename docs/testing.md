# テスト手順

Japanized for WooCommerce では、コード品質の維持のために以下のテスト手段を用意しています。

| 種類 | ツール | 対象 |
|------|--------|------|
| コーディング規約チェック | PHP_CodeSniffer (PHPCS) | PHP ファイル全体 |
| ユニットテスト | PHPUnit | PHP クラス・関数 |
| E2E テスト | Playwright | ブラウザ操作・画面動作 |

---

## 前提条件

### 共通
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) が起動していること
- Node.js 20 以上、npm 10 以上
- Composer がインストール済み

### ローカル開発環境の起動

```bash
# wp-env を起動（初回は Docker イメージのダウンロードに数分かかります）
npx wp-env start
```

起動後のアクセス先：

| 用途 | URL |
|------|-----|
| 開発サイト | http://localhost:8890 |
| テストサイト | http://localhost:8891 |

---

## 1. PHPCS — コーディング規約チェック

WordPress Coding Standards および PHP 互換性チェックを実施します。

### セットアップ（初回のみ）

```bash
composer install
```

### チェックの実行

```bash
# 違反箇所をリストアップ
composer lint

# 自動修正できる箇所を修正
composer format
```

### チェック対象

`.phpcs.xml.dist` に定義されています。主な設定：

- 対象: プロジェクトルート以下の `.php` ファイル
- 除外: `vendor/`, `node_modules/`, `tests/`
- ルール: WordPress Coding Standards + PHPCompatibilityWP（PHP 8.1+）

### CI での挙動

Pull Request 時に GitHub Actions が `composer lint` を自動実行します。エラーが残った状態でマージしないでください。

---

## 2. PHPUnit — ユニットテスト

PHP クラスのロジックを WordPress テスト環境上で検証します。

### セットアップ（初回のみ）

```bash
# テスト用データベースのセットアップ（wp-env 起動後に実行）
composer test-install
```

### テストの実行

```bash
composer test
```

### テストファイルの場所

```
tests/
├── Unit/
│   ├── test-jp4wc-address-email.php              # 注文メール住所フォーマット（読み仮名表示）
│   ├── test-jp4wc-delivery-payment.php           # 配送フィルタ・決済連携
│   ├── test-jp4wc-delivery-validation.php        # チェックアウト入力バリデーション
│   ├── test-jp4wc-delivery-saving.php            # 注文保存処理
│   ├── test-jp4wc-delivery-blocks-validation.php # ブロックチェックアウト検証
│   └── test-jp4wc-yomigana-blocks-validation.php # 読み仮名フィールド検証
└── bootstrap.php
```

各テストクラスは `WP_UnitTestCase` を継承しています。

### test-jp4wc-address-email.php の詳細

注文メールに読み仮名が表示される3層フィルターチェーンを検証します。

```text
woocommerce_localisation_address_formats    ← 層1: JPフォーマットへのプレースホルダー挿入
woocommerce_order_formatted_billing_address ← 層2: 注文メタからのyomigana値取得
woocommerce_formatted_address_replacements  ← 層3: プレースホルダーを実値に置換
```

| テストメソッド | 検証内容 |
| --- | --- |
| `test_localisation_address_formats_filter_is_registered` | **リグレッション防止**。フィルター登録が消えたら即失敗 |
| `test_jp_address_format_includes_yomigana_placeholders_when_enabled` | オプション有効時、JPフォーマットに `{yomigana_last_name}` / `{yomigana_first_name}` が含まれる |
| `test_jp_address_format_excludes_yomigana_when_disabled` | オプション無効時、プレースホルダーが含まれない |
| `test_address_replacements_sets_yomigana_values` | `address_replacements()` が yomigana 値を正しくセットする |
| `test_address_replacements_skips_yomigana_when_disabled` | オプション無効時は置換しない |
| `test_classic_checkout_yomigana_in_formatted_billing_address` | Classic Checkout（`_billing_yomigana_*` メタ）→ `get_formatted_billing_address()` に出現 |
| `test_block_checkout_yomigana_in_formatted_billing_address` | Block Checkout（`_wc_billing/jp4wc/yomigana_*` メタ）→ `get_formatted_billing_address()` に出現 |
| `test_block_checkout_yomigana_in_formatted_shipping_address` | Block Checkout（`_wc_shipping/jp4wc/yomigana_*` メタ）→ `get_formatted_shipping_address()` に出現 |

#### 背景: 2.9.10 リグレッション

2.9.10 のリファクタリングで `woocommerce_localisation_address_formats` フィルターの登録が誤って削除されました。このフィルターが未登録だと、WooCommerce はデフォルトの JP 住所フォーマット（`{yomigana_*}` プレースホルダーなし）を使用するため、層3で正しく置換値をセットしても出力に反映されず、注文メールから読み仮名が消えます。`test_localisation_address_formats_filter_is_registered` は同様の削除を検知するためのガードです。

---

### test-jp4wc-delivery-payment.php の詳細

`JP4WC_Delivery::jp4wc_delivery_check_data()` を検証します。このメソッドは決済完了後に `woocommerce_payment_successful_result` フィルターで配送日時の入力漏れを検知し、注文ステータスを `failed` に変更する役割を担います。

| テストメソッド | 検証内容 |
| --- | --- |
| `test_filter_registered_with_two_args` | **リグレッション防止**。フィルターが引数2つで登録されているか（1つだと `$order_id` が受け取れない） |
| `test_returns_result_unchanged_when_delivery_disabled` | 配送日時オプション未設定 → 結果をそのまま返す |
| `test_square_style_result_without_order_id_key_causes_no_error` | **リグレッション防止**。Square/Amazon Pay など `order_id` キーを返さないゲートウェイで Fatal Error が起きない |
| `test_returns_result_when_order_not_found` | 存在しない注文ID → エラーなく早期リターン |
| `test_returns_result_when_order_id_is_zero` | `order_id = 0`（未定義キー由来）→ エラーなく早期リターン |
| `test_passes_when_required_date_is_present` | 配送日必須・日付あり → `success` |
| `test_fails_when_required_date_missing` | 配送日必須・日付なし → `failure`、注文ステータスが `failed` になる |
| `test_fails_when_required_time_missing` | 配送時間帯必須・時間なし → `failure` |
| `test_passes_when_delivery_date_option_is_disabled` | 配送日オプション無効（`''`）、必須フラグあり → `success`（オプション設定が優先） |
| `test_fails_once_when_both_date_and_time_required_and_missing` | 日付・時間帯とも必須・両方なし → `failure`、注文 `failed` が1回だけセットされる |

---

### test-jp4wc-delivery-validation.php の詳細

Classic Checkout での `JP4WC_Delivery::validate_date_time_checkout_field()` を検証します。このバリデーターは `woocommerce_checkout_process` フック経由で実行され、`WP_Error` にエラーを追加することで注文確定をブロックします。

| テストメソッド | 検証内容 |
| --- | --- |
| `test_skips_validation_when_nonce_missing` | ノンスなし → バリデーションをスキップ（エラーなし） |
| `test_skips_validation_when_nonce_invalid` | ノンス不正 → スキップ |
| `test_skips_validation_for_virtual_products_only` | カートが仮想商品のみ → 配送不要のためスキップ |
| `test_no_error_when_delivery_date_option_disabled` | 配送日オプション無効 → 必須フラグに関わらずエラーなし |
| `test_no_error_when_required_date_present_in_fields` | 配送日必須・値あり → エラーなし |
| `test_adds_error_when_required_date_missing` | 配送日必須・値なし → `wc4jp_delivery_date_required` エラー追加 |
| `test_adds_error_when_date_value_is_zero` | 配送日必須・値が `'0'`（未選択）→ エラー追加 |
| `test_no_error_when_date_is_optional_and_missing` | 配送日任意・値なし → エラーなし |
| `test_adds_error_when_required_time_missing` | 配送時間帯必須・値なし → `wc4jp_delivery_time_zone_required` エラー追加 |
| `test_no_error_when_required_time_present` | 配送時間帯必須・値あり → エラーなし |
| `test_adds_both_errors_when_both_required_and_both_missing` | 日付・時間帯とも必須・両方なし → 両エラーが追加される |

---

### test-jp4wc-delivery-saving.php の詳細

`JP4WC_Delivery::save_delivery_data_to_order()` を検証します。Classic Checkout の `woocommerce_checkout_create_order` フックで呼ばれ、配送日時を注文メタに保存します。

| テストメソッド | 検証内容 |
| --- | --- |
| `test_saves_delivery_date_to_order_meta` | `wc4jp_delivery_date` → 注文メタ `wc4jp-delivery-date` に保存される |
| `test_saves_delivery_time_to_order_meta` | `wc4jp_delivery_time_zone` → 注文メタ `wc4jp-delivery-time-zone` に保存される |
| `test_saves_both_date_and_time` | 日付・時間帯の両方が正しく保存される |
| `test_does_not_save_when_data_is_empty` | 空の `$data` → 既存メタを上書きしない |
| `test_does_not_save_date_when_value_is_zero` | 日付が `'0'`（未選択）→ 保存しない |
| `test_does_not_save_time_when_value_is_zero` | 時間帯が `'0'` → 保存しない |
| `test_applies_date_format_when_option_is_set` | `wc4jp-date-format` オプション設定時、生の `Y-m-d` でなくフォーマット後の値が保存される |
| `test_skips_save_on_block_checkout` | Block Checkout 経由（`woocommerce_store_api_checkout_update_order_from_request` 発火済み）→ 二重保存を防ぐためスキップ |
| `test_saves_tracking_ship_date` | `wc4jp-tracking-ship-date` が注文メタに保存される |

---

### test-jp4wc-delivery-blocks-validation.php の詳細

Block Checkout 向けの `JP4WC_Delivery_Blocks_Integration::validate_additional_field()` を検証します。このバリデーターは Store API のすべてのリクエスト（合計計算・ロケール・最終確定）で呼ばれるため、中間リクエストでのスキップ処理が重要です。

| テストメソッド | 検証内容 |
| --- | --- |
| `test_skips_validation_on_calc_totals_request` | `__experimental_calc_totals` パラメータあり → バリデーションをスキップ（`$is_valid` をそのまま返す） |
| `test_skips_validation_on_calc_totals_preserves_false` | `calc_totals` 時に `$is_valid=false` でも `WP_Error` にならない |
| `test_returns_error_when_required_date_is_empty` | 配送日必須・値が空文字 → `invalid_delivery_date` エラー |
| `test_returns_error_when_required_date_is_null` | 配送日必須・値が `null` → エラー |
| `test_returns_error_when_required_date_is_zero` | 配送日必須・値が `'0'` → エラー |
| `test_passes_when_required_date_is_present` | 配送日必須・値あり → パス |
| `test_passes_when_date_is_not_required_and_empty` | 配送日任意・空 → パス |
| `test_returns_error_when_required_time_is_empty` | 配送時間帯必須・空 → `invalid_delivery_time` エラー |
| `test_passes_when_required_time_is_present` | 配送時間帯必須・値あり → パス |
| `test_passes_when_time_is_not_required_and_empty` | 配送時間帯任意・空 → パス |
| `test_passes_unrelated_field_key_unchanged` | 関係ないフィールドキー → `$is_valid` をそのまま返す |
| `test_hides_delivery_meta_keys_from_order_display` | `_wc_other/jp4wc/delivery-*` キーが注文メタ表示から除外される |
| `test_leaves_non_jp4wc_meta_unchanged` | JP4WC 以外のメタキーはそのまま残る |

---

### test-jp4wc-yomigana-blocks-validation.php の詳細

Block Checkout 向けの `JP4WC_Yomigana_Blocks_Integration` を検証します。読み仮名フィールドのバリデーション・メタ非表示・保存・注文確認ブロックのフィルタリングを網羅します。

| テストメソッド | 検証内容 |
| --- | --- |
| `test_returns_error_when_required_billing_last_name_empty` | 読み仮名必須・請求先姓が空 → `invalid_yomigana` エラー |
| `test_returns_error_when_required_billing_first_name_empty` | 読み仮名必須・請求先名が空 → エラー |
| `test_passes_when_required_yomigana_is_present` | 読み仮名必須・値あり → パス |
| `test_passes_when_yomigana_not_required_and_empty` | 読み仮名任意・空 → パス |
| `test_passes_unrelated_field_key` | 関係ないフィールドキー → パス |
| `test_shipping_keys_are_not_validated` | `_wc_shipping/` プレフィックスのキーはバリデーション対象外 |
| `test_hides_all_four_yomigana_meta_keys` | 4つの yomigana WCメタキー（billing/shipping × 姓/名）が注文メタ表示から除外される |
| `test_leaves_non_yomigana_meta_unchanged` | yomigana 以外のメタはそのまま残る |
| `test_saves_billing_yomigana_from_request` | REST リクエストの billing yomigana → `_billing_yomigana_*` メタに保存される |
| `test_saves_shipping_yomigana_from_request` | REST リクエストの shipping yomigana → `_shipping_yomigana_*` メタに保存される |
| `test_save_does_nothing_when_request_has_no_address_params` | アドレスパラメータなし → メタ保存なし |
| `test_sanitizes_yomigana_value_on_save` | HTMLタグを含む入力はサニタイズされて保存される |
| `test_strips_yomigana_entries_from_block_html` | 注文確認ブロックの HTML から yomigana の dt/dd ペアが除去される |
| `test_returns_block_html_unchanged_when_no_yomigana` | yomigana エントリなしの HTML → そのまま返す |
| `test_removes_empty_dl_wrapper_after_stripping` | yomigana 除去後に空の `<dl>` も削除される |

---

## 3. Playwright E2E テスト — ブラウザ操作テスト

実際のブラウザを使ってチェックアウト・管理画面を操作し、機能を検証します。

### E2E セットアップ（初回のみ）

```bash
# Node.js 依存パッケージのインストール
npm install

# Playwright ブラウザのインストール
npx playwright install chromium
```

### E2E テストの実行

```bash
# 全テストを実行（wp-env テストサイト: http://localhost:8891 を使用）
npm run test:e2e

# UI モードで実行（ブラウザの動作をリアルタイムで確認）
npm run test:e2e:ui

# デバッグモードで実行（ステップ実行）
npm run test:e2e:debug

# テスト結果レポートを開く
npm run test:e2e:report
```

### E2E テストファイルの場所

```text
tests/e2e/
├── utils/
│   └── helpers.ts              # 共通ヘルパー関数（ログイン・商品作成など）
├── global-setup.ts             # 全テスト共通の初期化処理
├── checkout-classic.spec.ts    # Classic Checkout（ショートコード）
├── checkout-blocks.spec.ts     # Block Checkout（ブロックエディタ）
├── admin-settings.spec.ts      # 管理画面設定ページ
└── admin-order.spec.ts         # 管理画面注文一覧・詳細
```

### テスト内容

| ファイル | テスト内容 |
| --- | --- |
| `checkout-classic.spec.ts` | COD決済でのサンクスページ遷移（バグ #166 回帰テスト）、配送日時バリデーション |
| `checkout-blocks.spec.ts` | 読み仮名フィールドの表示・非表示、CSS順序（位置）、必須バリデーションエラー |
| `admin-settings.spec.ts` | 設定ページの読み込み、トグル保存の永続化、REST API レスポンス |
| `admin-order.spec.ts` | 注文一覧の配送日カラム表示、注文編集画面のメタボックス、注文メタデータ |

### 環境変数

| 変数 | デフォルト | 説明 |
| --- | --- | --- |
| `WP_BASE_URL` | `http://localhost:8891` | テスト対象の WordPress URL |
| `WP_APP_PASSWORD` | （global-setup が自動生成） | WordPress Application Password |

別環境に対してテストを実行する場合：

```bash
WP_BASE_URL=https://staging.example.com npm run test:e2e
```

### global-setup の処理

`tests/e2e/global-setup.ts` が全テスト実行前に一度だけ動作し、以下を自動で設定します：

1. WP-CLI で WordPress Application Password を発行
2. COD（代引き）支払いゲートウェイを有効化
3. 送料無料ゾーン（Japan (e2e)）を作成

### 認証について

REST API の認証に **WordPress Application Passwords** を使用しています。
`global-setup.ts` が `wp user application-password create admin playwright-e2e --porcelain` を実行して自動発行し、`tests/e2e/.auth/credentials.json`（`.gitignore` 対象）に保存します。

---

## 全テストの一括実行

```bash
# 1. PHPCS チェック
composer lint

# 2. PHPUnit
composer test

# 3. E2E テスト
npm run test:e2e
```
