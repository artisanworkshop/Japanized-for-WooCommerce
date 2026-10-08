# dev-cycle 状態: fix/223-paidy-payment-id-hyphen
- タスク: Paidy 決済 ID に「-」を含む支払いが形式チェックで弾かれて「支払い済み」にならない不具合の修正（issue #223）
- 開始: 2026-10-08
- ベースブランチ: main
- PR: 未作成
- 現在のステップ: 4（push・PR 作成）
- Copilot: 依頼 0 回
- Codex: 依頼 0 回

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-08 | 0 | issue #223 を確認。`paidy_get_payment_data()` の `^pay_[A-Za-z0-9_]+$` が「-」入り ID を拒否し、thank-you / Webhook の両経路で `payment_complete()` に到達しないことをコードで確認 |
| 2026-10-08 | 1 | 計画承認（正規表現を `[A-Za-z0-9_-]` へ緩和、回帰テスト追加、readme は触らない） |
| 2026-10-08 | 2 | 実装コミット 46a4cd4。変異確認（旧正規表現で 4 件失敗）、フル 253 件 OK |
| 2026-10-08 | 3 | review-loop R1 APPROVE（Low 3、差分内のため修正 → 829d580、backlog 2）、R2 APPROVE（全解消・新規なし）。フル 254 件 OK |
