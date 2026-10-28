# dev-cycle 最終報告: feat/xmlrpc-guard

## 開発内容
- タスク: XML-RPC の利用状況の記録と、許可メソッド付きの「XML-RPC を無効化」（既定オフ、3.0.0 同梱）。issue #293
- PR: #294 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294
- 計画: `xmlrpc_methods` で許可外のメソッドを 405 に差し替え（メソッド単位、multicall も 1 件ずつ）、許可リストが空なら `xmlrpc_enabled` false と X-Pingback/RSD の削除、Jetpack が引き受けた要求は触らない。記録は常時、メソッドごとの info / 結果ごとの count / 成功ログインのクライアント。A-04 は設定と XML-RPC のログイン保護で判定。画面に利用状況の表とトグル・許可リスト
- コミット: 3e98514 / f496294（実装）、362e0c9（R1）、ec1bacf（R2）、8db3e7e（screenshot-8）、eaba549（G1）、ほか docs 記録
- 設計からの逸脱: 拒否はメソッド単位（issue は要求単位）、IP は保存しない、A-04 は XML-RPC のログイン保護で判定、`system.*` は拒否できない（PR 本文に記載）

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | High 1・Medium 2・Low 6・対象外 1 | 3 + Low 4 | 3 |
| R2 | 新規 Medium 1・Low 4（APPROVE） | 1 + Low 2 | 2 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 4 | 1 | 3（誤検知 1） | 未収束 |
| G1 | Codex | 1（P1） | 1 | 0 | 未収束 |
| G2 | Copilot | 0（Needs a closer look、No open issues） | — | — | 収束 |
| G2 | Codex | 0 | — | — | 収束 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-2 | Copilot | Medium | 405 の文言に同梱 .mo の補完 | eaba549 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294#discussion_r4398435940 |
| G1-4 | Codex | Medium | 成功ログインのクライアントを独立した行に（RMW の競合） | eaba549 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294#discussion_r4398459684 |

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Copilot | Low | 登録済みメソッドだけを記録する緩い上限。backlog | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294#discussion_r4398435888 |
| G1-3 | Copilot | — | 誤検知（head の JSON に全件ある） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294#discussion_r4398435987 |
| G1-5 | Copilot | Low | ログインの成否はリクエスト単位でしか分からない。backlog | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/294#discussion_r4398477426 |

## 品質ゲート
- CI: 5d0bbf2 の PR CI green（2 workflow）。品質チェック: `composer test-local` 982 件 green、`composer lint` は main と同じ件数、変更ファイル phpcs 0、`wp-scripts lint-js` 0、`npx wp-scripts build` OK

## 次にできること（人間の判断）
- Copilot の総評どおり、Jetpack を WordPress.com に接続したサイト（wc4jp-pro.work など）で、設定オンのまま Jetpack の同期・統計・パブリサイズが動き続けることを確かめる
- 保留分の修正: `/dev-cycle fix G1-1 G1-5` または `/fix-copilot-review 294`
- マージ（GitHub 上で人間が行う）→ `/post-merge 294` → `/release-bump add 290 292 294`（#288 に changelog 行）→ #288 のマージ → タグ 3.0.0（指示があってから）
