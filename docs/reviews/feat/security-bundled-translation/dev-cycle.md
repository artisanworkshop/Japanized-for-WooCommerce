# dev-cycle 状態: feat/security-bundled-translation
- タスク: backlog R1-L7（#257）— 3.0.0 で増えた PHP の文字列 22 件を、WordPress.org の言語パックに入るまで同梱 .mo で補う（#259 の R1-L3 も同じ変更で解消）
- 開始: 2026-10-27
- PR: 未作成
- 現在のステップ: 4（push と PR 作成）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## 計画で決めたこと
- 方針はユーザー決定（2026-10-27）: #272 の同梱 .mo フォールバックを使う（translate.wordpress.org の翻訳を待たない）
- 実装はユーザーの指示で plan mode を経ずに先に行い、dev-cycle は Step 2 のコミットから再開した
- 22 件の内訳: `class-jp4wc-security-notice.php` 16 件（`_x()` 5 件・`_n()` 2 件を含む）、`class-jp4wc-security-api.php` 6 件（`_n()` 1 件）。同梱の ja .mo に全件の訳がある
- #272 の補完は `__()`（gettext）だけだったので、`_x()` / `_n()` に広げて `jp4wc_with_bundled_translation()` にまとめる。`_nx()` は使っていないので対象外
- 補完は文字列を作っている間だけ（通知の描画、REST の各ルートのコールバック、PHP 通知の文字列）。常時のフィルターにはしない（CLAUDE.md の #272 の方針）
- changelog の行は不要（セキュリティチェック自体が 3.0.0 の新機能で、PHP 通知の挙動は 2.9.20 から変わらない）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-27 | 1 | 方針をユーザーが決定（同梱 .mo のフォールバック）。plan mode は経ていない |
| 2026-10-27 | 2 | 実装。PHPUnit 887 件 green、`composer lint` は main と同数（93 errors / 12 warnings）。新しいテスト 6 件はクラス側の変更を戻すと 2 件、`_x()`/`_n()` のフィルターを外すと 3 件が失敗することを確認 |
| 2026-10-27 | 2 | コミット 9c954bf（fix）/ 595b377（docs） |
| 2026-10-27 | 3 | review-loop R1: Medium 2（R1-1 テスト不足、R1-2 更新中の混在で Fatal）/ Low 3 + 対象外 1（backlog）。修正 bd208ef / 5aad113 |
| 2026-10-27 | 3 | review-loop R2: **APPROVE**（R1 全解消、新規 Low 1 を backlog R2-L1）。PHPUnit 887 件 green、lint は main と同数 |
