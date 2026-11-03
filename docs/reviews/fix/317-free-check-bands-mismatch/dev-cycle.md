# dev-cycle 状態: fix/317-free-check-bands-mismatch
- タスク: issue #317 無料診断で、プレビューに出ていない帯が次の診断で送られることがある（milestone 3.0.2、backlog 2026-10-28 R3-1）
- 開始: 2026-11-03
- モード: auto-commit（確認ゲートなし）、同時依頼
- PR: 未作成
- 現在のステップ: 4（push と PR 作成）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## 計画で決めたこと（2026-11-03 承認）
- `show( response, withBands )` の中で `setBands()` する。`run()` は送った `bands`、`remove()` は `false` を渡す（issue の案と同じ効果。応答を表示する経路はみな帯を揃える）
- テストは e2e（Playwright の route のモック）で、新しいファイル `tests/e2e/admin-security-scan.spec.ts` に置く。既存の `admin-settings.spec.ts` は main の時点で lint-js のエラーが 11 件あり、一般設定を実際の REST で保存するテストと性質が違うため
- POST / DELETE は必ずモックで返し、wc.artws.info には送らない。PHP は変えない

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-03 | 0 | wp-env を起動（止まっていた）。既存の e2e 1 本で Playwright が動くことを確認 |
| 2026-11-03 | 1 | 計画承認 |
| 2026-11-03 | 2 | main の build がコミット済みの出力と一致することを確認。修正前の build で e2e の競合テストが落ちる（チェック on のまま、2 回目の POST が `bands: true`）ことを確認。修正後は e2e 2 本 green（×3 回でも green）、lint-js green、PHPUnit 1088 件 green。コミット f39ad62（fix）、fcdf4a7（test） |
| 2026-11-03 | 3 | review-loop R1: APPROVE（Critical/High/Medium なし）。Low 2 件 + 範囲外 2 件は backlog |
