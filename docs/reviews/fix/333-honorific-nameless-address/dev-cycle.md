# dev-cycle 状態: fix/333-honorific-nameless-address
- CI: あり
- 記録: docs/reviews/fix/333-honorific-nameless-address/
- タスク: #333 敬称 (様) が ON のとき、名前のない日本の住所（メールのフッターのストア住所など）に「様」を付けない（3.1.1）
- 開始: 2026-11-17
- オプション: auto-commit
- PR: 未作成
- 現在のステップ: 4(push・PR 作成)
- Copilot: 依頼 0 回
- Codex: 依頼 0 回

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-17 | 1 | 計画承認(private ヘルパー `has_name()`、テスト 3 種、changelog は 3.1.1 の bump PR) |
| 2026-11-17 | 2 | 実装コミット 915983e。PHPUnit 1136 件 green、composer lint green、テストファイルの phpcs は既存行のみ。修正を戻すと新テスト 5 件(名前なし 3・ストア住所 2)が落ちることを確認。開発サイト(8890、WC 11.2.1、`pre_option_*` で上書き・DB は不変)で修正前はフッターの最後に「様」、修正後は無し、名前あり・姓だけの住所は「山田 太郎様」「山田 様」のまま |
| 2026-11-17 | 3 | review-loop R1 APPROVE(Critical/High/Medium 0、独立サブエージェントの敵対的レビューとマージ)。対象外 R1-X1(wcpdf-jp-v1 が「名前が空でも様」に依存、Medium)と Low 3 件 → backlog |
