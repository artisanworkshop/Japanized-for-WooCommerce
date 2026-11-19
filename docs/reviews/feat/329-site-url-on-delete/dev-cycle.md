# dev-cycle 状態: feat/329-site-url-on-delete
- CI: あり
- 記録: docs/reviews/feat/329-site-url-on-delete/
- タスク: #329 artws-security-hub: DELETE /site と GET /scan でも site_url を発行先と照合する（クライアントも同じ PR で site_url を送る。auto-commit）
- 開始: 2026-11-19
- PR: #338 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/338
- 現在のステップ: 8（完了。最終報告済み）
- Copilot: 依頼 2 回 / 収束（G2 で新規指摘なし。G1 の本文 2 件は保留）
- Codex: 依頼 1 回 / 収束（G1 で新規指摘なし）

## 計画の要点（2026-11-19 承認）
- ユーザーの決定: (1) サーバーとクライアントを 1 本の PR にし、3.2.0 のタグの前にサーバー 0.7.0 をデプロイする。(2) URL 置換でドメインを移した本番の DELETE も 401 になる（旧登録は保存期間で消える）ことは許容する
- DELETE /site は JSON 本文 `{schema_version: 1, site_url: home_url()}`（新スキーマ `site-delete-request.schema.json`）。本文なし（3.1.x 以前）は今のとおり受け付ける。不正な本文は 400、別のサイトは 401
- GET /scan/{token} は任意のクエリ `site_url`（JP4WC は GET を呼ばない）
- 法務ページは変更不要と判断（URL は第4条の項目、POST /scan と同じ照合の使い方。consent_version 据え置き）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-11-19 | 1 | 計画承認 |
| 2026-11-19 | 2 | ブランチ作成（ccca09b から） |
| 2026-11-19 | 2 | 実装コミット 4 件（c53f94e 契約、0fc2693 サーバー 0.7.0、a5c6847 クライアント、f732edb docs）。品質チェック green（ルート lint・PHPUnit 1140、サーバー lint・PHPUnit 494）。新しいテストは変更前のコードで落ちることを確認 |
| 2026-11-19 | 3 | review-loop R1（Medium 5 件修正: 72941f1・7a12cbc・73c2815、Low 1 件 backlog）→ R2 APPROVE（R2-1 を b44ae60 で修正、Low 2 件 backlog） |
| 2026-11-19 | 4 | push（upstream、a5d42d8、T=2026-11-19T03:31:55Z）、PR #338 作成（milestone Version 3.2.0） |
| 2026-11-19 | 6-7 | G1: CI green（2 ワークフロー）。Codex 0 件 = 収束。Copilot 本文のみ 2 件（G1-1 デプロイ前の承認、G1-2 readme の例外）→ どちらも保留。修正なし |
| 2026-11-19 | 6-7 | G2（40dd011、T=2026-11-19T03:42:37Z）: CI green。Copilot 0 件（本文は G1-1 の言い直し）= 収束 |
| 2026-11-19 | 8 | 最終報告（final-report.md） |
