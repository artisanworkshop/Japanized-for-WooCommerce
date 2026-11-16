# dev-cycle 最終報告: fix/333-honorific-nameless-address

## 開発内容
- タスク: #333 敬称 (様) が ON のとき、名前のない日本の住所(メールのフッターのストア住所など)に「様」を付けない(3.1.1)
- PR: #336 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/336(milestone Version 3.1.1)
- 計画: `address_replacements()` の敬称の条件に、姓か名が空でない(trim した値)ことを足す。判定は private の `has_name()`(`@since 3.1.1`)。姓だけの住所は `山田 様` のまま。テストは名前なし・名前あり・ストア住所の 3 種。changelog は 3.1.1 の bump PR
- コミット:
  - 915983e fix: skip the honorific for Japanese addresses without a name
  - 333fbf2 docs: record dev-cycle state and review-loop R1 of #333
  - (この報告と G1 の記録のコミット)
- 設計ドキュメントからの逸脱: なし

## review-loop(PR 前)
| ラウンド | 指摘(C/H/M) | 修正 | backlog |
|---|---|---|---|
| R1 | 0 | 0 | 4(対象外 R1-X1 Medium、Low R1-L1〜L3) |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 0(本文 2 本は既出の R1-X1 = G1-1) | 0 | 1 | 収束 |

### 修正した指摘
なし

### 修正しなかった指摘
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Copilot | —(リリースの調整) | R1-X1 と同じ。wcpdf-jp-v1 側の変更で、この PR では直せない。3.1.1 のタグの前に対応 | なし(本文のみ。サマリコメントで回答) |

## 品質ゲート
- CI: green(333fbf2)
- 品質チェック: `composer test-local` 1136 件 green、`composer lint` green、テストファイルの phpcs は既存行のみ
- 修正を戻すと新テスト 5 件(名前なし 3・ストア住所 2)が落ちる
- 開発サイト(WC 11.2.1): 修正前 `…道玄坂2-1, 様` → 修正後 `…道玄坂2-1`。名前あり・姓だけは `山田 太郎様` / `山田 様` のまま

## 次にできること(人間の判断)
- **wcpdf-jp-v1 に issue を立てる(R1-X1)**: 3.1.1 のタグの前に、名前のない住所に御中を付ける対応(`JP4WC_VERSION` で切り替え、docs/features.md:243 と display-check の期待値)
- マージ(GitHub 上で人間が行う)→ マージ後は `/post-merge`
- 3.1.1 の bump PR(`/release-bump start 3.1.1`)で changelog の行を足す。ブロックのチェックアウトのローカル受取の住所(`pickup_address`)にも「様」が付かなくなることに触れてよい
- Low の保留(R1-L1〜L3)を直すなら `/dev-cycle fix` ではなく別の PR で(G 指摘ではないため)
