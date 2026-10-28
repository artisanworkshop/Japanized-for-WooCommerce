# dev-cycle 状態: feat/xmlrpc-guard
- タスク: XML-RPC の利用状況の記録と、許可メソッド付きの「XML-RPC を無効化」オプション（既定オフ、3.0.0 同梱）。issue #293
- 開始: 2026-10-28
- 引数: auto-commit
- PR: #294 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294
- 現在のステップ: 6（G2・CI 待ちと bot 依頼）
- Copilot: 依頼 1 回 / 未収束
- Codex: 依頼 1 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-28 16:20 | 1 | 計画承認（拒否はメソッド単位の差し替え、IP は保存しない、A-04 は許可ありならログイン保護の有無で判定） |
| 2026-10-28 17:40 | 2 | 実装コミット 2 件（3e98514, f496294）。PHPUnit 976 件 green、composer lint は main と同じ件数、lint-js 0。wp-env tests サイトで off/on/許可/multicall/`?for=jetpack`（WooCommerce 同梱の Jetpack 接続が引き受け、core メソッドは無い）、X-Pingback と RSD、利用状況の記録、ブラウザで「許可」→保存を確認。screenshot-8 撮り直し |
| 2026-10-28 19:40 | 3 | review-loop: R1 High 1・Medium 2（A-04 が xmlrpc.php を守らない保護で pass、記録の書き込み量、実経路のテスト）→ 修正 362e0c9。R2 APPROVE（新規 Medium 1・Low 2 も修正 ec1bacf）。backlog 5 件。PHPUnit 981 件 green |
| 2026-10-28 17:30 | 4 | screenshot-8 撮り直し（8db3e7e）、upstream へ push、PR #294 作成 |
| 2026-10-28 17:50 | 7 | G1: Copilot 4（修正 1・保留 3〔誤検知 1〕）、Codex 1（P1、修正）。eaba549。auto-commit で push、返信・Resolve・サマリ投稿 |
