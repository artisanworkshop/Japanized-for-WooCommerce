# dev-cycle 状態: feat/296-security-hub-sites-list
- CI: あり（artws-security-hub.yml）
- 記録: docs/reviews/feat/296-security-hub-sites-list/
- タスク: issue #296 artws-security-hub 管理画面に集計・プラグイン上位 30・登録一覧・CSV（auto-commit）
- 開始: 2026-11-13
- PR: #332 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/332
- 現在のステップ: 6（G3 依頼・待ち、Copilot のみ）
- Copilot: 依頼 2 回 / 未収束
- Codex: 依頼 1 回 / 収束（G1 で新規指摘なし）

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
| 2026-11-13 18:06 | 4 | upstream に push（2d6c81f、T=2026-11-13T09:06:28Z）、PR #332 作成 |
| 2026-11-13 18:17 | 7 | G1: CI green、Codex 0 件（収束）、Copilot 2 件（G1-1 修正 7aca69d、G1-2 本文のみ・保留） |
| 2026-11-13 18:30 | 7 | G2: CI green、Copilot 本文のみ 1 件（G2-1 ページ送りの %#% のエンコード → 誤検知。テストを強化） |
