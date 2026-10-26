# artws-security-hub

wc.artws.info で動かす WordPress プラグイン。Japanized for WooCommerce（JP4WC）のセキュリティ診断（Epic #256）のサーバー側を受け持つ。JP4WC の配布物には含めない（`plugins/README.md`）。

| フェーズ | 内容 | 状態 |
|---|---|---|
| S0（#261） | 土台（lint・テスト・CI）と JSON 形式の確定 | 完了 |
| S1（#262） | Wordfence Intelligence V3 の取り込みと `/feed/v1/` の配信 | 完了（0.2.0） |
| S2（#263） | REST `artws-sec/v1`（`POST /scan`・`GET /scan/{token}`・`DELETE /site`） | このディレクトリ（0.3.0。0.4.0 で登録をトークン単位に変更） |

JP4WC とやり取りする JSON の形式は、リポジトリ直下の `tests/fixtures/security-feed/`（README と JSON Schema）で定める。

wc.artws.info への入れ方・設定・確認・更新は、作業順の手順書 [DEPLOY.md](DEPLOY.md) にまとめた。この README は仕組みと設定値の理由を書く。

## 秘密情報

このリポジトリは公開されている。API キー・署名の共通鍵・DB 情報はコードにもテストにも書かず、wc.artws.info の `wp-config.php` の定数（または環境変数）から読む。

```php
define( 'ARTWS_SEC_WORDFENCE_API_KEY', '...' ); // S1: Wordfence Intelligence V3 の API キー（必須）
define( 'ARTWS_SEC_FEED_DIR', '/path/to/artws-security-feed/v1' ); // 任意。既定は wp-content/artws-security-feed/v1
define( 'ARTWS_SEC_CLIENT_IP_HEADER', 'HTTP_CF_CONNECTING_IP' ); // 任意（S2）。CDN・リバースプロキシの背後で、送信元 IP を入れる $_SERVER のキー
```

API キーは `Authorization: Bearer <値>` でそのまま送る。wordfence-cli の CLI ライセンスキーを使う場合は、CLI と同じく `cli-` を付けた値を定数に入れる。キーはエラーメッセージにも管理画面にも出さない。

レート制限などの乱用対策は、コードを読まれても成り立つ作りにする。

## 脆弱性フィード（S1）

### 取り込み

WP-Cron の日次イベント `artws_sec_feed_update` が Wordfence Intelligence V3 の production フィード（`https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production`）を一時ファイルに落とし、契約 README の「V3 からの変換」どおりに `vuln-plugins.json.gz` / `vuln-themes.json.gz` / `vuln-core.json.gz` と `manifest.json` を作る。

- フィードは数十 MB の1つの JSON オブジェクトなので、丸ごと `json_decode()` せず1件ずつ読む（`Artws_Sec_Json_Object_Reader`）。レコードは JSON 文字列のまま集める（4 万件で最大使用メモリ約 100 MB。`wp_raise_memory_limit()` で `WP_MAX_MEMORY_LIMIT` まで上げる）
- 変換したレコードは1件ずつ契約の Schema と同じ条件で検査し（`Artws_Sec_Record_Validator`。実行時に Composer の依存は使わないので手書き。テストで Schema 本体と突き合わせている）、合わないものだけ除外して理由別に数える。UTF-8 でない文字列を含むレコードも除外する。構文の壊れたレコードはダウンロードの破損とみなし、実行全体を失敗にする（再試行の対象）
- mbstring は使わない（WordPress が必須にしていない）。使うのは WordPress が無いときに補う `mb_strlen()` / `mb_substr()` だけ
- 次のどれかに当たったら生成失敗とし、前回のファイルを残したまま manifest を `"stale": true` で書き直す（ほぼ空のフィードを出すと、クライアントは「脆弱性なし」と表示してしまう）

