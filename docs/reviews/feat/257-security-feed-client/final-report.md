# dev-cycle 最終報告: feat/257-security-feed-client

## 開発内容
- タスク: issue #257 セキュリティ診断 Phase 1 — 脆弱性フィードの取得・ローカル照合（V-01）・管理画面通知
- PR: #276 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276
- 承認された計画の要約:
  - wc.artws.info の `/feed/v1/` を日次で取得する。gz のバイト列の sha256 を検証し、`inflate_add()` で分割展開して、展開後の JSON を `uploads/jp4wc-security/` に保存する。
  - 照合はストリームで読み、インストール済みの slug だけを decode する。
  - 照合結果は autoload なしのオプションに保存し、`manage_woocommerce` のユーザーに閉じられる通知で知らせる。
  - 設定 `wc4jp-security-scan` は既定で有効。無効にすると cron とデータを消す。
  - readme の External Services に追記する。`@since 3.0.0`。
- 設計ドキュメントからの逸脱（計画承認時に合意済み）:
  - gzdecode の代わりに分割展開する
  - `generated_at` が 3 日を超えたデータも stale として扱う
  - 再照合は単発 cron で行う
  - 無効にしたらデータも消す
  - readme にはプライバシーポリシーのみ載せる
- ゲートで足した動作:
  - 展開後のファイルの sha256 を state に持ち、一致しないファイルは取り直す（G1-2）
  - リダイレクトに従わない（G1-3）
  - データが取れないまま数日たったら通知する（G2-1）
  - 無効化時に cron を外し、`init` で予約し直す（G2-6、G3-1）
  - 読めないファイルがあるときは前回の照合結果を残す（G3-2）

### コミット
| sha | メッセージ |
|---|---|
| 2b16c84 | feat: match installed software against the vulnerability feed files |
| fa5b5ab | feat: download the vulnerability feed daily and notify about findings |
| 50f13a9 | feat: add the vulnerability check setting |
| 7098309 | docs: translate the vulnerability check and list its feed as an external service |
| e1cc948 | fix: keep the security check off when it is turned off during a sync |
| 103f652 | test: cover turning the check off during a sync that downloads nothing |
| c707506 | fix: harden the feed sync against overlapping runs, uninstall and broken records |
| f9ef0de | fix: skip records whose fix information is malformed |
| 4a531f8 | fix: show a closed security notice again when a record it showed changes |
| 84eaa6e | fix: skip records whose CVSS score is missing or out of range |
| 2d4ecef | fix: say when no vulnerability data could be downloaded for days |
| 29788dd | fix: reschedule the feed sync on any request and keep findings when a file is missing |

ほかに、記録用の `docs:` コミットがある。

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | High 1・Medium 2・Low 10・対象外 2 | 3（e1cc948） | 12 |
| R2 | R1 の指摘はすべて解消（ミューテーションで実測）・新規は Low 4 | 1（103f652） | 3 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 7 | 7 | 0 | 未収束 |
| G1 | Codex | — | — | — | 未確認（20:21 UTC から Running のまま応答なし。ユーザー判断で G2 へ） |
| G2 | Copilot | 4 | 2 | 2 | 未収束 |
| G2 | Codex | 2 | 1 | 1 | 未収束 |
| G3 | Copilot | 1 | 1 | 0 | 上限 |
| G3 | Codex | 2 | 2 | 0 | 上限 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 | Copilot | Medium | 設定の行が無いのを「有効」と読み、アンインストール中の同期がデータを残す | c707506 | [r4353108498](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353108498) |
| G1-2 | Copilot | Medium | 同期が重なると世代の混ざったファイルが残る | c707506 | [r4353108561](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353108561) |
| G1-3 | Copilot | Medium | リダイレクトに従う | c707506 | [r4353108614](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353108614) |
| G1-4 | Copilot | Medium | 閉じた通知の指紋が表示内容の変化を反映しない | 4a531f8 | [r4353108668](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353108668) |
| G1-5 | Copilot | Medium | 壊れたレコードのリストが取得時の検査を通る | c707506 | [r4353160796](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353160796) |
| G1-6 | Copilot | Medium | DB の確認と書き込みの間の TOCTOU | c707506 | [r4353160855](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353160855) |
| G1-7 | Copilot | Medium | patched の型が不正だと「修正版なし」と誤表示する | f9ef0de | [r4353160924](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353160924) |
| G2-1 | Codex | Medium | 初回の取得が失敗し続けても何も表示されない | 2d4ecef | [r4353870321](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353870321) |
| G2-6 | Copilot（本文） | Medium | 無効化しても cron が残る | 2d4ecef | （レビュー本文） |
| G2-7 | Copilot（本文） | Medium | CVSS の score が不正なレコードを受け入れる | 84eaa6e | （レビュー本文） |
| G3-1 / G3-3 | Codex / Copilot | Medium | 再有効化後、管理画面を開くまで同期が予約されない | 29788dd | [r4354003696](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4354003696) / [r4354014754](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4354014754) |
| G3-2 | Codex | Medium | 読めないファイルがあると、その種類の照合結果を落とす | 29788dd | [r4354003707](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4354003707) |

### 修正しなかった指摘（理由を返信済み）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G2-2 | Codex | Low | rename の途中失敗でも各ファイルは完全な世代。次の同期で取り直す | [r4353870334](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353870334)（未解決） |
| G2-3 | Copilot | Low | 取得時に全体を検査済み。途中までの結果を捨てると隠れる脆弱性が増える | [r4353884491](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353884491)（Copilot が G3 で解決扱い） |
| G2-4 | Copilot | Low | 行が手で消された場合の、2 クエリ間の窓に限られる | [r4353884536](https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/276#discussion_r4353884536)（Copilot が G3 で解決扱い） |
| G2-5 | Copilot（本文） | Low | ライセンス文の変更で、閉じた警告を出し直さない | （レビュー本文） |

## 品質ゲート
- CI: 最終 head は下記の CI 待ちで確認（各ラウンドの依頼の前にも green を確認済み）
- 品質チェック: `composer test-local` で 652 件 green。新規・変更したファイルの lint は 0 件（`composer lint` の総数は main と同じで、既存のエラーのみ）。テストファイルも `phpcs --standard=WordPress` で 0 件
- ブラウザでの確認は未実施（このリポジトリの wp-env が起動していなかったため）

## 次にできること（人間の判断）
- 保留分の修正: `/dev-cycle fix G2-2` など、または `/fix-copilot-review 276`
- Codex の G1 は未確認。ただし G2・G3 で同じ範囲をレビューして応答しているので、再確認の必要は低い
- リリースの前に必要なこと:
  - S1（#262）を wc.artws.info にデプロイする（manifest は現在 404）
  - `.wordpress-org/screenshot-1.png` と `== Screenshots ==` の説明文を更新する（一般設定に「セキュリティ」パネルが増えた）
  - 新しい文字列を translate.wordpress.org で翻訳する（backlog R1-L7）
- サーバー側の検討（backlog R1-X1）: 毎回の生成で `generated_at` が変わり、gz の sha256 も毎日変わる。そのため、全サイトが毎日 3 ファイルを取得している
- マージ（GitHub 上で人間が行う）→ マージ後は `/post-merge`
