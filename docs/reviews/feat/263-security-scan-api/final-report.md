# dev-cycle 最終報告: feat/263-security-scan-api

## 開発内容
- タスク: issue #263 S2 — REST `artws-sec/v1`（POST /scan・レポート生成・GET /scan/{token}・DELETE /site）。`plugins/artws-security-hub`（wc.artws.info、JP4WC の配布物には入らない）
- PR: #281 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/281
- 承認された計画（2026-10-23）: サイトごとのトークン（SHA-256 だけ保存）、応答は封筒 `{schema_version, report, site_token?}`、レート制限はサイト 1時間1回・IP 1時間20回、保存期間 180 日、レポートは ja / en、V-01・V-03 はサーバーが判定、照合用インデックスを S1 の取り込みで作る
- コミット:

| sha | メッセージ |
|---|---|
| 594312d | feat(security-feed): define the scan API responses in the contract |
| aced2b0 | feat(artws-security-hub): add the free security check REST API |
| 562dbe6 | fix(artws-security-hub): address the R1 review of the scan API |
| 9c7ead7 | docs(artws-security-hub): describe the decoupled vulnerability index |
| 3abfb36 | fix(artws-security-hub): address the R2 review of the scan API |
| 58478bc | docs: record review-loop R2 result |
| b6012a7 | fix(artws-security-hub): fail closed and keep the site usable on errors |
| a22c7ce | fix(artws-security-hub): keep the index and scans consistent on failures |
| fb82f8d | docs: record dev-cycle gate round 1 |
| b2f89e9 | fix(artws-security-hub): report read failures and keep deletes retryable |
| c87c3be | docs: record dev-cycle gate round 2 |
| 47de581 | fix(artws-security-hub): drop the scan data on uninstall |

- 設計ドキュメント（issue #263・契約 README）からの逸脱（計画承認時にユーザーと合意）:
  - 応答を封筒にした
  - IP の制限を 20 回にした
  - `reregister` を Schema に追加した
  - V-01 / V-03 はサーバーが判定する
  - S1 の取り込みにインデックスの書き込みを足した
  - site_url のスキームを同一性から外した
- 要判断として残したもの:
  - backlog R1-X1: `reregister` は認証を見ないので、URL を知る第三者がそのサイトの診断履歴の削除・トークンの失効をできる。issue の仕様が許容している挙動のまま実装した

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 6、設計 1、Low 7 | 6（＋ R1-L1 のテスト） | 7（R1-X1、R1-L2〜L7。R1-L3 は後に G1-8 で解消） |
| R2 | APPROVE。新規 Medium 2、Low 3 | 3（R2-1・R2-2・R2-4） | 2（R2-3、R2-L1） |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 6（レビュー 2 本） | 6 | 0 | 未収束 |
| G1 | Codex | 2 | 2 | 0 | 未収束 |
| G2 | Copilot | 3 ＋ 本文 2 | 3 | 2 | 未収束 |
| G2 | Codex | 1 | 1 | 0 | 未収束 |
| G3 | Copilot | スレッド 0 ＋ 本文 1 | 1 | 0 | 上限（3 回目でも本文に新規指摘あり） |
| G3 | Codex | 1 | 1 | 0 | 上限（3 回目でも新規指摘あり） |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 / G1-4 | Copilot / Codex | Medium | レート制限のクエリ失敗で制限が外れる → 500 で拒否 | b6012a7 | r4357091621 / r4357145163 |
| G1-2 | Copilot | Medium | 503 が認証より先に返る → 401 を先に | b6012a7 | r4357091664 |
| G1-3 / G1-5 | Codex / Copilot | Medium | 保存失敗でトークンが使えなくなる → 登録の取り消し・前のトークンに戻す | b6012a7 | r4357145147 / r4357166549 |
| G1-6 | Copilot | Medium | 同じ版のインデックスを消してから書き直す | a22c7ce（G2-2 で一意な版に置き換え） | r4357166588 |
| G1-7 | Copilot | Medium | 読めないインデックス行を黙って飛ばす → 503 | a22c7ce | r4357166630 |
| G1-8 | Copilot | Medium | サイトの無い診断が残る → サイトがあるときだけ保存、cleanup | a22c7ce | r4357166656 |
| G2-1 | Codex（＋ Copilot 本文） | Medium | 保存データの読み出しエラーが 404 / 401 になる → 500 | b2f89e9 | r4357242598 |
| G2-2 | Copilot | Medium | 同じ秒の取り込みでインデックスが遅れたまま最新扱い → 版を一意に | b2f89e9 | r4357263547 |
| G2-4 | Copilot | Medium | DELETE /site の途中失敗で再試行できない → 診断→サイト→診断の順 | b2f89e9 | r4357263627 |
| G3-1 | Codex | Medium | 削除してもデータが残る → uninstall.php | 47de581 | r4357345393 |
| G3-2 | Copilot（本文） | Medium | index_version の切り替え失敗を見ていない | 47de581 | （本文のみ） |

スレッドは `https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/281#discussion_<id>`。

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G2-3 | Copilot | Low | 再登録と旧トークンの診断の競合。同じサイトの要求はサイト単位の枠（1時間1回）を原子的に取り合うので既定では起きない（backlog G2-3） | r4357263599 |
| G2-5 | Copilot（本文） | Low | JSON の `{}` を配列として受け付ける。保存は配列に直るので実害なし（backlog R1-L2） | （本文のみ） |

## 品質ゲート
- CI（47de581）: Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39921527500 green / artws-security-hub https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39921527488 green
- 品質チェック: server `composer lint` green、PHPUnit 422 件 green。ルートの `composer test-local` 881 件 green（契約ファイルの一覧を更新したため）。ルートの `composer lint` は main に既存のエラーで失敗する（この PR が触れていないファイル）
- 各修正について、修正を外すと対応するテストが落ちることをミューテーションで確かめた

## 次にできること（人間の判断）
- **R1-X1（要判断）**: 再登録を所有確認なしで受け付ける仕様のままでよいか。塞ぐなら所有確認（サーバーが site_url 側の JP4WC にチャレンジを問い合わせる等）・二段階の再登録・再登録だけ厳しい制限のいずれかで、#260 の作りにも影響する
- 保留分の修正: `/dev-cycle fix G2-3 G2-5` または `/fix-copilot-review 281`
- 両 bot とも 3 回目でも新規指摘があったので「上限」。時間差で届く指摘が無いか、数時間後に `/fix-copilot-review 281` で確かめることを推奨
- マージ（GitHub 上で人間が行う）→ マージ後は `/post-merge`
- 反映（wc.artws.info）: ZIP を置き換えた後に取り込みを1回走らせる（それまで POST /scan は 503）。Authorization ヘッダーの受け渡し・匿名 REST の除外・CDN の背後なら `ARTWS_SEC_CLIENT_IP_HEADER`。プライバシーポリシー・利用規約は #260 のリリース前に必要