| 条件 | 既定 | 変えるフィルター |
|---|---|---|
| 除外したレコードの割合が超えた | 2% | `artws_sec_feed_max_excluded_ratio` |
| plugin / theme / core のどれかが 0 件 | — | — |
| 前回成功時の件数に対して、種別ごとにこの割合を下回った | 90% | `artws_sec_feed_min_count_ratio` |
| 著作権者が1つも無い | — | — |

- 取得・読み込み・書き込みの失敗（タイムアウト・5xx など）は、1 時間後に再試行する（`artws_sec_feed_retry`。日次・手動の実行ごとに 3 回まで）。上の表の条件による失敗はデータそのものの問題なので再試行しない
- Wordfence 側の正当な整理で件数が減ったときは、管理画面の「Allow fewer records on the next update」で次の1回だけ件数減少の検査を外す（成功した実行で消える）
- メモリ不足・時間切れの fatal では失敗処理が走らないので、毎時の `artws_sec_feed_watchdog` が「最後の成功から 48 時間超」（`artws_sec_feed_stale_after`）で stale にする。fatal で残った一時ファイル（システムの一時ディレクトリの `artws-sec-wordfence-*` とフィードのディレクトリの `.*.tmp`）は次の実行が消す
- プラグインを無効化すると、cron を外して manifest を stale にする（ファイルは配信され続けるが、もう更新されない）。取り込みの実行中に無効化しても、その実行は manifest を書いた後にフラグ（`artws_sec_feed_deactivated`）を読み直して stale にする（無効化はフラグを立ててから stale の manifest を書くので、どの時点の無効化でも最後は stale になる）。有効化でフラグを消す
- 同時実行は options テーブルの1行（`INSERT IGNORE`）で防ぐ。2 時間たったロックは、死んだ実行の残りとして引き継ぐ。公開した状態はロックの中で、options のキャッシュではなく DB から読む
- manifest の `version` は成功した生成ごとに変わる（`YYYYMMDD.HHMMSS`）。`generated_at` は最後に成功した生成の時刻で、stale にしたときや EOL を変えたときには変わらない

### 管理画面（設定 > Security Hub）

- 最後の成功（version・件数）、stale、最後の実行の結果とエラー、除外件数と除外例（最大 20 件）
- 「Update now」: 取り込みを WP-Cron に積む。WP-CLI があれば `wp cron event run artws_sec_feed_update` でその場で走らせて結果を見られる
- EOL（manifest の `eol.{php,wordpress,woocommerce}.min_supported`）: 数字とドットだけ。保存すると manifest を書き直す（取り込みの実行中なら 1 分後に `artws_sec_feed_republish` で書き直す。既定 PHP 8.2 / WordPress 6.7 / WooCommerce 10.2）

WP-Cron はアクセスが無いと走らない。取り込みは数十秒かかるので、`DISABLE_WP_CRON` とサーバーの cron（例: `*/15 * * * * wp --path=/path/to/wordpress cron event run --due-now`）で回すのがよい。`DISABLE_WP_CRON` はサイトのすべての予定（WordPress・WooCommerce を含む）に効く。Pressable では最初から true で、Pressable 側が予定を走らせているので、何も設定しない（DEPLOY.md の 6-2）。

### 配信

ファイルは `ARTWS_SEC_FEED_DIR`（既定 `wp-content/artws-security-feed/v1/`）に置かれる。`/feed/v1/` は WordPress 本体の RSS（`/feed/`）と URL が重なるので、ドキュメントルートに実ディレクトリ `feed/` は作らず、`/feed/v1/` だけをこのディレクトリへの Alias で配信する（PHP を通さない静的配信。Pressable では下のとおり PHP で返す）。

