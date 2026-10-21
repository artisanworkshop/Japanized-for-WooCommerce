# dev-cycle 状態: fix/268-load-files-at-plugins-loaded
- タスク: issue #268 — plugins_loaded の後にファイルを読む箇所を無くす(#266 の残り)
- 開始: 2026-10-21
- PR: 未作成
- 現在のステップ: 3(review-loop)
- Copilot: 依頼 0 回
- Codex: 依頼 0 回

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-21 16:00 | 1 | 計画承認。issue の表に無い Paidy のブロック対応(init 5、毎リクエスト)を対象に追加。決済 4 種のブロック対応のガードが `use` の短い名前で常に false(今は early binding で動いているだけ)で、plugins_loaded で読むと壊れるため、完全修飾名への修正を含める |
| 2026-10-21 16:20 | 2 | 実装。PHPUnit 443 件 green、新規テスト 12 件。lint は main と同じ既存の 94 errors / 12 warnings(変更ファイルの指摘数も main と同じ) |
