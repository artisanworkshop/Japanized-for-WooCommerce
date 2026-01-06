# Japanized for WooCommerce

日本のEコマースに特化したWooCommerce拡張プラグインです。住所フォーマット、配送設定、決済方法、特定商取引法表記など、日本で必要な機能を提供します。

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/woocommerce-for-japan.svg)](https://wordpress.org/plugins/woocommerce-for-japan/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/woocommerce-for-japan.svg)](https://wordpress.org/plugins/woocommerce-for-japan/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/woocommerce-for-japan.svg)](https://wordpress.org/plugins/woocommerce-for-japan/)

## 公式サイト

https://ja.wordpress.org/plugins/woocommerce-for-japan/

## 主な機能

### 住所表示設定
- 氏名の読み仮名（ふりがな）入力
- 敬称（様）の追加
- 会社名フィールド
- 郵便番号からの自動住所入力（Yahoo! API連携）
- 日本式住所フォーマット

### 配送設定
- 配送希望日時の指定
- 配送時間帯の管理
- 配送不可曜日の設定
- 休業期間の設定

### 決済方法
- 日本の銀行振込設定
- 代引き手数料の設定
- Paidy決済連携

### 特定商取引法対応
- 法的表記の管理
- カスタマイズ可能なテンプレート

### アフィリエイト連携
- A8.net対応
- アクセストレード対応
- バリューコマース対応

## 開発環境のセットアップ

### 必要要件

- Node.js 18以上
- npm 8以上
- Docker Desktop（wp-env用）
- PHP 8.1以上

### 環境構築手順

1. **リポジトリのクローン**

```bash
git clone https://github.com/shoheitanaka/Japanized-for-WooCommerce.git
cd Japanized-for-WooCommerce
```

2. **依存パッケージのインストール**

```bash
npm install
```

3. **WordPress環境のセットアップ（wp-env）**

```bash
# wp-envのグローバルインストール
npm install -g @wordpress/env

# WordPress環境の起動
wp-env start
```

wp-envが起動すると、以下のURLでアクセスできます：
- WordPress: http://localhost:8888
- 管理画面: http://localhost:8888/wp-admin
  - ユーザー名: `admin`
  - パスワード: `password`

4. **開発サーバーの起動**

```bash
# ビルド（本番用）
npm run build

# 開発モード（ファイル監視）
npm run start
```

## ディレクトリ構造

```
Japanized-for-WooCommerce/
├── assets/                    # コンパイル済みアセット
│   ├── css/                  # スタイルシート
│   ├── images/               # 画像ファイル
│   └── js/
│       └── build/            # ビルド済みJavaScript
├── i18n/                     # 翻訳ファイル
│   ├── *.pot                 # 翻訳テンプレート
│   ├── *.po                  # 翻訳ソース
│   ├── *.mo                  # コンパイル済み翻訳
│   └── *.json                # JavaScript用翻訳
├── includes/                 # PHPクラス
│   ├── admin/               # 管理画面
│   │   ├── class-jp4wc-admin-settings.php  # Gutenberg設定画面
│   │   └── class-jp4wc-settings-api.php    # REST API
│   ├── blocks/              # ブロック機能
│   ├── gateways/            # 決済ゲートウェイ
│   └── ...
├── src/                      # ソースファイル
│   └── js/
│       └── jp4wc/
│           └── admin/
│               └── settings/ # React設定画面コンポーネント
│                   ├── index.js
│                   ├── components/
│                   │   ├── Settings.js
│                   │   ├── GeneralSettings.js
│                   │   ├── ShipmentSettings.js
│                   │   ├── PaymentSettings.js
│                   │   ├── LawSettings.js
│                   │   └── AffiliateSettings.js
│                   └── README.md
├── templates/                # テンプレートファイル
├── tests/                    # テストファイル
├── webpack.config.js         # webpack設定
├── package.json             # npm設定
└── woocommerce-for-japan.php # メインプラグインファイル
```

## 開発ワークフロー

### JavaScriptのビルド

プラグインはReactとWordPress Gutenbergコンポーネントを使用しています。

```bash
# 開発モード（ファイル監視、ソースマップ付き）
npm run start

# 本番ビルド（最適化済み）
npm run build
```

### 翻訳ファイルの更新

```bash
# 1. PHPとJavaScriptから翻訳可能な文字列を抽出
npm run make-pot

# 2. POファイルを更新（手動で翻訳を追加）
# i18n/woocommerce-for-japan-ja.po を編集

# 3. POファイルをマージ
msgmerge --update i18n/woocommerce-for-japan-ja.po i18n/woocommerce-for-japan.pot

# 4. JavaScript用のJSON翻訳ファイルを生成
npm run make-json

# 5. MOファイルを生成（PHP用）
msgfmt i18n/woocommerce-for-japan-ja.po -o i18n/woocommerce-for-japan-ja.mo
```

### コーディング規約

- **PHP**: WordPress Coding Standards
- **JavaScript**: ESLint + WordPress規約（@wordpress/scriptsに含まれる）
- **CSS/SCSS**: BEM命名規則推奨

```bash
# コードフォーマット
npm run format

# パッケージの更新チェック
npm run packages-update
```

## 主要技術スタック

### フロントエンド
- React 18
- WordPress Components (@wordpress/components)
- WordPress Element (@wordpress/element)
- WordPress i18n (@wordpress/i18n)
- SCSS（スタイリング）

### バックエンド
- PHP 8.1+
- WordPress 6.7+
- WooCommerce 8.0+
- WordPress REST API

### ビルドツール
- webpack 5
- @wordpress/scripts
- Babel
- PostCSS

## 設定画面の構造（Gutenberg版）

新しいブロックベースの設定画面は、以下のコンポーネント構成になっています：

```
Settings (メインコンテナ)
├── TabPanel (タブナビゲーション)
│   ├── GeneralSettings（一般設定）
│   ├── ShipmentSettings（配送設定）
│   ├── PaymentSettings（決済設定）
│   ├── LawSettings（特定商取引法）
│   └── AffiliateSettings（アフィリエイト）
└── Footer（相談案内）
```

REST APIエンドポイント：
- GET `/wp-json/jp4wc/v1/settings` - 設定取得
- POST `/wp-json/jp4wc/v1/settings` - 設定保存

## テスト

### PHPユニットテスト

このプラグインでは、WordPress + WooCommerceの統合テスト環境を使用しています。

#### 必要要件

- Docker Desktop（MySQLコンテナ用）
- Composer
- Subversion（svn）

#### テスト環境のセットアップ

初回のみ、以下のコマンドでテスト環境をセットアップします：

```bash
composer test-install
```

このコマンドは以下を自動で実行します：

1. DockerでMySQL 8.0コンテナを起動
2. データベース `wordpress_test` を作成
3. WordPressのテストライブラリをダウンロード
4. WooCommerceをダウンロードしてインストール

#### テストの実行

```bash
# 全てのテストを実行
composer test

# 特定のテストファイルのみ実行
./vendor/bin/phpunit tests/Unit/test-jp4wc-address.php

# 特定のテストメソッドのみ実行
./vendor/bin/phpunit --filter test_jp4wc_address_fields_class_exists
```

#### Docker環境の管理

```bash
# MySQLコンテナの状態確認
docker ps | grep jp4wc-mysql-test

# MySQLコンテナの停止
docker stop jp4wc-mysql-test

# MySQLコンテナの起動
docker start jp4wc-mysql-test

# MySQLコンテナの削除（再セットアップが必要になります）
docker rm jp4wc-mysql-test

# データベースに直接接続（デバッグ用）
docker exec -it jp4wc-mysql-test mysql -uroot -proot wordpress_test
```

#### テスト環境の詳細

- **WordPress**: `/tmp/wordpress/` に最新版がインストールされます
- **WooCommerce**: WordPress内のプラグインディレクトリにインストールされます
- **MySQL**: localhost:3306（Dockerコンテナ）
  - データベース名: `wordpress_test`
  - ユーザー名: `root`
  - パスワード: `root`

#### トラブルシューティング

**テストが失敗する場合：**

```bash
# テスト環境をクリーンアップして再セットアップ
docker rm -f jp4wc-mysql-test
rm -rf /tmp/wordpress /tmp/wordpress-tests-lib
composer test-install
```

**MySQLコンテナが起動しない場合：**

```bash
# Dockerが起動しているか確認
docker ps

# Docker Desktopを再起動
```

### E2Eテスト

```bash
# E2Eテスト（準備中）
npm run test:e2e
```

## デバッグ

wp-envでのデバッグ：

```bash
# ログ確認
wp-env run cli wp option get wc4jp_debug_log

# WordPressデバッグモード有効化
wp-env run cli wp config set WP_DEBUG true --raw

# PHPエラーログ表示
wp-env run cli wp config set WP_DEBUG_LOG true --raw
```

## 貢献

プルリクエストを歓迎します！大きな変更の場合は、まずissueを開いて変更内容を議論してください。

### 開発の流れ

1. このリポジトリをフォーク
2. フィーチャーブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add some amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

## ライセンス

GPL v3 or later

## サポート

- [公式ドキュメント](https://wc4jp-pro.work/)
- [サポートフォーラム](https://wordpress.org/support/plugin/woocommerce-for-japan/)
- [GitHub Issues](https://github.com/shoheitanaka/Japanized-for-WooCommerce/issues)

## 作者

- **Shohei Tanaka** - [Artisan Workshop](https://wc.artws.info/)

## 謝辞

このプラグインを使用していただいているすべてのユーザーと、貢献してくださった開発者の皆様に感謝します。
