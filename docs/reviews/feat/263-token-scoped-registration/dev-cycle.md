# dev-cycle 状態: feat/263-token-scoped-registration
- タスク: S2 仕様変更: 登録をトークン単位にする（backlog R1-X1 案D。reregister を廃止し site_url を一意にしない）
- 開始: 2026-10-23
- PR: #282 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282
- 現在のステップ: 8（完了）
- Copilot: 依頼 3 回 / 上限（G3 は具体的な指摘なし）
- Codex: 依頼 3 回 / 収束（G2・G3 とも指摘なし）
- 確認ゲート: あり（auto-commit の指定なし）

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-23 | 1 | 計画承認（案D: 登録ごとのトークン、reregister 廃止、枠は登録単位、sites.site_key の一意制約を外す DB v2、#260/#256 の本文を PR 作成時に更新） |
| 2026-10-23 | 2 | ブランチ作成（main 682ecfa から） |
| 2026-10-23 | 2 | 実装コミット bb88d00。server lint green / PHPUnit 418 件 green、ルート security_feed 114 件 green。ミューテーション 5 件すべて CAUGHT（枠のキーを URL に戻す・URL 不一致を通す・DROP INDEX なし・一意制約が残っても版を記録・IP 制限なし） |
| 2026-10-23 | 3 | review-loop R1: Medium 3（R1-1 SHOW INDEX の失敗を一意制約なしと読む / R1-2 契約の版規則の例外 / R1-3 同じ URL の DELETE のテスト）を修正 608ea25、Low 3・範囲外 2 を backlog。R2: 3 件解消（ミューテーション CAUGHT）、新規 Low 2 を backlog → APPROVE |
| 2026-10-23 | 4 | upstream へ push（f7d69a5、T=2026-10-23T13:27:13Z）、PR #282 作成。#260 の認証節・受け入れ条件と #256 の決定事項 1 行を案D に書き換え |
| 2026-10-24 | 6-7 | G1: CI green（f7d69a5）。Copilot 3 件（2 本目のレビューを含む）、Codex 1 件を修正（2470128、ミューテーション 3 件 CAUGHT）。確認ゲートで commit & push を承認、返信・Resolve・サマリを投稿。保留なし |
| 2026-10-24 | 6-7 | G2: CI green（896e307）。Copilot の本文 1 件（G2-1 枠の窓の引き継ぎ）を Low で保留。Codex は Running のまま 2 回待っても応答なし → ユーザー判断で未確認のまま G3 へ |
| 2026-10-24 | 6-8 | G3: CI green（eaa8082）。Codex は指摘なしで収束（G2 も 22:16Z に指摘なしで届いていた）。Copilot は本文に具体的でない記述のみで、上限で終了。最終報告 |
