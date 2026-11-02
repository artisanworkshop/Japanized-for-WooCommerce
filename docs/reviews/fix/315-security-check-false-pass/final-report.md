# dev-cycle 最終報告: fix/315-security-check-false-pass

## 開発内容
- タスク: issue #315 セキュリティチェックの誤った pass を塞ぐ(A-05 の `?rest_route=`、D-02 の圧縮した DB ダンプ。milestone Version 3.0.2)
- PR: #323 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/323
- 承認された計画の要約:
  - A-05: パーマリンク有効時は `?rest_route=/wp/v2/users` も probe キー `A-05-query` で確かめる。どちらかが一覧を返せば fail、両方遮断なら pass、それ以外は unknown。`X-WP-Total` が重複して配列で返っても警告を出さず、すべて 0 のときだけ pass
  - D-02: UpdraftPlus の `[-_.]db[0-9]*.gz`、`.sql` / `.tar` + bzip2・xz・zstd、`.tbz2` / `.txz` / `.wpress` を追加。kind はパターンの名前付きグループ `kind` から取る
- コミット:
  - 2123518 fix: stop A-05 and D-02 from passing what is still open (#315)
  - 21339e9 test: pin every extension of the rewritten D-02 pattern and more A-05 cases
  - 44a9b24 docs: record review-loop round 1 of #315
  - b316cb4 docs: record review-loop round 2 of #315
  - 8233953 docs: record dev-cycle gate round 1
  - (このファイルのコミット) docs: record dev-cycle final report
- 設計ドキュメントからの逸脱: なし。issue で「計画で決める」とした 3 点は計画でユーザーが決めた。計画で `.tar.zst` も足した

## review-loop(PR 前)
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 1 / Low 4 / 範囲外 3 | 1(R1-1: テストの抜け) | 7(2026-11-02 R1-L1〜L4・R1-X1〜X3) |
| R2 | 新規なし(R1-1 は mutate-check 4 件 CAUGHT) | — | — |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 1 | 0 | 1 | 未収束(2 本目のレビューで 1 件) |
| G2 | Copilot | 0 | 0 | 0 | 収束 |

### 修正した指摘
なし

### 修正しなかった指摘(PR 上で未解決のまま残してある)
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Copilot | High → 誤検知 | `add_query_arg()` は足す値をエンコードしない(`build_query()` → `_http_build_query( …, false )`)。`get_rest_url()` も同じ呼び方で `?rest_route=/wp/v2/users` を返し、新テストはその URL で通る | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/323#discussion_r4438079505 |

## 品質ゲート
- CI: Testing green(b316cb4: https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/41368090631、8233953 も green)
- 品質チェック: `composer test-local` 1088 件 OK、テストファイルの phpcs 0 件、`composer lint` は main と同数(93 errors / 12 warnings)

## 次にできること(人間の判断)
- G1-1 の保留を見直す場合: `/fix-copilot-review 323`
- backlog に回した Low(特に R1-L1: `?rest_route=` にトップページが 200 で返る構成での誤った fail、R1-X1: A-05 の文言に残る `/wp-json/wp/v2/users`)を issue にするか判断する
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`、続けて `/release-bump add 323` で #310 に changelog の行を足す
