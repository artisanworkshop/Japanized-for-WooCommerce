# dev-cycle 最終報告: fix/320-blocks-support-wc-loaded-first

## 開発内容
- タスク: #320 WooCommerce を先に読む構成(WooCommerce をネットワーク有効化し、このプラグインをサイト単位で有効化したマルチサイト、PHPUnit)でも、ブロックチェックアウトの決済ブロック 4 種と配達日時・読み仮名欄を登録する
- PR: #327 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/327
- 計画の要約: `init()` → `hook_blocks_support()` が `did_action( 'woocommerce_blocks_loaded' )` なら `jp4wc_blocks_support()` を直接呼び、まだなら登録する。`jp4wc_blocks_support()` のクロージャ 6 本を名前付き static メソッド 3 本にして、2 回呼ばれても登録が増えないようにした(決済ブロックの option を読む時点は plugins_loaded → init 5)。テスト 6 件を追加し、CLAUDE.md の Boot Sequence・AGENTS.md・backlog を更新
- コミット
  - 96a331e fix: register block checkout support when WooCommerce loads first
  - 9d7e894 docs: describe the blocks support boot order for WooCommerce loaded first
  - 8c402b8 docs: record review-loop R1 for #320
  - 9266bd3 docs: record dev-cycle gate round 1
  - (このファイル) docs: record dev-cycle final report
- 設計ドキュメントからの逸脱: なし(名前付きメソッド化・option を読む時点の移動・AGENTS.md の 1 行は計画で了承済み)
- 補足: issue 本文の「両方をネットワーク有効化したときは有効化した順で決まる見込み」は誤り。`wp_get_active_network_plugins()` は名前順に並べるので、このプラグインが先に読まれ、修正前から動いていた(PR 本文に記載)

## review-loop(PR 前)
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Critical/High/Medium 0、Low 2 | 0 | 2(R1-L1・R1-L2) |

R1 で APPROVE(自分のレビューと独立サブエージェントの敵対的レビューをマージ)。

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 0 | 0 | 0 | 収束 |
| G1 | Codex | 0 | 0 | 0 | 収束 |

Codex は最初の依頼で `@codex review` の投稿を付け忘れて 15 分の TIMEOUT になり、同じ HEAD に投稿し直して約 2 分で応答を得た。

### 修正した指摘
なし

### 修正しなかった指摘(PR 上で未解決のまま残してある)
なし(review-loop の Low 2 件は `docs/review-backlog.md` と PR 本文の「今回は直さない指摘」)

## 品質ゲート
- CI: G1 の head 8c402b8 の Testing run https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/43008684417 green(記録用コミットの run も確認する)
- 品質チェック: `composer test-local` 1099 件 green、`composer lint` green、テスト 2 ファイルの `phpcs --standard=WordPress` green。修正を戻すと新テスト 3 件が落ちることを確認
- 実環境: 使い捨てのマルチサイト(WooCommerce ネットワーク有効化)で、修正前は決済ブロック 0 件・追加欄なし → 修正後は 4 種と 3 欄(レジストリと Checkout ブロックのページ)。単一サイト(開発サイト 8890)でも 4 種と 3 欄

## 次にできること(人間の判断)
- backlog の Low(R1-L1: option を読む時点の食い違い、R1-L2: AGENTS.md の追加行の言い回し)を直すかどうか
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`。3.1.0 で利用者に見える最初の修正なので、マージ後に `/release-bump start 3.1.0` → `add 327`
