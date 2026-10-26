# wc.artws.info への反映手順（artws-security-hub）

wc.artws.info に artws-security-hub を入れて、脆弱性フィード（`/feed/v1/`）と無料診断の REST API（`/wp-json/artws-sec/v1/`）を動かすまでの手順。上から順に進め、各節の「確認」で結果を確かめる。

- 対象の版: 0.4.0
- 仕組みや設定値の理由: [README.md](README.md)
- JP4WC とやり取りする JSON の決まり: [契約 README](../../tests/fixtures/security-feed/README.md)

| 状況 | 進め方 |
|---|---|
| 初めて入れる | 1 から 9 まで |
| 新しい版に更新する | [10. 更新するとき](#10-更新するとき) |
| うまく動かない | [11. 困ったとき](#11-困ったとき) |

---

## 1. 事前に用意するもの

- [ ] WordPress 6.7 以上、PHP 8.3 以上（zlib 拡張。gz を作るのに使う）
- [ ] HTTPS で配信されていること（無料診断の API は http の要求を 403 で断る）
- [ ] Wordfence Intelligence V3 の API キー（2026-10-20 に決めたアカウントで発行したもの）
- [ ] `wp-config.php` を編集できること
- [ ] Web サーバーの設定（nginx の `location` / Apache の VirtualHost）を変えられること。Pressable では変えられないので、代わりに `htdocs/custom-redirects.php` を置ける SFTP か SSH（[5-2](#5-2-pressable-で-custom-redirectsphp-を置く)）
- [ ] データベースのユーザーが、表の作成・変更（CREATE / ALTER / INDEX / DROP）をできること
- [ ] あると楽なもの:
  - SSH と WP-CLI（確認のコマンドに使う。Pressable では、MyPressable のサイトの Collaborators タブにある SSH Username / Password で `ssh.pressable.com` に入る。SFTP も同じ認証情報で `sftp.pressable.com`）
  - 手元に `curl` と `jq`

## 2. `wp-config.php` に定数を入れる

`/* That's all, stop editing! */` の行より上に足す。このリポジトリは公開されているので、キーはここにだけ書き、コード・チャット・issue には貼らない。

```php
// 必須: Wordfence Intelligence V3 の API キー。
// wordfence-cli の CLI ライセンスキーを使う場合は、頭に cli- を付けた値にする。
define( 'ARTWS_SEC_WORDFENCE_API_KEY', '...' );

// 任意: フィードを書き出す場所。省略すると wp-content/artws-security-feed/v1
// define( 'ARTWS_SEC_FEED_DIR', '/path/to/artws-security-feed/v1' );

// CDN・リバースプロキシの背後にあるときだけ: 送信元 IP が入る $_SERVER のキー
// （Cloudflare なら HTTP_CF_CONNECTING_IP）。プロキシが必ず上書きするヘッダーを選ぶ。
// プロキシを置かないとき（wc.artws.info は Cloudflare を DNS only にしている。5-2）は入れない。
// プロキシを通らない要求はこのヘッダーを自由に書けるので、入れると送信元 IP の枠を逃れられる。
// define( 'ARTWS_SEC_CLIENT_IP_HEADER', 'HTTP_CF_CONNECTING_IP' );

// 6-1 でサーバーの cron を短い間隔（15 分おきなど）で回すときだけ。Pressable では入れない（最初から true。6-2）
// define( 'DISABLE_WP_CRON', true );
```

TLS をプロキシ（CDN・ロードバランサー）で終えている場合は、WordPress から見て https になるようにする。これをしないと、無料診断の要求がすべて 403 になる。プロキシがこのヘッダーを必ず上書きする構成のときだけ、次を使う。

```php
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}
```

## 3. ZIP を作る（手元）

JP4WC のタグやリリースとは連動しない。リポジトリの直下で次を実行する。

```bash
rm -rf dist/artws-security-hub && mkdir -p dist/artws-security-hub
rsync -a --exclude-from=plugins/artws-security-hub/.distignore plugins/artws-security-hub/ dist/artws-security-hub/
(cd dist && rm -f artws-security-hub.zip && zip -r artws-security-hub.zip artws-security-hub && rm -rf artws-security-hub)
```

`dist/artws-security-hub.zip` ができる。`dist/` は `.gitignore` と JP4WC の `.distignore` に入っているので、コミットにも JP4WC の ZIP にも入らない。`vendor/`・`tests/`・`hosting/`・README などは `plugins/artws-security-hub/.distignore` で除かれる（実行時に Composer の依存は使わない。`hosting/` は 5-2 で別に置く）。

## 4. アップロードして有効化する

1. 管理画面 > プラグイン > 新規追加 > プラグインのアップロードで `dist/artws-security-hub.zip` を選ぶ。
   - 2 回目以降は「現在のものをアップロードしたもので置き換える」を選ぶ。
2. 「Artws Security Hub」を有効化する。
   - 有効化で、表 4 つ（`{prefix}artws_sec_sites` / `_scans` / `_limits` / `_vulns`）を作る。
   - 毎日の取り込み・毎時の見張り・毎日の削除の予定も入る。

`Update URI: false` なので、WordPress.org から同名のプラグインの更新が届くことはない。

**確認**（WP-CLI）

```bash
wp option get artws_sec_db_version
# → 2

wp cron event list --fields=hook,next_run_relative,recurrence | grep artws_sec
# → artws_sec_feed_update（daily）、artws_sec_feed_watchdog（hourly）、artws_sec_scan_cleanup（daily）
```

## 5. `/feed/v1/` を配信する

フィードのファイルは `ARTWS_SEC_FEED_DIR`（既定 `wp-content/artws-security-feed/v1/`）に書かれる。`/feed/` は WordPress の RSS の URL なので、ドキュメントルートに `feed/` ディレクトリは作らない。`/feed/v1/` だけを、このディレクトリのファイルとして返す。

- Web サーバーの設定を変えられる → [5-1](#5-1-web-サーバーで-alias-を作る)
- 変えられない（Pressable。wc.artws.info は 2026-10 時点でこちら）→ [5-2](#5-2-pressable-で-custom-redirectsphp-を置く)

どちらの場合も次を守る。確認は 7 で取り込みを済ませてから、8 で行う。

- `.json.gz` に `Content-Encoding: gzip` を付けてはいけない。付けると JP4WC 側が自動で展開してしまい、sha256 が合わなくなる。
- リダイレクトにしない。JP4WC はフィードの要求でリダイレクトをたどらないので、`/feed/v1/` の URL のまま中身を返す。
- CDN を挟むなら、`/feed/v1/` はキャッシュさせない。CDN は拡張子で gz だけをキャッシュすることがある（Cloudflare の既定では `.gz` はキャッシュし、`.json` はしない）。すると更新の後しばらく、新しい manifest と古い gz が組みになる。JP4WC は sha256 が合わない日は取り込まず、次に取りに行くのは翌日なので、取得の時刻がその時間帯に当たるサイトは毎日更新を逃す。

### 5-1. Web サーバーで Alias を作る

nginx（`server` ブロック内。パスは実際の場所に直す）:

```nginx
location ^~ /feed/v1/ {
    alias /path/to/wp-content/artws-security-feed/v1/;
    location ~ /\. { deny all; }            # 書き込み途中の一時ファイル
    types {
        application/json json;
        application/gzip gz;
    }
    gzip off;                               # .json.gz を二重に圧縮しない
    add_header Cache-Control "public, max-age=600";
}
```

Apache（サーバー設定か VirtualHost。`.htaccess` では Alias を使えない）:

```apache
Alias /feed/v1/ /path/to/wp-content/artws-security-feed/v1/
<Directory /path/to/wp-content/artws-security-feed/v1/>
    Require all granted
    Options -Indexes
    RemoveEncoding .gz
    AddType application/gzip .gz
    AddType application/json .json
    Header set Cache-Control "public, max-age=600"
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
</Directory>
```

設定を読み込み直す（`nginx -t && systemctl reload nginx` / `apachectl configtest && systemctl reload apache2` など）。

### 5-2. Pressable で custom-redirects.php を置く

Pressable では nginx の設定を変えられないが、`htdocs/custom-redirects.php` が WordPress より前に PHP で動く。nginx はディスクにあるファイルをそのまま返し、ファイルの無い URL だけがこの PHP に来る。`/feed/v1/` には実ファイルが無いので、ここで `wp-content/artws-security-feed/v1/` のファイルを読んで返す。中身は [hosting/pressable/custom-redirects.php](hosting/pressable/custom-redirects.php)。

- 返すのは 4 つの名前（`manifest.json` と `vuln-{plugins,themes,core}.json.gz`）への GET / HEAD だけ。ほかの要求はそのまま WordPress に渡すので、`/feed/`（RSS）は変わらない。
- 応答には `Cache-Control: no-store` を付け、PHP の出力圧縮を切る（`Content-Encoding` を付けない）。custom-redirects.php の出力は Pressable のページキャッシュ（Batcache）にも入らない。
- 取得 1 回ごとに PHP のワーカーを 1 つ使う。

1. **Light Weight 404s をオフにする**: MyPressable の Settings > Performance。オンだと、一部の静的ファイルの拡張子では PHP を通さずに 404 を返すので、custom-redirects.php が動かない。
2. **ファイルを置く**: SFTP（`sftp.pressable.com`）で `htdocs/custom-redirects.php` としてアップロードする。
   - 置く前に手元で構文を確かめる: `php -l plugins/artws-security-hub/hosting/pressable/custom-redirects.php`。このファイルが壊れていると、サイトのすべてのページが止まる。
   - 既に `htdocs/custom-redirects.php` があるときは置き換えない。このファイルの `<?php` より後ろを、既存のファイルの `<?php` のすぐ後に貼る（ほかのリダイレクトより先に動くように）。
   - `ARTWS_SEC_FEED_DIR` を既定から変えたときは、ファイルの中のパス（`/wp-content/artws-security-feed/v1/`）も直す。WordPress を読み込む前に動くので、wp-config.php の定数は使えない。
   - 置いたら、プロキシを外す前に 8 の「脆弱性フィード」の curl（`dig` 以外。この時点では Cloudflare の IP が出る）で `/feed/v1/` を確かめておく。応答が `no-store` なので、Cloudflare を通っていてもキャッシュされない（`cf-cache-status: BYPASS`）。
3. **Cloudflare のプロキシを外す**（Pressable の推奨。理由は下の「あわせて確かめること」）
   1. 先に Pressable の証明書を確かめる。Cloudflare の DNS で wc.artws.info の A レコードの値（Pressable の IP）を見て、Cloudflare を通さずにつなぐ。

      ```bash
      curl -sv --resolve wc.artws.info:443:<A レコードの値> https://wc.artws.info/ -o /dev/null 2>&1 | grep -E 'subject:|expire date:|verify'
      ```

      `SSL certificate verify ok.` と、先の日付の expire date が出ればよい。subject は `CN=tls.automattic.com` でよい（Automattic の共用の証明書で、wc.artws.info はその別名の 1 つ。verify ok なら含まれている）。出ないときは、次の手順で DNS only にした直後に、MyPressable の Domains タブで wc.artws.info の Actions ▼ > Retry SSL を押す。Pressable の証明書は DNS が Pressable を指していると自動で発行される（成功すれば 1 分ほどで有効になる）。
   2. Cloudflare の DNS で、wc.artws.info を指すレコード（A。www があればそれも）の Proxy status を Proxied（橙の雲）から DNS only（灰色の雲）にする。
   3. 自分で入れた AAAA レコードがあれば、Pressable の案内に従って消す（AAAA が別の場所を指していると、証明書を発行するときの確認がそちらへ行く）。

あわせて確かめること:

- プロキシを外すと、Cloudflare のキャッシュ・WAF・ルールなど、プロキシを使う機能は wc.artws.info に効かなくなる。DNS の管理はそのまま Cloudflare で続けられる。代わりに、Pressable の WAF・レート制限・DDoS 対策が本当の送信元を見て働く。プロキシを通すと、送信元が Cloudflare に置き換わって Pressable の防御が見えなくなり、正しい要求を誤って止めることもある、と Pressable は書いている。
- `wp-config.php` の `ARTWS_SEC_CLIENT_IP_HEADER`（節 2）は入れない（入っていれば消す）。プロキシが無ければ `REMOTE_ADDR` が送信元で、偽れない。正しく数えているかは、8 の「無料診断の API」の 5 で確かめる。
- `wp-content/artws-security-feed/v1/` のファイルは、その URL でも nginx がそのまま返す（書き込み途中の一時ファイルも）。中身は公開しているフィードとその書きかけで、JP4WC も読まないので、そのままでよい。
- 後で Cloudflare のプロキシを戻すなら、Cache Rules で `/feed/v1/` をキャッシュさせる設定を作らない（応答の `no-store` が守られる）。節 2 の `ARTWS_SEC_CLIENT_IP_HEADER` の注意も読み直す。

## 6. WP-Cron を確実に走らせる

WP-Cron はサイトへのアクセスが無いと走らない。5-2 の `/feed/v1/` の取得は WordPress を読み込まないので、フィードを配っているだけでは取り込みが走らない。サーバーの cron で回すか、ホスティングが走らせていることを確かめる。

`wp cron event run --due-now` は、このプラグインの予定だけでなく、期限の来たサイトの予定（WordPress 本体・WooCommerce・ほかのプラグイン）を全部走らせる。`DISABLE_WP_CRON` もサイトのすべての予定に効く。

### 6-1. 自分でサーバーの cron を作る

cron を短い間隔で回せるサーバーでは、2 の `DISABLE_WP_CRON` と組で使ってよい。`DISABLE_WP_CRON` を入れてサーバーの cron を作らないと、サイトの予定がどれも走らない。

```cron
*/15 * * * * wp --path=/path/to/wordpress cron event run --due-now >/dev/null 2>&1
```

### 6-2. Pressable

何も設定しない。Pressable では `DISABLE_WP_CRON` が wp-config.php の外（Pressable 側）で最初から true になっていて、期限の来た予定は Pressable 側が走らせている。ドキュメントには書かれていないが、2026-10-26 に wc.artws.info で確かめた（`wp config get DISABLE_WP_CRON` は「not defined in the 'wp-config.php' file」、毎分の `action_scheduler_run_queue` が 1〜2 分前に走っていた）。

- `DISABLE_WP_CRON` を wp-config.php に書かない。すでに定義されている定数を define し直すと、PHP が警告を出す。
- `wp cron test` は「The DISABLE_WP_CRON constant is set to true」の Error になるが、Pressable ではそれが正常。
- 管理画面の「Update now」と、EOL の保存による manifest の書き直しは、Pressable 側の次の実行で走る。すぐ走らせるなら、SSH で 7 の WP-CLI のコマンドを使う。
- 下の確認で予定が走っていなかったときだけ、MyPressable の Site Tools > Cron Jobs > Create New で予備のジョブを作る（最短は Hourly）。コマンドは `wp --path=/srv/htdocs cron event run --due-now`。`/srv/htdocs` は、管理画面の Security Hub に出る Directory の `wp-content` より前の部分。

**確認**（WP-CLI。数分あけて 2 回）

```bash
wp cron event list --fields=hook,next_run_gmt,next_run_relative | grep -E 'action_scheduler_run_queue|artws_sec'
# → 毎分の action_scheduler_run_queue の next_run_gmt が、2 回目には先へ進んでいる。
#   artws_sec_* が now のまま残らない。残るなら予定が走っていない（Pressable のサポートに問い合わせ、上の予備のジョブを作る）
```

## 7. 初回の取り込み

次のどちらかで取り込む。

- 管理画面 > 設定 > Security Hub で「Update now」を押す（cron の次の実行で走る）。
- WP-CLI でその場で走らせる:

```bash
wp cron event run artws_sec_feed_update
```

**確認**（設定 > Security Hub）
- [ ] 最後の実行が成功で、plugin / theme / core の件数が 0 でない
- [ ] 除外したレコードが 2% 以下。超えて失敗した場合は除外例を見てから、閾値か検査を見直す（README「取り込み」）
- [ ] EOL（サポートが終わった最低の版。PHP / WordPress / WooCommerce）が今の方針に合っている（既定は PHP 8.2 / WordPress 6.7 / WooCommerce 10.2）

この取り込みで、無料診断が使う照合用のインデックスもできる。それまで `POST /scan` は 503 を返す。

## 8. 動作を確かめる

### 脆弱性フィード

```bash
dig +short wc.artws.info
# → 5-2 なら、Cloudflare の DNS の A レコードの値（Pressable の IP）と同じ。違えば、まだプロキシが入っている

curl -sI -H 'Accept-Encoding: gzip, deflate, br' https://wc.artws.info/feed/v1/vuln-plugins.json.gz
# → 200 で、Content-Encoding の行が無い（圧縮を受け付ける要求でも付かないことを確かめる）
#   Content-Type は application/gzip。5-2 なら Cache-Control: no-store と X-Content-Type-Options: nosniff
#   （custom-redirects.php が返した印）で、Pressable の x-ac が HIT でない
#   CDN を挟むときは、キャッシュから返っていない（Cloudflare なら cf-cache-status が HIT でない）

curl -s https://wc.artws.info/feed/v1/vuln-plugins.json.gz | shasum -a 256
curl -s https://wc.artws.info/feed/v1/manifest.json | jq -r '.files.plugin.sha256'
# → 2 つの値が一致する。CDN を挟むときは、取り込みの直後にも確かめる

curl -s https://wc.artws.info/feed/v1/manifest.json | jq '{version, stale}'
# → version が管理画面の Published version と同じで、stale が false

curl -s https://wc.artws.info/feed/ | head -3
# → 今までどおり RSS（<?xml …）が返る
```

### 無料診断の API

契約のサンプルで実際に 1 回登録し、読めることを確かめてから消す。リポジトリの直下で実行する。

```bash
BASE=https://wc.artws.info/wp-json/artws-sec/v1

# 1. 登録と診断（200 で site_token と report が返る）。-4 は 5 で IPv4 のアドレスを使うため
curl -4 -s -X POST "$BASE/scan" -H 'Content-Type: application/json' \
  --data @tests/fixtures/security-feed/scan-request.json > /tmp/artws-scan.json
jq '{site_token, scan: .report.token, score: .report.score.total, error: .code}' /tmp/artws-scan.json

TOKEN=$(jq -r .site_token /tmp/artws-scan.json)
SCAN=$(jq -r .report.token /tmp/artws-scan.json)

# 2. Authorization ヘッダーがサーバーで落ちないか（200 なら OK、401 なら落ちている → 11 へ）
curl -s -o /dev/null -w '%{http_code}\n' -H "Authorization: Bearer $TOKEN" "$BASE/scan/$SCAN"

# 3. 後片付け: テストの登録を消す（204）
curl -s -o /dev/null -w '%{http_code}\n' -X DELETE -H "Authorization: Bearer $TOKEN" "$BASE/site"
rm /tmp/artws-scan.json

# 4. http の要求を受け付けないか（403、または https へのリダイレクト）
curl -s -o /dev/null -w '%{http_code}\n' -X POST http://wc.artws.info/wp-json/artws-sec/v1/scan
```

初めて入れたときと、プロキシの有無を変えたときは、1 の要求が本当の送信元 IP で数えられたかも確かめる。1 から 1 時間以内に、サーバーの WP-CLI で実行する。

```bash
# 5. <手元の IPv4> は 1 を送った手元のグローバル IPv4 アドレス
KEY=$(wp eval "echo Artws_Sec_Rate_Limiter::key( 'ip', '<手元の IPv4>' );")
wp db query "SELECT hits FROM $(wp db prefix)artws_sec_limits WHERE limit_key = '$KEY'"
# → 1 行出る。出なければ REMOTE_ADDR が送信元になっていない（プロキシが残っている、
#   ARTWS_SEC_CLIENT_IP_HEADER が入っている、など）
```

1 が失敗したときは `error`（`code`）を見る。主なものは次のとおり（ほかは 11）。
- `artws_sec_unavailable`（503）: 7 の取り込みがまだ。
- `artws_sec_https_required`（403）: 2 の HTTPS の設定。
- `rest_…` で始まる 401 / 403: ログインしていない REST の要求を、別のプラグインが止めている。

この手順でサンプルを 1 回送ると、送信元 IP の枠（1 時間 20 回）を 1 回使う。

### データベース

```bash
wp db query "SHOW INDEX FROM $(wp db prefix)artws_sec_sites WHERE Key_name = 'site_key'"
# → Non_unique が 1（同じ URL の登録をいくつでも持てる）
```

## 9. 公開する前に

JP4WC の無料診断（#260）を出す前に、wc.artws.info にプライバシーポリシーと利用規約を載せる（Epic #256 の未決事項）。少なくとも次のことを書く。

- [ ] **保存する項目**:
  - サイトの URL
  - WordPress / WooCommerce / PHP / JP4WC の版
  - インストールしているプラグインとテーマ（slug・版・有効か）
  - セキュリティチェックの結果（pass / fail / unknown だけ。設定値は受け取らない）
  - 同意があるときだけ、売上と注文数の帯（実額ではない）
  - 同意文の版
  - 作ったレポート
- [ ] **保存期間**: 診断は 180 日。180 日診断の無い登録は消える。
- [ ] **削除の方法**: JP4WC の「診断データを削除」で、その登録と診断がすぐ消える。
- [ ] **トークンを失った場合**: 古い登録の診断は API では消せず、保存期間で消える。問い合わせで削除を受けるなら、その手順と、サイトの持ち主であることの確かめ方を書く（同じ URL の登録には、開発環境など他人のものも含まれうる）。
- [ ] **IP アドレス**: レート制限のために、IP アドレスそのものではなく鍵付きのハッシュを持つ。制限の時間（1 時間）が終わったものは、毎日の削除で消える。
- [ ] **データベースのバックアップ**: バックアップを取っている場合、その中の診断が 180 日より長く残りうる。バックアップの保存期間も合わせて書く。
- [ ] **脆弱性データの出典**: Wordfence Intelligence（README「Wordfence Intelligence の利用規約」）。

## 10. 更新するとき

1. 3 で ZIP を作り、4 の「現在のものをアップロードしたもので置き換える」でアップロードする（無効化は要らない）。
2. ZIP の置き換えでは有効化フックが走らない。表の更新は次の要求の `init` で自動で行われる。
3. 下の表で、更新元の版に当てはまる作業をする。
4. `wp option get artws_sec_db_version` と、管理画面の取り込みの結果を確かめる。
5. 前回の反映から `hosting/pressable/custom-redirects.php` が変わっていたら、5-2 の 2 で置き直す（ZIP には入らない）。

| 更新元の版 | 追加の作業 |
|---|---|
| 0.2.x 以前 → 0.3.0 以降 | 7 の取り込みを 1 回走らせて照合用のインデックスを作る（それまで `POST /scan` は 503）。8 の「無料診断の API」を確かめる |
| 0.3.x → 0.4.0 以降 | `artws_sec_db_version` が `2` になったことと、8 の「データベース」を確かめる。0.3.x で登録したサイトのトークンはそのまま使える。ただし移行の直前 1 時間に診断したサイトは、移行の直後にもう 1 回診断できる（一度きり） |

## 11. 困ったとき

| 症状 | 原因と対処 |
|---|---|
| `/feed/v1/manifest.json` が 404（サイトの 404 ページ） | `/feed/v1/` の Alias（5-1）か `custom-redirects.php`（5-2）が無い。ファイルが `ARTWS_SEC_FEED_DIR` にあるかも確かめる |
| `/feed/v1/` が 404 で、サイトの 404 ページではない短い応答 | Pressable の Light Weight 404s がオンで、PHP まで届いていない（5-2 の 1） |
| `custom-redirects.php` を置いたら、サイト全体が 500 か真っ白 | ファイルの構文の誤り（貼り付けの誤りなど）。SFTP で元に戻し、`php -l` で確かめてから置き直す |
| JP4WC が「does not match the sha256 of the manifest」で取り込まない | gz に `Content-Encoding` が付いているか（8 の 2 つ目の curl）、CDN が古い gz を返している。CDN を挟むなら、5 のとおり `/feed/v1/` をキャッシュさせない。すぐ直すなら CDN のキャッシュを消す |
| `POST /scan` がすべて 503（`artws_sec_unavailable`） | 照合用のインデックスが無いか読めない。7 の取り込みを走らせる。取り込みが失敗し続けているなら、管理画面の結果を見る |
| すべての要求が 403（`artws_sec_https_required`） | WordPress が https と認識していない。2 のプロキシの設定（`$_SERVER['HTTPS']`） |
| 正しいトークンでも GET が 401 | `Authorization` ヘッダーが PHP に届いていない。Apache の CGI / FastCGI なら、WordPress 標準の `.htaccess` の `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` があるか確かめる。`Authorization: Bearer` を自分の認証（JWT など）として読むプラグインも外す |
| 多くのサイトがすぐ 429 になる | CDN・プロキシの背後で、全サイトが 1 つの送信元として数えられている。2 の `ARTWS_SEC_CLIENT_IP_HEADER` を入れる。wc.artws.info（プロキシを置かない）で起きたら、8 の `dig` で Cloudflare のプロキシが戻っていないか確かめる |
| `rest_` で始まる 401 / 403 | ログインしていない REST の要求を止めるセキュリティプラグイン。`artws-sec/v1` を除外する |
| 管理画面に stale と出る | 最後の成功から 48 時間を超えた。取得の失敗は 1 時間後に 3 回まで自動で再試行する。続くなら管理画面のエラーを見る |
| Wordfence 側の整理で件数が減り、取り込みが失敗した | 管理画面の「Allow fewer records on the next update」で、次の 1 回だけ件数の検査を外す |
| `artws_sec_db_version` が古いまま | 表の作成・変更の権限が無いか、移行が失敗している。権限を確かめる。次の要求で自動でやり直す |

### 止める・消す

- **無効化**:
  - cron の予定を外す。
  - フィードを stale にする。ファイルは配信され続けるが、もう更新されない。
  - 診断のデータは残る。
- **削除（プラグインの削除）**:
  - 無料診断の表 4 つと `artws_sec_db_version` を消す。
  - 脆弱性フィード（S1 の設定と配信ファイル）は残る。
