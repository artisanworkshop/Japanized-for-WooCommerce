# dev-cycle 状態: feat/263-token-scoped-registration
- タスク: S2 仕様変更: 登録をトークン単位にする（backlog R1-X1 案D。reregister を廃止し site_url を一意にしない）
- 開始: 2026-10-23
- PR: 未作成
- 現在のステップ: 3（review-loop）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束
- 確認ゲート: あり（auto-commit の指定なし）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-23 | 1 | 計画承認（案D: 登録ごとのトークン、reregister 廃止、枠は登録単位、sites.site_key の一意制約を外す DB v2、#260/#256 の本文を PR 作成時に更新） |
| 2026-10-23 | 2 | ブランチ作成（main 682ecfa から） |
| 2026-10-23 | 2 | 実装コミット bb88d00。server lint green / PHPUnit 418 件 green、ルート security_feed 114 件 green。ミューテーション 5 件すべて CAUGHT（枠のキーを URL に戻す・URL 不一致を通す・DROP INDEX なし・一意制約が残っても版を記録・IP 制限なし） |
