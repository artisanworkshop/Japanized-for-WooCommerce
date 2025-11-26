# Japanized for WooCommerce - Gutenberg Block Settings

## 概要

このディレクトリには、Gutenberg Block（React）を使用して作成された新しい管理画面の設定が含まれています。

## 構成

### フロントエンド（React）

- `src/js/jp4wc/admin/settings/` - Reactコンポーネント
  - `index.js` - エントリーポイント
  - `components/Settings.js` - メイン設定コンポーネント
  - `components/GeneralSettings.js` - 一般設定タブ
  - `components/ShipmentSettings.js` - 配送設定タブ
  - `components/PaymentSettings.js` - 支払い設定タブ
  - `components/LawSettings.js` - 特定商取引法設定タブ
  - `components/AffiliateSettings.js` - アフィリエイト設定タブ
  - `components/Settings.scss` - スタイル

### バックエンド（PHP）

- `includes/admin/class-jp4wc-admin-settings.php` - 設定画面の登録とスクリプトのエンキュー
- `includes/admin/class-jp4wc-settings-api.php` - REST APIエンドポイント

## 使用方法

### 1. ビルド

```bash
npm run build
```

これにより、Reactコンポーネントがコンパイルされ、`assets/js/build/admin/settings.js`に出力されます。

### 2. アクセス

WordPress管理画面で以下のメニューからアクセスできます：

**WooCommerce > JP4WC Settings**

## 機能

### タブ構成

1. **General Settings（一般設定）**
   - 名前よみがな
   - 敬称（様）
   - 会社名
   - 郵便番号自動入力
   - Yahoo APP ID
   - 日本語住所順序
   - 送料無料表示
   - メールテンプレートカスタマイズ
   - バーチャル商品設定
   - 使用状況追跡

2. **Shipment Settings（配送設定）**
   - 配送希望日指定
   - 配送希望時間帯指定
   - 休日期間設定
   - 配送不可曜日設定
   - 通知メール設定

3. **Payment Settings（支払い設定）**
   - 日本の銀行振込
   - 郵便振替
   - 店頭払い
   - 代金引換（定期購入対応）
   - PayPal
   - 代引手数料設定

4. **Commercial Law（特定商取引法）**
   - 店舗名
   - 販売会社名
   - 代表者名
   - 管理者名
   - 所在地
   - 連絡先
   - 電話番号
   - その他法定項目

5. **Affiliate（アフィリエイト）**
   - A8.net設定
   - felmat設定

### REST API

#### エンドポイント

- `GET /wp-json/jp4wc/v1/settings` - 全設定の取得
- `POST /wp-json/jp4wc/v1/settings` - 設定の保存

#### 認証

- WordPress REST API認証（Nonce）
- 必要な権限: `manage_woocommerce`

## 開発

### 開発モードで実行

```bash
npm run start
```

これにより、ファイルの変更を監視し、自動的に再ビルドされます。

### コンポーネントの追加

新しい設定セクションを追加する場合：

1. `src/js/jp4wc/admin/settings/components/` に新しいコンポーネントファイルを作成
2. `Settings.js` でコンポーネントをインポートして追加
3. `class-jp4wc-settings-api.php` の `get_all_setting_keys()` に設定キーを追加

### スタイルのカスタマイズ

`src/js/jp4wc/admin/settings/components/Settings.scss` でスタイルをカスタマイズできます。

## 既存設定画面との違い

### 旧設定画面（class-jp4wc-admin-screen.php）
- 従来のWordPress Settings API使用
- PHPでレンダリング
- ページリロードが必要

### 新設定画面（Gutenberg Block版）
- React + WordPress Components使用
- REST APIでデータ管理
- ページリロード不要（SPA）
- モダンなUI/UX

## トラブルシューティング

### ビルドエラー

```bash
# node_modulesを削除して再インストール
rm -rf node_modules package-lock.json
npm install
npm run build
```

### 設定が保存されない

1. ブラウザのコンソールでエラーを確認
2. REST APIエンドポイントが正しく登録されているか確認
3. ユーザーに適切な権限があるか確認

### スタイルが適用されない

1. ビルドが正しく完了しているか確認
2. ブラウザのキャッシュをクリア
3. `assets/js/build/admin/settings.css` が存在するか確認

## 今後の予定

- [ ] 設定のエクスポート/インポート機能
- [ ] 設定の検索機能
- [ ] 設定値のバリデーション強化
- [ ] 設定変更履歴の記録
- [ ] 多言語対応の強化
