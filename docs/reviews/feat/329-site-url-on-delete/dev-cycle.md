# dev-cycle 状態: feat/329-site-url-on-delete
- CI: あり
- 記録: docs/reviews/feat/329-site-url-on-delete/
- タスク: #329 artws-security-hub: DELETE /site と GET /scan でも site_url を発行先と照合する（クライアントも同じ PR で site_url を送る。auto-commit）
- 開始: 2026-11-19
- PR: 未作成
- 現在のステップ: 2（実装）
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

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
