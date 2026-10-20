# dev-cycle 最終報告: feat/261-artws-security-hub-foundation

## 開発内容
- タスク: セキュリティ診断 サーバー側 S0(issue #261): `plugins/artws-security-hub` の土台(配布除外・Lint/テスト/CI 分離)と、本体とサーバーの間の JSON 形式の確定
- PR: #264 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264(head は upstream の同名ブランチ)
- 承認された計画の要約: `plugins/artws-security-hub/` に専用の composer・phpcs・phpunit・ワークフローを持つ土台を置き、本体の配布物(`.distignore`・export-ignore・デプロイ時の ZIP 検査)・lint(相対の除外パターン)・テスト(`testing.yml` の paths-ignore)から外す。JSON 契約は `tests/fixtures/security-feed/` に JSON Schema(draft 2020-12)・サンプル・README で定め、両側のテストが同じファイルを読む。計画時の決定: Wordfence V3 準拠の項目名 / POST /scan の送信データの Schema も含める / フィードは `/feed/v1/` / サーバーは PHP 8.3+ / 新規依存 opis/json-schema(サーバー側の dev のみ)
- コミット:

| sha | メッセージ |
|---|---|
| 74796a5 | build: keep plugins/ out of the JP4WC package, lint and test matrix |
| 1698304 | test: define the security check JSON contract with schemas and samples |
| 5c45b61 | feat: add the artws-security-hub server plugin skeleton |
| b5375b8 | docs: describe plugins/ and the security check contract in CLAUDE.md |
| a9ed1c4 | docs: record dev-cycle implementation step |
| db71c63 | fix: spell out compatibility, conversion and matching rules in the security check contract |
| 9890110 | test: cover every schema constraint in the contract tests |
| b6f3a77 | build: keep plugins/ out of the JP4WC translation template |
| 9f834d3 | docs: record review-loop round 1 |
| 9b53b66 | fix: fail the feed build when too many Wordfence records are dropped |
| a20f03c | docs: record review-loop round 2 |
| b042913 | fix: make the contract schemas enforce what the README promises |
| e57b1ec | test: check every object schema is closed and cover the G1 constraints |
| 5820c79 | docs: record dev-cycle gate round 1 |
| 2fc543e | docs: limit the empty-version rule to software versions in the contract |
| 50272af | docs: record dev-cycle gate round 2 |

- 設計ドキュメントからの逸脱:
  - 設計書(Claude Docs)の JSON 例(`affected[{from,to,inclusive}]`・`patched_in`・`cvss` が数値・`ref`・`fix` が文字列)とは異なる形式に確定した。設計書は書き換えていない
  - #257 / #262 の本文の表記(`/feed/manifest.json`、manifest 直下の `sha256` / `count`、`license`、`references[0]`)は、`tests/fixtures/security-feed/README.md` の形式(`/feed/v1/`、`files.<type>`、`copyrights`、`reference`)が正になる。issue 本文は未更新(R1-X2)

## review-loop(PR 前)
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 6 / Low 11 / 対象外 2(High 1・Low 1) | 6 | 13 |
| R2(APPROVE) | R1 の 6 件すべて解消、新規 Medium 1 / Low 3 | 1 | 3 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 6 | 6 | 0 | 未収束 |
| G2 | Copilot | 1(本文) | 1 | 0 | 未収束 |
| G3 | Copilot | 0(本文は既出の保留分への言及のみ) | 0 | 0 | 収束(上限の 3 回目) |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 | Copilot | Medium | 構造テストが `properties` の無い object を見ていない | e57b1ec | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321939239 |
| G1-2 | Copilot | Medium | `software[].version` が C1 制御文字を通す | b042913 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321939284 |
| G1-3 | Copilot | Medium | 2 つの帯の依存関係が無い(`dependentRequired`) | b042913 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321939300 |
| G1-4 | Copilot | Medium | レコードの type がファイルの type に縛られていない(plugin / theme) | b042913 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321939338 |
| G1-5 | Copilot | Low | token の末尾改行(Schema は誤検知。PHP で使い回す時の注意を README に追記) | b042913 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321975586 |
| G1-6 | Copilot | Medium | トップレベルの version も C1 を通す | b042913 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#discussion_r4321975629 |
| G2-1 | Copilot(本文) | Medium | README の空文字規則が `wp_ver` などにも読める | 2fc543e | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/264#pullrequestreview-5581456452 |

### 修正しなかった指摘(PR 上で未解決のまま残してある)
ゲートで保留にした指摘は無い(未解決スレッド 0)。review-loop で backlog に送ったものは `docs/review-backlog.md` の 2026-10-20 の行(R1-X1・R1-X2・R1-L1・R1-L5〜L11・R2-4 が未対応。R1-L3・R1-L4・R2-2・R2-3 は G1 で対応済み)。

## 品質ゲート
- CI: 50272af で Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39381604101 と artws-security-hub https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39381603961 が green
- 品質チェック: 本体 PHPUnit 428 件 OK、本体 lint は main と同じ 94 errors / 12 warnings(増加なし)、サーバー lint 0・PHPUnit 76 件 OK。追加した Schema の制約は mutate-check で外すと該当ケースが落ちることを確認

## 次にできること(人間の判断)
- **R1-X1(High、差分外・既存)**: リリース ZIP 自体が WordPress.org の SVN に入っている(trunk と tags/2.9.17 に `woocommerce-for-japan-2.9.17.zip`)。`.distignore` に `/*.zip` を足せば止まる。2.9.18 のタグの前に別 PR での対応を推奨
- #257 / #262 の本文を契約に合わせる(R1-X2)。設計書(Claude Docs)の JSON 例の更新
- 保留分の修正: `/dev-cycle fix <ID>`(backlog の R1-L9〔ZIP 検査を SVN 操作より前に〕など)
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`
