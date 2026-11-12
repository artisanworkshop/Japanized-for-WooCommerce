# dev-cycle 状態: feat/318-hide-user-names
- CI: あり
- 記録: docs/reviews/feat/318-hide-user-names/
- タスク: issue #318 ユーザー名の列挙対策を REST API のユーザー一覧以外にも広げる（auto-commit）
- 開始: 2026-11-12
- PR: #331 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/331
- 現在のステップ: 7（G2 依頼）
- Copilot: 依頼 1 回 / 未収束
- Codex: 依頼 1 回 / 収束（G1 で新規指摘なし）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-12 | 1 | 計画承認（A-06 を新設、別オプション `wc4jp-hide-user-names`、公開ページ・ログイン/リセット・REST Basic 認証・著者アーカイブも閉じる） |
| 2026-11-12 | 2 | 実装コミット 5 件（b29e7e5 設定と 8 経路、0d1e9c7 A-06 とサーバー 0.5.0、19d86ef 画面、0e88f26 翻訳 6 件、35ae157 readme と screenshot-8）。PHPUnit 1127 件 green、composer lint 0、テストの phpcs 0、lint-js 0、サーバー lint 0 / PHPUnit 424 件 green。tests サイトで `?author=1` 404・ログインのエラー同一（入力欄も残る）・未登録のリセットが checkemail=confirm・REST Basic が同じ 401 を確認。A-06 の Web 側の再チェックは tests サイトが自分の localhost:8891 に届かず unknown（判定は単体テスト） |
| 2026-11-12 | 3 | review-loop R3 で APPROVE。R1: High 1 / Medium 3 を 290978e で修正、Low 4 件は backlog。R2: R1-2 の残り（WPS Hide Login + `wc_reset_password`）と R2-1（テストの不備）を b660aaf で修正、R2-2 は backlog。R3: 新規なし |
| 2026-11-12 17:30 | 4 | upstream へ push（21a0610、T=2026-11-12T08:28:38Z）、PR #331 作成（milestone Version 3.1.0） |
| 2026-11-12 17:41 | 6 | G1 依頼（CI green 後、Codex は `@codex review`）。両 bot が応答 |
| 2026-11-12 17:44 | 7 | G1: Codex 0 件で収束。Copilot 2 件 → G1-1 修正（26270a8）、G1-2 は誤検知で保留 |
