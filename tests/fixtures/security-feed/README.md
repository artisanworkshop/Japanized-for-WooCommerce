# セキュリティ診断の JSON 契約（v1）

JP4WC（クライアント）と wc.artws.info の `artws-security-hub`（サーバー、`plugins/artws-security-hub/`）がやり取りする JSON の形式を、このディレクトリの JSON Schema とサンプルで定める。両者の接点はここだけで、本体とサーバーのテストは同じファイルを読む。

| やり取り | Schema | サンプル |
|---|---|---|
| フィードの manifest（`GET /feed/v1/manifest.json`） | `schema/manifest.schema.json` | `manifest.json` |
| 脆弱性ファイル（`GET /feed/v1/vuln-{plugins,themes,core}.json.gz` を展開したもの） | `schema/vuln-file.schema.json` | `vuln-*.json`（`vuln-*.json.gz` はそのまま配信される形） |
| 無料診断の送信データ（`POST /wp-json/artws-sec/v1/scan` の本文） | `schema/scan-request.schema.json` | `scan-request.json` |
| 診断の応答（`POST /scan` と `GET /scan/{token}` の 200。レポートと、登録時だけ `site_token`） | `schema/scan-response.schema.json` | `scan-response.json` |
| レポート（診断の応答の `report`） | `schema/report.schema.json` | `report.json` |
| `artws-sec/v1` のエラー応答 | `schema/error.schema.json` | `error-rate-limited.json` |

共通の定義は `schema/common.schema.json`。Schema は JSON Schema draft 2020-12 で、`$id` の `https://wc.artws.info/schema/security/v1/` は識別子（配信はしない）。サンプルの slug・UUID・トークンは架空の値。

`pattern` は JSON Schema（ECMA-262）の意味で、`$` は文字列の末尾にだけ一致する。PHP の `preg_match()` で同じパターンを使うときは `D` 修飾子を付ける（付けないと `$` が末尾の改行の直前にも一致し、`"scn_…\n"` のような値を通す）。WordPress の REST API の引数スキーマの `pattern` は `D` を付けずに照合するので、トークンなど URL に入れる値の検証には使わない。

## Schema で表せない取り決め

### 形式のバージョン
- すべての JSON が `schema_version: 1` を持つ。読む側は 1 以外を受け付けない。
- 書く側は Schema に無い項目を出さない（すべてのオブジェクトが `additionalProperties: false`）。項目を足すときは、先にこの Schema に足す。
- 読む側は Schema を検査に使わず、知らない項目を無視する。項目の追加は後方互換な変更とし、`schema_version` は据え置く。配布済みの JP4WC は更新を強制できないので、新しい項目が無くても動く作りにする。
- enum に値を足すこと（`severity`・`requirement`・`files` の種類・`check_id` の接頭辞など）、項目の削除・改名・意味の変更は互換を壊す変更とする。それでも読む側は、知らない値を持つ要素を読み飛ばし、全体をエラーにしない。
- 互換を壊す変更は `schema_version: 2` とし、新しい URL で並行運用する（フィードは `/feed/v2/`、REST は `artws-sec/v2`）。`/feed/v1/` と `artws-sec/v1` は、使う JP4WC が無くなるまで提供し続ける。

### フィード
- 配置: `https://wc.artws.info/feed/v1/manifest.json`。脆弱性ファイルは manifest の `files.<type>.file` を同じディレクトリからの相対名として取得する（`file` はディレクトリを含まない名前に限る）。クライアントは基底 URL をフィルタで差し替えられるようにする（#257）。
- `sha256` は配信する `.json.gz` のバイト列のハッシュ（小文字16進）で、展開後の JSON のハッシュではない。クライアントは「取得 → sha256 検証 → 展開」の順に扱う。
- `.json.gz` は `Content-Type: application/gzip` で、`Content-Encoding: gzip` を付けずに配信する。付けると HTTP クライアントが自動で展開し、sha256 が合わなくなる。
- サーバーは脆弱性ファイルを置いてから manifest を更新する。それでも CDN のキャッシュなどで manifest と脆弱性ファイルの組が食い違うことはあり得るので、クライアントは sha256 が合わなければ前回のデータを使い続ける。
- `count` はファイル内のレコード数（slug ごとの配列の長さの合計）。1件の Wordfence レコードが複数の slug に影響する場合、slug ごとに1件と数える。
- 各レコードの `software.type` はファイルの `type` と、`software.slug` は置かれている slug と一致する。コアのファイルの slug は `wordpress` だけ。
- `stale` が true のとき、サーバーは Wordfence からの更新に失敗して古いデータを配信している。クライアントは「データが古い」旨を表示する。
- `eol.<name>.min_supported` より古い PHP / WordPress / WooCommerce はサポート終了とみなす（`version_compare( 現在, min_supported, '<' )`。V-03）。値はサーバーの管理画面で設定する（サンプルの値は例）。

