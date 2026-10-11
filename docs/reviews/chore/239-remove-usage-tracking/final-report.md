# dev-cycle 最終報告: chore/239-remove-usage-tracking

## 開発内容
- タスク: 利用状況トラッキングを新しい仕組みに作り直すため、現行の実装と、配送日・配送時間帯の通知メールの開発元宛て BCC を削除する(issue #239、2.9.17)
- PR: #240 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240(head は upstream の `chore/239-remove-usage-tracking`)
- 承認された計画の要約:
  - トラッキングのクラスと `jp4wc_tracker_*` フックを削除する
  - 更新時と uninstall で、WP-Cron のイベントと 2 つのオプションを片付ける
  - 設定キー・設定パネル・readme の External Services の項目を削除する
  - PHP バージョン通知の売上の条件を削除する
  - 通知メールの BCC を削除する
  - テストを先に書き、main で失敗することを確認する
- コミット:
  | sha | メッセージ |
  |---|---|
  | 757e670 | chore: remove the usage tracking feature (#239) |
  | a911418 | chore: send the missing-delivery notice only to the shop's address (#239) |
  | 1e91f19 | docs: record the review-loop round for the usage tracking removal |
  | 1edf554 | chore: also clear the tracking event that 2.6.10-2.6.19 scheduled (#239) |
  | 681124f | chore: drop the usage tracking panel from screenshot 1 (#239) |
  | dac0600 | docs: record dev-cycle gate round 1 |
  | 533a3bf | docs: record dev-cycle gate round 2 |
- 計画からの逸脱: G1 の指摘を受けて、計画に無かった 2 件を追加した。
  - 2.6.10〜2.6.19 の旧名イベント `wc4jp_tracker_send_event` の片付け
  - `.wordpress-org/screenshot-1.png` から追跡パネルを切り取る(計画では「変えないもの」に入れていた)

## review-loop(PR 前)
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Critical / High / Medium 0、Low 6、対象外 2 | 0(APPROVE) | 8 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 1 | 1 | 0 | 未収束 |
| G1 | Copilot | 3 | 3 | 0 | 未収束 |
| G2 | Codex | 0 | 0 | 0 | 収束 |
| G2 | Copilot | 2 | 0 | 2 | 未収束 |
| G3 | Copilot | 0 | 0 | 0 | 収束 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 | Codex | Medium | 2.6.10〜2.6.19 の旧名イベント `wc4jp_tracker_send_event` が更新時に残る | 1edf554 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241378862 |
| G1-2 | Copilot | Medium | 同上 | 1edf554 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241381683 |
| G1-3 | Copilot | Medium | uninstall でも旧名を消す | 1edf554 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241381696 |
| G1-4 | Copilot | Medium | screenshot-1.png に削除したパネルが写っている | 681124f | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241381711 |

### 修正しなかった指摘(PR 上で未解決のまま残してある)
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G2-1 | Copilot | Low | テストファイル冒頭の「weekly when enabled」が不正確(毎週の送信は `'1'` / `'yes'` の食い違いで動いていなかった)。コメントのみで動作・判定に影響しない(R1-L4) | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241405974 |
| G2-2 | Copilot | Low | 「An update runs on an admin request」が不正確(`check_version()` はどのリクエストでも走る)。同上(R1-L4) | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240#discussion_r4241405993 |

## 品質ゲート
- CI: https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/38135970082 green(533a3bf。PHP 8.3 / WP 6.7 / WC 10.2.2、PHP 8.4 / WP 6.9 / WC 10.6.2、PHP 8.5 / WP latest / WC latest)
- 品質チェック: PHPUnit 363 件 OK(main は 358 件)、lint 94 errors / 12 warnings(main は 97 / 30。すべて既存の指摘)、`npx wp-scripts build` の差分は `assets/js/build/admin/settings.*` だけ

## 次にできること(人間の判断)
- 保留分の修正: `/dev-cycle fix G2-1 G2-2`(テストのコメント 2 か所の書き換えだけ)または `/fix-copilot-review 240`
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`
- マージ後、jp4wc-pro#8 を含むリリースの条件が揃ったら、2.9.17 のタグを push する
