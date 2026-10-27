# dev-cycle 状態: feat/security-hardening-options
- タスク: 3.0.0 同梱のセキュリティ対策 2 つ（REST のユーザー一覧を未ログインに返さない / 管理画面のファイル編集を禁止、既定オフ）+ #290 の仕上げ（@since 3.0.0、Jetpack の接続判定、screenshot-8）。issue #291
- 開始: 2026-10-27
- PR: 未作成
- 現在のステップ: 3（review-loop）
- Copilot: 依頼 0 回
- Codex: 依頼 0 回

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-27 23:50 | 1 | 計画承認（既定オフ、3.0.0 同梱、issue 起票） |
| 2026-10-28 00:40 | 2 | issue #291、実装。PHPUnit 945 件 green、composer lint は main と同じ件数、wp-env の tests サイトで /wp/v2/users が未ログインに 404・DISALLOW_FILE_EDIT が定義されることを確認、screenshot-8 撮り直し |