### 脆弱性レコード
- 項目名は Wordfence Intelligence V3 の production フィードに合わせる。違うのは2点: V3 の `affected_versions`（範囲文字列をキーにしたオブジェクト）を配列にすることと、V3 に無い `reference`（下記）を持つこと。
- バージョン `v` が影響を受けるのは、`affected_versions` のどれか1つの範囲について次の両方が成り立つとき（V3 と同じ判定。比較は `version_compare()`）:
  - `from_version` が `*`、または `from_version < v`、または `from_inclusive` かつ `from_version == v`
  - `to_version` が `*`、または `v < to_version`、または `to_inclusive` かつ `v == to_version`
- インストール済みのバージョンが空のソフトウェア（Version ヘッダーが無いもの）は照合しない。`version_compare( '', … )` は常に「古い」と判定するので、照合すると `from_version: "*"` の範囲で必ず「影響あり」になる。
- slug は WordPress.org と Wordfence の slug で、インストール済みのものとはプラグインのディレクトリ名（単一ファイルのプラグインはファイル名から `.php` を除いたもの）、テーマのディレクトリ名で突き合わせる。
- `reference` は wordfence.com のそのレコードのページ。データのライセンスがレコードへのリンクを求めるので、レコードを表示する所では必ずリンクする。`copyrights` と `attribution` は V3 の `copyrights` から作り、クライアントは画面に表示する（文言は S1 で実データから写す。サンプルの文言は仮）。

### V3 からの変換（S1 のサーバー）
- V3 の `informational` が true のレコードは除外する。1件のレコードが複数のソフトウェアに影響する場合は、`software[]` の要素ごとに1件ずつ、その slug の下に置く。
- `published` は V3 の文字列（UTC）を `YYYY-MM-DDTHH:MM:SSZ` に直す。無いか解釈できなければ `null`。
- `reference` は V3 の `references` から取らず、`id` から組み立てる（`https://www.wordfence.com/threat-intel/vulnerabilities/id/<id>`。`references` は空のことがある）。
- 変換したレコードはレコード単位で Schema を検査し、合わないもの（バージョンに空白を含む、64 文字を超えるなど）は除外して件数を記録する。1件の不備でフィード全体を止めない。
- ただし、除外が一定の割合を超えたとき、または件数が前回から大きく減ったとき（閾値は S1 で決める）は、Wordfence 側の形式変更などを疑って生成失敗として扱う。生成に失敗したときは前回のファイルを配信し続けて `stale` を立てる。ほぼ空のフィードを `stale: false` で出すと、クライアントは「脆弱性なし」と表示してしまう。
- `https://wc.artws.info/feed/` は WordPress 本体の RSS の URL でもある。ドキュメントルートに実ディレクトリ `feed/` を作ると、サーバーが `/feed/` を WordPress に渡さなくなりサイトの RSS が壊れるので、ファイルは別の場所に置き `/feed/v1/` だけを Alias（nginx なら location）で配信する。

### 送信データ（POST /scan）
- 同意画面に一覧で示した項目だけを送る。Schema に無い項目（管理者メール、注文の実額、パス、設定値など）は送らない。サーバーは Schema に無い項目を保存しない。
- `revenue_band` と `order_band` は別の同意（既定オフ）を得たときだけ、2つ一緒に含める。同意が無いときはどちらもキーごと省く（片方だけは Schema が拒否する）。
- `software[].version` は各プラグイン・テーマの Version ヘッダーの値をそのまま送る。64 文字（コードポイント数。PHP では `mb_strlen( $v, 'UTF-8' )`）を超えるもの、または制御文字（U+0000〜U+001F、U+007F〜U+009F）を含むものは空文字で送り、サーバーはそのソフトウェアを照合しない。
- `wp_ver`・`wc_ver`・`php_ver`・`jp4wc_ver` は空にできない（Schema が拒否する）。WordPress・WooCommerce・PHP・JP4WC 自身の版で、上の条件に当たる値は無い前提で、空文字で送る規則は適用しない。
- `consent_version` はサーバーが知っている版だけを受け付ける。
- `reregister` はトークンを失ったサイトが登録し直す時だけ `true` で付ける（下記「REST」）。

