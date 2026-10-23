# dev-cycle 状態: feat/263-security-scan-api
- タスク: issue #263 S2 — REST `artws-sec/v1`（POST /scan・レポート生成・GET /scan/{token}・DELETE /site）（plugins/artws-security-hub）
- 開始: 2026-10-23
- オプション: auto-commit
- PR: 未作成
- 現在のステップ: 2（実装中）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-23 | 1 | 計画承認（応答は封筒 `scan-response`、IP 制限 1時間20回、保存期間 180 日、レポートは ja / en） |
| 2026-10-23 | 2 | 実装（契約・サーバー）。server lint green / PHPUnit 396 件 green、ミューテーション 3 種（401 で枠消費・キーの持ち越し・再登録で削除しない）をテストが検出 |