- `.json.gz` は `Content-Type: application/gzip` で、`Content-Encoding` を付けない（付けるとクライアントが自動で展開し、sha256 が合わなくなる）
- ファイルは一時名で書いてから rename で差し替え、gz を全部置いてから manifest を置く。CDN を挟む場合は `/feed/v1/` をキャッシュさせない。CDN は拡張子で gz だけをキャッシュすることがあり（Cloudflare の既定）、manifest と食い違うと、クライアントは sha256 の不一致でその日の更新を取り込めない（次に取りに行くのは翌日）
- Web サーバーの設定を変えられない Pressable（wc.artws.info）では、WordPress より前に動く `htdocs/custom-redirects.php`（[hosting/pressable/custom-redirects.php](hosting/pressable/custom-redirects.php)）が、4 つの名前だけをこのディレクトリから読んで返す。リダイレクトにはしない（JP4WC はフィードのリダイレクトをたどらない）。Cloudflare は DNS only にする（手順と理由は DEPLOY.md の 5-2）
- アクセスログに残るのは UA と IP だけ。サイト URL などは受け取らない

nginx:

```nginx
location ^~ /feed/v1/ {
    alias /path/to/wp-content/artws-security-feed/v1/;
    location ~ /\. { deny all; }            # 書き込み途中の一時ファイル（.manifest.json.*.tmp など）
    types {
        application/json json;
        application/gzip gz;
    }
    gzip off;                               # .json.gz を二重に圧縮しない
    add_header Cache-Control "no-store";    # CDN にもキャッシュさせない（上の CDN の項目）
}
```

Apache（サーバー設定または VirtualHost。`.htaccess` では Alias を使えない）:

```apache
Alias /feed/v1/ /path/to/wp-content/artws-security-feed/v1/
<Directory /path/to/wp-content/artws-security-feed/v1/>
    Require all granted
    Options -Indexes
    RemoveEncoding .gz
    AddType application/gzip .gz
    AddType application/json .json
    # CDN にもキャッシュさせない（上の CDN の項目）
    Header set Cache-Control "no-store"
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
</Directory>
```

確認: `curl -sI https://wc.artws.info/feed/v1/vuln-plugins.json.gz` が `Content-Type: application/gzip` で `Content-Encoding` を含まないこと、`curl -s https://wc.artws.info/feed/v1/vuln-plugins.json.gz | shasum -a 256` が manifest の `sha256` と一致すること、`https://wc.artws.info/feed/` が今までどおり RSS を返すこと。

### Wordfence Intelligence の利用規約

[Wordfence Intelligence Terms and Conditions](https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/)（2026-01-26 更新版を 2026-10-22 に確認）:

- 無償。複製・改変・公開・再配布・サブライセンスを許諾する（取消不能）。条件は、複製物に Defiant の著作権表示、この license、開示された他の licensor の license を含めること
  - フィードの `copyrights` は、公開したレコードの V3 `copyrights`（`message` を除く）の和集合を manifest と3ファイルすべてに入れる。各保持者の `notice`（著作権表示）・`license`（本文）・`license_url` は V3 の値そのまま。同じ保持者で文言の違う版があれば、`defiant-2` のようにキーを変えて全部残す。形が契約に合わない保持者を持つレコードは公開しない
  - レコードを表示する所では `reference`（wordfence.com のレコードのページ）にリンクする（契約 README）
- 表記の文言は指定されていない。`attribution` は `Vulnerability data: Wordfence Intelligence`
- API キーは第三者に渡さない（コード・ログ・管理画面に出さない）。Defiant はレート制限を課せる（取り込みは1日1回）

## 無料診断の REST API（S2）

`/wp-json/artws-sec/v1/` の `POST /scan`・`GET /scan/{token}`・`DELETE /site`。認証（登録ごとのトークン）、判定の順序、ステータスコード、エラーの形、レート制限、保存期間は契約 README（`tests/fixtures/security-feed/README.md` の「REST」）に書いたとおり。ここにはサーバー側の作りと運用だけを書く。

### 作り

