# レビューベースライン（許容済み指摘リスト）
レビュー時、ここに記載された項目は指摘しないこと。

## 形式
- [カテゴリ] 対象範囲 — 許容理由

## 許容項目
<!-- 例:
- [WPCS] Yoda condition 非適用 — チーム規約で不採用
- [DB] $wpdb 直接クエリは Repository クラス内に限り許容 — 抽象化済みのため
-->
- [WPCS] `class-wc-paidy.php` / `woocommerce-for-japan.php` / `uninstall.php` の既存 phpcs エラー（`wc_jp4wc_logging` など非プレフィックスのフック名等） — main に既存。本ループの差分範囲外
- [DB] `WC_Paidy_Apply_Receiver::prune_expired_state_tokens()` と `uninstall.php` の `$wpdb` 直接 `LIKE` クエリ — オプション名をプレフィックスで列挙するコア API が無いため（PR #200 で合意済み）
- [Security] `paidy-receiver/v1/receive` が nonce/capability なしの公開 REST エンドポイントであること — 外部サーバー（paidy-app）からのコールバックを state トークンまたは site_hash 署名で認証する設計
