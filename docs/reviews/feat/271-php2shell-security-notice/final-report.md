# dev-cycle 最終報告: feat/271-php2shell-security-notice

## 開発内容
- タスク: issue #271 — PHP2Shell(CVE-2026-109618 / CVE-2026-109619)未対応の PHP で、管理画面に更新を促すモーダルを出す
- PR: #272 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/272
- 承認された計画の要約: 共通ディレクトリ `includes/artws-php-security-notice/`(`register.php` の登録関数 + plugins_loaded 5 で最新版だけを読むローダー、`ArtisanWorkshop\PhpSecurityNotice\Notice`)を PHP 7.0 互換で作り、JP4WC のメインファイルから自分のコピーを登録して `woocommerce-for-japan` ドメインの文言を渡す。`<dialog>` のモーダル、「今後表示しない」は admin-ajax(nonce・権限)で user meta に保存。テストは境界値・ローダー(別プロセス)・dismiss・AJAX・権限・画面・出力
- コミット:
  | sha | メッセージ |
  |---|---|
  | 78a602f | feat: add a shared PHP security notice for PHP2Shell |
  | 9ce110a | feat: show the PHP2Shell notice from Japanized for WooCommerce |
  | 972d378 | fix: show the PHP2Shell notice in the bundled translation until the language pack has it |
  | 7eda6ca | fix: check the notice capability in one place and send the dismissal to a relative URL |
  | d544364 | docs: record review-loop R1 and R2 for #271 |
  | 76a7e5c | test: grant manage_woocommerce by hand instead of using the shop_manager role |
  | 9ee8001 | fix: skip the PHP security notice instead of stopping a request during an update |
  | bef574f | docs: record dev-cycle gate round 1 |
  | 891ced6 | docs: record dev-cycle gate round 2 |
- issue / 計画からの逸脱(すべて人間の判断済み、または計画で承認済み):
  - changelog はこの PR に入れない(bump PR に集約)。案は PR 本文に記載
  - 記事 URL `https://wc4jp-pro.work/php2shell-php-update/` はスラッグ案のまま(**マージ前に公開 URL と一致するか確認が必要**)
  - インライン CSS / JS は `wp_register_*( false )` + `wp_add_inline_*`、`<dialog>` は `admin_footer`
  - **計画外の追加(ユーザー判断)**: wp.org の日本語言語パックが同梱 .mo より優先され、新しい文言が英語で出ることを wp-env で確認 → 文言 callback の実行中だけ gettext フィルターで不足分を同梱 .mo から引く(972d378)

## review-loop(PR 前)
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 2 / Low 9(+ この PR の誤り 1) | 2(+1) | 9 |
| R2 | 新規 Low 1 | 0 | 1 |
APPROVE(R2)。詳細: R1.md / R2.md

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 5 | 2 | 3 | 未収束 |
| G2 | Copilot | 2 | 0 | 2 | 未収束 |
| G3 | Copilot | 0(総評に詳細のない軽微な点 1) | 0 | 1 | 収束(🟢 Approval recommended、3 回の上限に到達) |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-2 | Copilot | Medium | ローダーの `is_readable()` → `require_once` の間に更新でファイルが消えると fatal → `include_once` にし、最新版が無い状態のテストを追加 | 9ee8001 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/272#discussion_r4345038756 |
| G1-3 | Copilot | Medium | メインファイルの `require_once register.php` も同じ → 読めるときだけ `include_once`、`function_exists()` を確かめて登録 | 9ee8001 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/272#discussion_r4345071061 |

### 修正しなかった指摘(PR 上で未解決のまま残してある)
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Copilot | (High 相当の主張) | 誤検知。`wp_get_active_and_valid_plugins()` は依存関係を見ずに読み込む(WordPress 7.1.3 で確認、根拠を返信) | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/272#discussion_r4345038705 |
| G1-4 | Copilot(本文) | Low | テストの AJAX URL の固定文字列。backlog R2-L1 と同じ | なし |
| G1-5 | Copilot(本文) | Low | `<dialog>` 非対応ブラウザで Esc が効かない。対象ブラウザはすべて対応 | なし |
| G2-1 | Copilot(本文) | Low | CVE 番号のフィルター。issue の要件外、脆弱性ごとにコードで変える | なし |
| G2-2 | Copilot(本文) | — | changelog が無い。bump PR に集約する運用(承認済み) | なし |
| G3-1 | Copilot(本文) | Low | 翻訳カタログの参照の細かい点(詳細なし。既存エントリの行番号のずれと推定)。POT 再生成 PR で直す | なし |

## 品質ゲート
- CI: https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39733617578 green(891ced6)
- 品質チェック: `composer lint` は main と同じ既存の 94 errors / 12 warnings(新規ファイルは指摘なし)、テストファイルは `phpcs --standard=WordPress` で指摘なし、共有ディレクトリは PHPCompatibilityWP `7.0-` で指摘なし、`composer test-local` 499 件 green
- wp-env(PHP 8.3.35、日本語サイト)で目視確認済み

## 次にできること(人間の判断)
- **記事 URL の確認**: wc4jp-pro.work の解説記事の公開 URL が `https://wc4jp-pro.work/php2shell-php-update/` と違えば、マージ前に `Notice::INFO_URL` を直す
- 保留分の修正: `/dev-cycle fix G1-4 G2-1` など、または `/fix-copilot-review 272`
- backlog(R1-L1〜L9、R2-L1)のうち、決済プラグインへの同梱前に効いてくるもの: R1-L1(PHP 7.0 互換の自動チェック、composer.json の変更)、R1-L3(同梱先にも同梱 .mo の補完が要る)、R1-L4(登録する版数のずれ防止)
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`。リリース時は 2.9.20 の bump PR に PR 本文の changelog 案を `/release-bump add 272` で追加し、リリース後に translate.wordpress.org で 9 文字列を訳す(言語パックが更新されれば同梱 .mo の補完は不要になる)
- 決済プラグイン(Pay.jp / Paidy / Paygent / Epsilon)への同梱は各リポジトリで(`diff -r` で中身を一致させる)