- `Artws_Sec_Rest_Controller`: 経路と判定の順序。経路は WordPress のユーザーではなく `Authorization: Bearer <site_token>` で認証するので、`permission_callback` は `__return_true`。本文は JSON の本文だけを読む（クエリ文字列は読まない）
- `Artws_Sec_Scan_Request`: 送信データの検査。契約の Schema を手書きで写したもの（テストで Schema 本体と突き合わせている）。Schema に無いキーはどの階層でも捨て、保存しない
- `Artws_Sec_Report_Builder` / `Artws_Sec_Report_Templates`: レポートの組み立てと文言（チェック ID ごとに日本語と英語）。V-01（既知の脆弱性）と V-03（サポート終了）はサーバーが判定し、ほかのチェックは送られた状態を使う。チェックの重大度・点数・範囲の判定は JP4WC の `JP4WC_Security_Checks` / `JP4WC_Security_Scanner` と同じで、テストがクライアントのクラスを読み込んで突き合わせる
- `Artws_Sec_Vuln_Index`: 照合用のインデックス。`.json.gz` を診断のたびに展開すると数百 MB 使うので、取り込み（S1）が公開するデータを slug ごとに1行ずつ表に書き、診断は送られたソフトウェアの行だけを読む。取り込みはフィードを公開した後に新しい世代を書き、書き終えてから state の `index_version` を切り替える。置き換えた1つ前の世代は残す（直前の状態を読んだ診断が空振りしない）。インデックスを書けなくてもフィードの公開は成功のままで、診断は前の世代を使い続ける（管理画面の結果にメッセージが出て、次の取り込みでやり直す）。インデックスが公開中のフィードに追いつかないまま `artws_sec_feed_stale_after`（48 時間）を過ぎると、診断は脆弱性データを古いものとして扱う（何も見つからなければ V-01 は「確認できず」、次の手順に「データが古い」旨）。インデックスが読めない・WordPress コアの行が無いときは、「脆弱性なし」と答えずに 503 を返す
- `Artws_Sec_Site_Store`: 登録（`site_token` の SHA-256 と、トークン付きの要求が発行先のサイトから来たかを確かめる正規化した `site_url`）と診断の保存、保存期間による削除。同じ `site_url` の登録はいくつあってもよい
- `Artws_Sec_Rate_Limiter`: 固定窓のレート制限。キーは `wp_salt()` の HMAC で、IP アドレスを生で残さない。1回の `INSERT … ON DUPLICATE KEY UPDATE` の影響行数で、同時の要求が最後の1回を取り合わないようにする
- `Artws_Sec_Db`: 表（`{prefix}artws_sec_sites` / `_scans` / `_limits` / `_vulns`）。`artws_sec_db_version` が違えば `init` で `dbDelta()` する（ZIP の置き換えでは有効化フックが走らないため）。版 2（0.4.0）は sites 表の `site_key` の一意制約を外す（`dbDelta()` は索引を消せないので先に `DROP INDEX`。外せるまで版を記録せず、次の要求でやり直す）

| フィルター | 既定 | 内容 |
|---|---|---|
| `artws_sec_scans_per_site` | 1 | 1登録（`site_token`）が窓ごとに実行できる回数。登録した要求の診断が1回目 |
| `artws_sec_scans_per_address` | 20 | 1つの送信元（IPv6 は /64）から窓ごとに受け付ける回数。共用サーバーでは多数のショップが同じ IP を使う |
| `artws_sec_scan_window` | 3600 | 窓の長さ（秒） |
| `artws_sec_retention_days` | 180 | 診断と、診断の無い登録を残す日数（プライバシーポリシーに書く値） |
| `artws_sec_consent_versions` | `2026-10` | 受け付ける同意文の版。JP4WC が同意文を改訂したら足す |
| `artws_sec_client_ip` | — | レート制限が数える送信元アドレス |
| `artws_sec_require_https` | true | http の要求を 403 にするか |

### 運用

