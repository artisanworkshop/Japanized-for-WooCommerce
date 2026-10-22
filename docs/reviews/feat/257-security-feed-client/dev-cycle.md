# dev-cycle 状態: feat/257-security-feed-client
- タスク: issue #257 セキュリティ診断 Phase 1 — 脆弱性フィード取得・ローカル照合（V-01）・管理画面通知
- 開始: 2026-10-23
- オプション: auto-commit
- PR: 未作成
- 現在のステップ: 2（実装中）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## 計画で決めたこと
- `@since 3.0.0`（ユーザー決定）。readme の規約類 URL はプライバシーポリシーのみ（ユーザー決定。利用規約は Phase 3 前に新設）
- 実フィードは数十 MB なので、gz は `inflate_add()` で分割展開して展開後 JSON をファイルに保存し、照合はストリームで読んでインストール済み slug だけ decode する（issue の `gzdecode` からの逸脱）
- `generated_at` が 3 日より古いデータも stale 扱い（backlog R1-L8）
- 無効化で cron 解除に加えて状態・照合結果・保存ディレクトリも消す。`install()` と `admin_init` で設定と cron を揃える
- 再照合は単発 cron（`jp4wc_security_rescan`）で非同期に行う。専用ロックは入れない（WP-Cron のロックと rename で足りる）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-23 | 1 | 計画承認 |
