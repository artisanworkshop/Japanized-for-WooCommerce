# dev-cycle 状態: chore/321-composer-lint-ci
- CI: あり
- 記録: docs/reviews/chore/321-composer-lint-ci/
- タスク: issue #321 composer lint を main で通し、PR の CI で走らせる（milestone 3.1.0、backlog 2026-10-27 R1-X1）
- 開始: 2026-11-11
- モード: auto-commit（確認ゲートなし）、同時依頼
- PR: #326 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/326
- 現在のステップ: 6（G2 依頼。Copilot のみ）
- Copilot: 依頼 1 回 / 未収束（G1 で本文に 1 件、修正）
- Codex: 依頼 1 回 / 収束（G1 で「Didn't find any major issues」）

## 計画で決めたこと（2026-11-11 承認）
- `*.asset.php`（自動生成）は `.phpcs.xml.dist` で相対パターン `\.asset\.php$` により対象外
- 共有フレームワーク `includes/jp4wc-framework/class-jp4wc-framework.php` は編集せず、ルールセットで PrefixAllGlobals と DiscouragedPHPFunctions だけ外す（Pro・paidy-wc のコピーとそろえるため）
- 公開名（Paidy の名前は paidy-wc と共有、`is_woocommerce_active` は Pro と共有、古いフック・コールバック）は改名せず、理由付きの 1 行 `phpcs:ignore`
- 動きの変わらないコード変更は別コミット: uninstall の関数の改名、`wp_register_script` の `in_footer` に `false` を明示、空の `return;` を削除
- CI の `lint` ジョブは pull_request・schedule・workflow_dispatch だけ。タグのデプロイ（workflow_call）では走らせない（composer.lock が無いので WPCS の新版でリリースが止まりうる）。G1-1 でジョブ単位の `if` をやめ、ステップだけを飛ばす形にした
- 違反を入れて落ちるかは使い捨てブランチ `chore/321-lint-probe` を upstream に push して workflow_dispatch で確かめ、確かめた後に削除（計画の承認で許可済み）
- changelog の行なし

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-11 | 1 | 計画承認 |
| 2026-11-11 | 2 | 実装コミット 5 件（6f08841 ruleset / ad3c184 comments / 42bdfa8 behaviour-neutral code / 24b5561 CI / 91e6cf8 docs）。`composer lint` 0 件。`php -w` の比較で差は計画の 3 か所だけ。`composer test-local` 1093 件 OK |
| 2026-11-11 | 3 | review-loop R1: Medium 2 件（R1-1 フレームワークの除外をメッセージ単位に f3429c9、R1-2 lint ジョブのトークンを read に 126bb0e）、Low 2・対象外 2 を backlog。R2 APPROVE（R2-L1 を backlog） |
| 2026-11-11 | 4 | upstream に push（1a9ee9b、T=2026-11-11T09:06:26Z）、PR #326 作成（milestone 3.1.0） |
| 2026-11-11 | 4 | 違反の確認: 使い捨てブランチ chore/321-lint-probe（25f54b3）を upstream に push、workflow_dispatch の run 42992502432 で PHPCS の Lint が failure（exit 2）。残りのジョブを取り消し、ブランチを upstream・ローカルとも削除。PR にコメント・本文を更新 |
| 2026-11-11 | 7 | G1（1a9ee9b、T=09:06:26Z）: CI green（PHPCS 16s）。Codex 指摘なし＝収束。Copilot 本文 1 件 G1-1（タグのデプロイでジョブ単位の skip に頼る）→ 0198875 でステップ単位の skip に修正、push（T=09:16:53Z）、サマリ投稿、PR 本文更新 |

## 計画からの逸脱（PR 本文に書く）
- `class WC_Paidy` だけは 1 行の `phpcs:ignore` を置けない（宣言の行の `{` の後は OpeningBraceSameLine、docblock との間は ClassComment.Missing になる）ため、そのスニッフのコードだけの `phpcs:disable` / `phpcs:enable` で宣言を挟んだ。同じファイルに同じコードの `phpcs:ignore` は無い
- ルールの中の `<exclude-pattern type="relative">` は PHPCS が絶対パスと照合する（`File::addMessage()` のコメントどおり）ので、フレームワークの除外は絶対パスの指定にした（計画のフォールバックどおり）
- uninstall の改名は、paidy-wc と同じプロセスでアンインストールしたときの「Cannot redeclare wc_paidy_delete_plugin()」を直す（コミット 42bdfa8 の「behaviour change なし」は厳密ではない。PR 本文に書く。changelog は bump で判断）
- `docs/review-baseline.md` の WPCS の許容項目（既存の phpcs エラー）を消し、`docs/testing.md` の PHPCS の節（除外・CI の挙動）を実際に合わせた（計画に無かったドキュメント）