- **反映した直後は 503 になる**: 0.2.0 までの取り込みはインデックスを書いていない。ZIP を置き換えたら「Update now」か `wp cron event run artws_sec_feed_update` で取り込みを1回走らせる
- **Authorization ヘッダー**: Apache の CGI / FastCGI では `Authorization` が PHP に届かないことがある。WordPress 標準の `.htaccess`（`RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`）があれば WordPress が `REDIRECT_HTTP_AUTHORIZATION` から読む。確認: 登録済みのトークンで `curl -s -H "Authorization: Bearer <token>" https://wc.artws.info/wp-json/artws-sec/v1/scan/<scan_token>` が 401 にならないこと
- **匿名の REST を止めるプラグイン**: ログインしていない要求の REST API を止めるセキュリティプラグインや、`Authorization: Bearer` を自分の認証（JWT など）として読むプラグインがあると、この API が動かない。`artws-sec/v1` を除外する
- **CDN・リバースプロキシ**: 背後に置くと `REMOTE_ADDR` がプロキシの IP になり、全サイトが1つの送信元として数えられる。プロキシが上書きするヘッダーのキーを `ARTWS_SEC_CLIENT_IP_HEADER` に入れる（クライアントが自由に書ける `X-Forwarded-For` をそのまま信じない。リストなら最後の値を使う）。プロキシを通らずに届く要求はどのヘッダーも書けるので、プロキシを置かないとき（wc.artws.info）は入れない。TLS をプロキシで終える場合は、`is_ssl()` が true になるよう wp-config.php で `$_SERVER['HTTPS']` を設定する（しないと全要求が 403）
- **毎日の削除**: WP-Cron の `artws_sec_scan_cleanup` が、180 日より古い診断・180 日診断の無い登録・終わったレート制限の窓を消す。無効化で止まる（データは残る）
- **プラグインの削除**: `uninstall.php` が無料診断の4表と `artws_sec_db_version` を消す（診断データを保存期間より長く残さない）。脆弱性フィード（S1 の option と配信ファイル）は残る
- **公開前に必要なもの**: wc.artws.info のプライバシーポリシーと利用規約（保存する項目・保存期間 180 日・削除の方法を書く。#256 の未決事項）。トークンを失った登録の診断は API では消せず、保存期間で消えることも書く。問い合わせで削除を受けるなら `site_url` で登録を探して手で消すが、同じ URL の登録には他人のもの（開発環境など）も含まれうるので、サイトの持ち主であることを確かめてから消す

## 開発

```bash
cd plugins/artws-security-hub
composer install
composer lint        # phpcs（WordPress Coding Standards、prefix artws_sec_ / ARTWS_SEC_。tests も対象）
composer test-local  # phpunit（WordPress テストライブラリ上）
```

テストは WordPress のテストライブラリで動く（`pre_http_request` で Wordfence への要求を差し替え、実際の通信はしない）。`composer test-local`（`bin/test-local.sh`）は、リポジトリ直下の `composer test-local` が用意する環境（`~/.cache/jp4wc-tests` の WordPress とテストライブラリ、MySQL コンテナ `jp4wc-phpunit-db`）を借り、専用のデータベース `artws_sec_tests` で走らせる。その環境が無ければ、先にリポジトリ直下で `composer test-local` を一度実行する。

CI は `.github/workflows/artws-security-hub.yml`。このディレクトリか `tests/fixtures/security-feed/`、`bin/install-wp-tests.sh` を変える PR でだけ走り、`bin/install-wp-tests.sh` でテストライブラリを入れて `composer test` を実行する。

## wc.artws.info への反映

JP4WC のタグやリリースとは連動しない。手動で ZIP を作ってアップロードする。初めて入れるとき・更新するときの手順（ZIP の作り方、版ごとの追加作業、確認のコマンド）は [DEPLOY.md](DEPLOY.md) を見る。

`Update URI: false` を指定しているので、WordPress.org の同名プラグインから更新が届くことはない。実行時に Composer の依存は使わない（`vendor/` はテスト用で、ZIP には入れない）。