### レポート
- 文字列はすべてプレーンテキスト。クライアントはエスケープして表示し、HTML として解釈しない。URL（`reference`）は https のみ。
- `findings` は優先順に並んでおり、クライアントはその順に表示する。`score.total` は4要件のスコアの最小値。
- スコアは JP4WC のセキュリティタブと同じ計算（各要件 100 点から、失敗したチェックごとに重大度で critical 25 / high 15 / medium 8 / low 3 点を引く。V-01 は脆弱性の件数によらず1チェックとして、見つかった中で最も重い重大度で1回引く）。ただし V-01（既知の脆弱性）と V-03（サポート終了）はサーバーが自分で判定し、送信データの `local_findings` のその2つは使わない。
- V-01 は脆弱性レコード1件につき1件の finding で、`reference` はそのレコードのページ。
- 文言は `locale` が `ja` で始まれば日本語、それ以外は英語。

### REST（`artws-sec/v1`、S2）
- HTTPS のみ（http の要求は 403）。応答はすべて `Cache-Control: no-store`。
- 認証はサイトごとのトークン（`site_token`）。サーバーが発行し、自分は SHA-256 のハッシュだけを持つ。クライアントは `Authorization: Bearer <site_token>` で送り、画面・ログ・自分の REST 応答に出さない。
- サイトは `site_url` で見分ける。スキームの違い（http / https）、ホストの大文字小文字、末尾の `/` は同じサイトとみなす。
- `POST /scan`:
  - 初回（`site_url` が未登録で `Authorization` なし）はサイトを登録し、応答に `site_token` を入れる。`site_token` が応答に入るのは登録した時と再登録した時だけ。
  - 2回目以降は `Authorization` が必須。登録済みの `site_url` にトークンなし・不一致で来たら 401。未登録の `site_url` にトークン付きで来た場合（サイトの移転、ステージングへの複製）も 401。
  - 401 を受けたクライアントは、利用者の操作で `reregister: true` を付けて送り直せる。再登録はその `site_url` の過去の診断データをすべて削除してから新しい `site_token` を発行する（旧トークンは使えなくなる）。
  - 知らないキーは 400 にせず捨てる（保存もしない）。Schema に合わない値・未知の `consent_version` は 400。本文が 1 MB を超えたら 413。
  - レート制限: 1サイト（`site_url`）1時間1回、送信元 IP（IPv6 は /64）1時間20回。初回登録と再登録も数える。400・401・403・413・503 になった要求は数えない。超過したら 429 で、`data.retry_at` と `Retry-After` ヘッダーに次に実行できる時刻を入れる。
  - サーバーがまだ脆弱性データを持っていないとき・読めないときは 503（何も登録しない）。認証の判定（401）はこれより先に行う。
- `GET /scan/{token}`: `Authorization` が必須。トークンの形式が違う・診断が無い（期限切れ・削除済みを含む）なら 404、診断はあるがトークンが一致しないなら 401。
- `DELETE /site`: `Authorization` のトークンのサイトと、その診断をすべて削除して 204（本文なし）。未知のトークンは 401（削除済みの場合を含むので、クライアントは 401 を「削除済み」として扱ってよい）。
- 診断は 180 日保存し、それより古いものは毎日削除する。180 日診断の無いサイトはトークンごと削除する（その後の診断は 401 になり、再登録が要る）。
- エラーの本文は `{schema_version, code, message, data: {status, retry_at?}}`（`schema/error.schema.json`）。`message` は英語のログ用で、クライアントは画面に出さず、ステータスに応じた自分の文言を出す。WordPress 自身が返すエラー（存在しない経路、JSON でない本文など）はこの形でないので、クライアントはステータスで判断する。`code`:

| ステータス | `code` | 意味 |
|---|---|---|
| 400 | `artws_sec_invalid_request` | 本文が Schema に合わない |
| 400 | `artws_sec_unknown_consent_version` | サーバーが知らない `consent_version` |
| 401 | `artws_sec_unauthorized` | トークンが無い・合わない |
| 403 | `artws_sec_https_required` | http で送った |
| 404 | `artws_sec_scan_not_found` | 診断が無い |
| 413 | `artws_sec_request_too_large` | 本文が大きすぎる |
| 429 | `artws_sec_rate_limited` | レート制限 |
| 500 | `artws_sec_server_error` | 保存・読み出しに失敗した（レート制限を確かめられないときも、通さずにこれを返す。登録・再登録の後で診断を保存できなかったときは、登録を取り消す・前のトークンに戻す） |
| 503 | `artws_sec_unavailable` | 脆弱性データの準備ができていない |

## 形式を変えるとき
- Schema とサンプルを同じ PR で変え、本体（`composer test-local`）とサーバー（`plugins/artws-security-hub` の `composer test`）の両方のテストを通す。このディレクトリを変える PR では、両方の CI（`testing.yml` と `artws-security-hub.yml`）が走る。
- `vuln-*.json` を変えたら `php tests/fixtures/security-feed/regenerate.php` で `.json.gz` と manifest の `sha256` / `count` を作り直す。
