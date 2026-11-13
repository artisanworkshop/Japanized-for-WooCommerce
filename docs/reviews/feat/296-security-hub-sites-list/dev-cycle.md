# dev-cycle 状態: feat/296-security-hub-sites-list
- CI: あり（artws-security-hub.yml）
- 記録: docs/reviews/feat/296-security-hub-sites-list/
- タスク: issue #296 artws-security-hub 管理画面に集計・プラグイン上位 30・登録一覧・CSV（auto-commit）
- 開始: 2026-11-13
- PR: 未作成
- 現在のステップ: 4（push・PR 作成）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束（`--request-codex` が必要）

## 決定（計画承認 2026-11-13）
- 版 0.6.0（0.5.0 は #318 A-06 が使用済み・未反映）
- 一覧に帯を出す同意の版 = 2026-11（規約 第4条第4項第4号・第5項・附則を 2026-11-13 に確認）
- 帯の「5 未満」は 0〜4。「帯を送らなかった」も両表の共有マスとして隠す判定に入れる
- 1 回に読む件数 200（issue は 500）
- 画面の文字列は英語、一覧は素の表 + paginate_links

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-13 | 1 | 計画承認 |
| 2026-11-13 | 2 | 実装コミット 2 件（6ce0104 feat、0b10a39 docs）。lint 0/0、PHPUnit 463 件 green。主要ロジックの変異テストで検出を確認 |
| 2026-11-13 | 3 | review-loop R3 APPROVE。R1: High 1（空の CSV で fatal）・Medium 3（帯の割り出し・孤立した診断のテスト・CSV インジェクションの入口）を修正（50bf73b）、Low 2 を backlog。R2: R1-2 の残りをユーザー判断（今の規則 + 同数の順序 + 説明を狭める）で修正（9407fff） |
