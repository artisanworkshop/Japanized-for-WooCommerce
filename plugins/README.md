# plugins/

JP4WC と連携するサーバー側の WordPress プラグインを置く。ここにあるものは JP4WC の配布物（リリース ZIP と WordPress.org）に含めない。

| プラグイン | 動かす場所 | 内容 |
|---|---|---|
| `artws-security-hub/` | wc.artws.info | セキュリティ診断の脆弱性フィード配信と無料診断 API（Epic #256） |

## JP4WC との分離

| | JP4WC 本体 | `plugins/<name>/` |
|---|---|---|
| 配布 | タグ push で WordPress.org とリリース ZIP | 含めない。`.distignore` の `/plugins`（ZIP 作成と WordPress.org デプロイはどちらも `rsync --exclude-from=.distignore`）、`.gitattributes` の `plugins/* export-ignore`、デプロイワークフローの ZIP 検査で確認する |
| lint | ルートの `composer lint`（`.phpcs.xml.dist` で `plugins/` を除外） | 各プラグインの `composer lint`（専用の `.phpcs.xml.dist`） |
| テスト | ルートの `composer test-local`（`tests/Unit/`） | 各プラグインの `composer test` |
| CI | `testing.yml`（`plugins/**` だけの変更では走らない） | プラグインごとのワークフロー（例 `artws-security-hub.yml`） |
| 反映 | JP4WC のリリース | 各プラグインの README の手順。JP4WC のタグとは連動しない |

新しいプラグインを足すときも、専用の `composer.json`・`.phpcs.xml.dist`・`phpunit.xml.dist`・ワークフローを用意する。

## JSON 形式（契約）を変えるとき

JP4WC とサーバーの接点は JSON だけで、その形式は `tests/fixtures/security-feed/`（README・JSON Schema・サンプル）で定める。

- 形式を変える PR は、Schema とサンプルを同じ PR で変え、**本体とサーバーの両方のテストを通す**。このディレクトリを変える PR では `testing.yml` と `artws-security-hub.yml` の両方が走る
- 配布済みの JP4WC は更新を強制できない。互換を壊す変更は `schema_version` を上げて新しい URL（例 `/feed/v2/`）で並行配信し、古い形式も配信し続ける

## 公開リポジトリでの注意

このリポジトリは公開されているので、サーバーのコードも公開される。API キー・署名の共通鍵・DB 情報はコードに入れず、サーバーの `wp-config.php` の定数か環境変数から読む。レート制限などの乱用対策は、コードを読まれても成り立つ作りにする。
