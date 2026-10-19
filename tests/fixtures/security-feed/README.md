# セキュリティ診断の JSON 契約（v1）

JP4WC（クライアント）と wc.artws.info の `artws-security-hub`（サーバー、`plugins/artws-security-hub/`）がやり取りする JSON の形式を、このディレクトリの JSON Schema とサンプルで定める。両者の接点はここだけで、本体とサーバーのテストは同じファイルを読む。

| やり取り | Schema | サンプル |
|---|---|---|
| フィードの manifest（`GET /feed/v1/manifest.json`） | `schema/manifest.schema.json` | `manifest.json` |
| 脆弱性ファイル（`GET /feed/v1/vuln-{plugins,themes,core}.json.gz` を展開したもの） | `schema/vuln-file.schema.json` | `vuln-*.json`（`vuln-*.json.gz` はそのまま配信される形） |
| 無料診断の送信データ（`POST /wp-json/artws-sec/v1/scan` の本文） | `schema/scan-request.schema.json` | `scan-request.json` |
| レポート（`POST /scan` の 200 応答と `GET /scan/{token}`） | `schema/report.schema.json` | `report.json` |

共通の定義は `schema/common.schema.json`。Schema は JSON Schema draft 2020-12 で、`$id` の `https://wc.artws.info/schema/security/v1/` は識別子（配信はしない）。サンプルの slug・UUID・トークンは架空の値。

## Schema で表せない取り決め

### 形式のバージョン
- すべての JSON が `schema_version: 1` を持つ。読む側は 1 以外を受け付けない。
- 書く側は Schema に無い項目を出さない（すべてのオブジェクトが `additionalProperties: false`）。項目を足すときは、先にこの Schema に足す（後方互換な追加なら `schema_version` は据え置き）。
- 互換を壊す変更は `schema_version: 2` とし、フィードは `/feed/v2/` で並行配信する。配布済みの JP4WC は更新を強制できないので、`/feed/v1/` は使われなくなるまで配信し続ける。

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
- 項目名は Wordfence Intelligence V3 の production フィードに合わせる。V3 の `affected_versions`（範囲文字列をキーにしたオブジェクト）は配列にする。V3 の `informational` が true のレコードはサーバーで除外する。
- バージョン `v` が影響を受けるのは、`affected_versions` のどれか1つの範囲について次の両方が成り立つとき（V3 と同じ判定。比較は `version_compare()`）:
  - `from_version` が `*`、または `from_version < v`、または `from_inclusive` かつ `from_version == v`
  - `to_version` が `*`、または `v < to_version`、または `to_inclusive` かつ `v == to_version`
- `reference` は wordfence.com のそのレコードのページ。データのライセンスがレコードへのリンクを求めるので、レコードを表示する所では必ずリンクする。`copyrights` と `attribution` は V3 の `copyrights` から作り、クライアントは画面に表示する（文言は S1 で実データから写す。サンプルの文言は仮）。

### 送信データ（POST /scan）
- 同意画面に一覧で示した項目だけを送る。Schema に無い項目（管理者メール、注文の実額、パス、設定値など）は送らない。サーバーは Schema に無い項目を保存しない。
- `revenue_band` と `order_band` は別の同意（既定オフ）を得たときだけ含める。無いときはキーごと省く。
- `consent_version` はサーバーが知っている版だけを受け付ける。

### レポート
- 文字列はすべてプレーンテキスト。クライアントはエスケープして表示し、HTML として解釈しない。URL（`reference`）は https のみ。
- `findings` は優先順に並んでおり、クライアントはその順に表示する。`score.total` は4要件のスコアの最小値。
- エラー応答（レート制限の 429 など）の形式は S2（#263）で定める。

## 形式を変えるとき
- Schema とサンプルを同じ PR で変え、本体（`composer test-local`）とサーバー（`plugins/artws-security-hub` の `composer test`）の両方のテストを通す。このディレクトリを変える PR では、両方の CI（`testing.yml` と `artws-security-hub.yml`）が走る。
- `vuln-*.json` を変えたら `php tests/fixtures/security-feed/regenerate.php` で `.json.gz` と manifest の `sha256` / `count` を作り直す。
