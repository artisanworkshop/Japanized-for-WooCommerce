# dev-cycle 最終報告: feat/262-security-feed-import

## 開発内容
- タスク: issue #262 S1 — Wordfence Intelligence V3 の取り込みと `/feed/v1/` の配信（`plugins/artws-security-hub/` 0.2.0）
- PR: #275 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275（upstream ブランチ、auto-commit）
- 承認された計画の要約:
  - 日次の WP-Cron で V3 production フィードをストリーム保存し、1 件ずつ読む
  - 契約 README どおりに変換する
  - 手書きの検査器（テストで Schema 本体と突き合わせる）でレコード単位に除外する
  - 閾値（除外 2% 超 / 種別 0 件 / 前回の 90% 未満 / 著作権者なし）で失敗にし、前回のファイルを残して stale にする
  - 一時名から rename して公開する（gz → manifest の順）
  - 毎時の watchdog（48 時間）、INSERT IGNORE のロック、管理画面（EOL・状態・手動実行・件数減少の許可）を持つ
  - テストは WordPress のテストライブラリで動かす（ローカルはルートの環境を借りる。CI は MySQL）
- 計画に無かった追加。どれも stale を正しく出すため、または bot の指摘への対応:
  - 失敗時の再試行（1 時間後、3 回まで）
  - 無効化時の stale（実行中の取り込みにも効く）
  - ロック中に EOL を保存したときの 1 分後の書き直し
  - 構文の壊れたレコードは実行全体の失敗にする
  - 著作権情報の別版を `<holder>-2` のキーで残す
  - mbstring を使わない
  - レコードを JSON 文字列で集める（4 万件でピーク約 100 MB。丸ごと decode すると 715 MB）
- 利用規約: 2026-01-26 版を Claude in Chrome で確認した（README に記録）。再配布の条件は、著作権表示と license を含めること。表記の文言の指定は無い
- コミット:

| sha | メッセージ |
|---|---|
| 295d0bc | test: run the artws-security-hub tests on the WordPress test library |
| 087f51b | feat: import the Wordfence Intelligence feed and publish /feed/v1/ |
| 6b83cec | docs: note how the artws-security-hub tests run locally |
| a179b89 | fix: read the feed state under the lock and retry failed imports |
| 3f1143a | fix: mark the feed stale on deactivation even while the lock is held |
| a25d223 | docs: record the review-loop rounds for the feed import |
| c5f5943 | fix: fail on broken JSON, keep copyright variants and drop mbstring |
| acbe953 | fix: check for deactivation after the manifest is written |
| 4e96236 | docs: record dev-cycle gate round 1 |
| e00c9cf | docs: record dev-cycle gate round 2 |
| 0fc48bb | fix: count vulnerabilities with an empty software list as left out |

- 設計ドキュメントからの逸脱: なし。要判断として backlog に残したもの:
  - gz のファイル名に世代を入れる案（R1-L1 / G3-1）
  - cron 自体が止まった場合をクライアント（#257）の `generated_at` で補う案（R1-L8）

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 6 / Low 8 / 対象外 1 | 6（a179b89） | 9 |
| R2 | R1-4 一部未解消 / 新規 Low 3 | 1（3f1143a） | 3 |
| R3 | APPROVE / 新規 Low 1 | 0 | 1（後に G1-7 で修正） |

独立サブエージェントを R1・R2 で併用した。修正したガードは、壊すとテストが落ちることを mutate-check で計 15 箇所確かめた。

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 7 | 5 | 2（G1-2, G1-9） | 未収束 |
| G1 | Codex | 2 | 2 | 0 | 未収束 |
| G2 | Copilot | 2（本文） | 0 | 2（G2-1 誤検知, G2-2 Low） | 未収束 |
| G2 | Codex | 0 | — | — | **収束** |
| G3 | Copilot | 3 | 1 | 2（G3-1, G3-2） | **上限**（3 回目でも新規指摘あり） |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 | Copilot | Medium | 構文の壊れたレコードを実行全体の失敗にする（UTF-8 でないレコードは除外） | c5f5943 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348635874 |
| G1-3 | Copilot | Medium | `mb_check_encoding()` をやめ、PCRE で UTF-8 を判定する | c5f5943 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348636047 |
| G1-4 | Codex | Medium | 同上 | c5f5943 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348690416 |
| G1-5 | Codex | Medium | 著作権情報の別版を `<holder>-2` などのキーで残す | c5f5943 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348690434 |
| G1-6 | Copilot | Medium | 除外の経路の mbstring 依存（G1-3 で解消） | c5f5943 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348695490 |
| G1-7 | Copilot | Medium | 無効化フラグを manifest を書いた後に読み直す | acbe953 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348695593 |
| G1-8 | Copilot | Medium | 区切りの食い違いは不正な JSON として失敗する（テストを追加） | c5f5943 / acbe953 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348695644 |
| G3-3 | Copilot（本文） | Medium | `software: []` を除外として数える | 0fc48bb | （スレッドなし） |

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-2 | Copilot | Low | ロックの持ち主の確認。実行時間はダウンロード 300 秒と `set_time_limit(900)` で上限があり、2 時間を超えて生き残らない（backlog R1-L2） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348635977 |
| G1-9 | Copilot | —（誤検知） | `update_option_{$option}` と `add_option_{$option}` は DB に書いた後に発火する（option.php 1019 行・1176 行） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4348695697 |
| G2-1 | Copilot（本文） | —（誤検知） | `wp_raise_memory_limit()` は独自の context でも `WP_MAX_MEMORY_LIMIT` まで上げる | （スレッドなし） |
| G2-2 | Copilot（本文） | Low | 同じ秒に 2 回公開することは無い（ロックで直列化、毎回フィード全体を読む） | （スレッドなし） |
| G3-1 | Copilot | Low | rename が途中で失敗した場合の部分的な差し替え。起きても契約上クライアントは前回のデータを使う。根本対応はファイル名の設計判断（backlog R1-L1、要判断） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4349019484 |
| G3-2 | Copilot | Low | state と manifest の間のミリ秒の fatal（backlog G3-2） | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/275#discussion_r4349019555 |

## 品質ゲート
- CI: 0fc48bb で green。`artws-security-hub`（https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39789046642）と `Testing`（https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39789046607。CLAUDE.md を変えたので走った）。全ラウンドで green
- 品質チェック: `composer lint` 違反 0、`composer test-local` 255 件 green

## 次にできること（人間の判断）
- 保留分の修正: `/dev-cycle fix G1-2 G3-1 G3-2`、または `/fix-copilot-review 275`
  - G3-1（gz のファイル名に世代を入れる）は issue のファイル名を変える設計判断なので、するかどうかを決める
- Copilot は上限で終えた（3 回目でも新規指摘があった）。新しく直したのは G3-3 の 1 箇所だけなので、再依頼するかは任意
- マージ（GitHub 上で人間が行う）→ マージ後は `/post-merge`
- デプロイ（PR 本文の「デプロイ時の確認」）:
  - wc.artws.info に API キーの定数を設定する
  - `/feed/v1/` の Alias を設定する
  - ZIP を作って反映し、手動で取り込む
  - 実データで除外の割合と `copyrights` の中身（版の数）を確かめる
- #257（クライアント）への申し送り:
  - `generated_at` の古さでも stale と見なす（R1-L8）
  - 数字だけの slug は `json_decode( …, true )` で整数のキーになるので、文字列として照合する
