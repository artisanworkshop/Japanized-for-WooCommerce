# セキュリティ診断の JSON 契約（v1）

JP4WC（クライアント）と wc.artws.info の `artws-security-hub`（サーバー、`plugins/artws-security-hub/`）がやり取りする JSON の形式を、このディレクトリの JSON Schema とサンプルで定める。両者の接点はここだけで、本体とサーバーのテストは同じファイルを読む。

| やり取り | Schema | サンプル |
|---|---|---|
| フィードの manifest（`GET /feed/v1/manifest.json`） | `schema/manifest.schema.json` | `manifest.json` |
| 脆弱性ファイル（`GET /feed/v1/vuln-{plugins,themes,core}.json.gz` を展開したもの） | `schema/vuln-file.schema.json` | `vuln-*.json`（`vuln-*.json.gz` はそのまま配信される形） |
| 無料診断の送信データ（`POST /wp-json/artws-sec/v1/scan` の本文） | `schema/scan-request.schema.json` | `scan-request.json` |
| レポート（`POST /scan` の 200 応答と `GET /scan/{token}`） | `schema/report.schema.json` | `report.json` |

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

### レポート
- 文字列はすべてプレーンテキスト。クライアントはエスケープして表示し、HTML として解釈しない。URL（`reference`）は https のみ。
- `findings` は優先順に並んでおり、クライアントはその順に表示する。`score.total` は4要件のスコアの最小値。
- エラー応答（レート制限の 429 など）の形式は S2（#263）で定める。

## 形式を変えるとき
- Schema とサンプルを同じ PR で変え、本体（`composer test-local`）とサーバー（`plugins/artws-security-hub` の `composer test`）の両方のテストを通す。このディレクトリを変える PR では、両方の CI（`testing.yml` と `artws-security-hub.yml`）が走る。
- `vuln-*.json` を変えたら `php tests/fixtures/security-feed/regenerate.php` で `.json.gz` と manifest の `sha256` / `count` を作り直す。
