# dev-cycle 最終報告: feat/263-token-scoped-registration

## 開発内容
- **タスク**: S2 の仕様変更「登録をトークン単位にする」（backlog R1-X1 の案D。2026-10-23 のユーザー判断）
- **PR**: #282 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282
- **承認された計画の要約**:
  - `Authorization` なしの `POST /scan` は、同じ URL でも常に新しい登録にする。トークン付きの要求は、そのトークンの登録で、かつ発行時と同じサイトの `site_url` のときだけ受け付ける。
  - `reregister` を廃止する。サイトごとの枠を登録ごとにし、IP の枠は残す。
  - DB を版 2 にし、`site_key` の一意制約を外す。
  - 契約 README・Schema・サーバー README を更新する。プラグインは 0.4.0。
  - #260（認証節・受け入れ条件）と #256（決定事項 1 行）の本文を書き換える。
- **コミット**:
  - bb88d00 feat(artws-security-hub): register sites per token, not per URL
  - 608ea25 fix(artws-security-hub): do not record the table upgrade on a failed index read
  - 95e0937 docs: record review round 1 of the token-scoped registration
  - f7d69a5 docs: record review round 2 of the token-scoped registration
  - 2470128 fix(artws-security-hub): compare table names without case in the upgrade
  - 896e307 docs: record dev-cycle gate round 1
  - eaa8082 docs: record dev-cycle gate round 2
  - （この報告）docs: record dev-cycle final report
- **設計ドキュメント・前回の決定からの逸脱**:
  - #263 の「再登録で既存データを削除」とその受け入れ条件を撤回した。
  - レート制限を site_url 単位から登録単位にした。
  - 方式B は「登録ごとのトークン」になった。
  - トークンを失った登録のデータは API では消せず、180 日で消える（プライバシーポリシーに書く）。
  - 契約の「互換を壊す変更は v2」の例外として、REST v1 は使う JP4WC を出すまで直接変える、と明記した。

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 3 / Low 3 / 範囲外 2 | 3 | 5 |
| R2 | 新規 Low 2（R1 は全件解消、APPROVE） | 0 | 2（G1 で対応） |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 3（2 本目のレビューを含む） | 3 | 0 | 未収束 |
| G1 | Codex | 1 | 1 | 0 | 未収束 |
| G2 | Copilot | 1（本文のみ） | 0 | 1 | 未収束 |
| G2 | Codex | 0（待ちの終了後に届いた） | 0 | 0 | — |
| G3 | Copilot | 0（本文に具体的でない記述） | 0 | 1 | 上限 |
| G3 | Codex | 0 | 0 | 0 | 収束 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 | Copilot | Medium | 移行の最後の確認で表を探せないとき版を記録しない分岐を、テストで固定 | 2470128 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#discussion_r4360078497 |
| G1-2 | Copilot | Medium | 索引が読めないとき `DROP INDEX` を送らないことを assert | 2470128 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#discussion_r4360078588 |
| G1-3 | Codex | Medium | `lower_case_table_names=1` で表名を大文字小文字を無視して比べる | 2470128 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#discussion_r4360097570 |
| G1-4 | Copilot | Medium | G1-2 と同じ | 2470128 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#discussion_r4360112216 |

### 修正しなかった指摘（未解決のスレッドは無い。本文だけの指摘）
| ID | bot | 重大度 | 理由 | レビュー |
|---|---|---|---|---|
| G2-1 | Copilot | Low | 移行直後の 1 時間だけ、各登録が最大 1 回余分に診断できる。0.3.0 は未反映なので本番に v1 の窓は無い（backlog） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#pullrequestreview-5634140787 |
| G3-1 | Copilot | — | 具体的な指摘が無い（「No open issues」）。近い既知の項目は G2-1・R1-L3 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/282#pullrequestreview-5635066493 |

## 品質ゲート
- **CI**（eaa8082）: artws-security-hub https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/40032720681 green / Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/40032720655 green
- **品質チェック**: server `composer lint` green / PHPUnit 421 件 green。ルートの security_feed 114 件 green
- **ミューテーション**: 実装・R1・G1 で計 11 件、すべて CAUGHT

## 次にできること（人間の判断）
- **保留分の修正**: `/dev-cycle fix G2-1`（または backlog のまま）
- **Copilot の再確認**: 上限で終えたので、必要なら後日 `/fix-copilot-review 282` で、遅れて届いた指摘が無いか確かめる。Codex は収束済み
- **マージ**: GitHub 上で人間が行う → マージ後は `/post-merge`
- **wc.artws.info への反映**:
  - 0.4.0 の ZIP を置き換える
  - 取り込みを 1 回走らせる（インデックス）
  - `wp option get artws_sec_db_version` が 2 になったことを確かめる
  - Authorization ヘッダー・送信元 IP を確かめる
- **公開前**: プライバシーポリシーに「トークンを失った登録の診断は 180 日で消える」と、問い合わせでの削除の扱いを書く
