# dev-cycle 状態: fix/224-delivery-meta-html-encoding
- タスク: Issue #224 — クラシックチェックアウトの配達日・配送時間帯・出荷日を HTML エンコードせずに保存し、出力時にエスケープする
- 開始: 2026-10-08
- PR: #230 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/230
- 現在のステップ: 5→6(G1 修正を push 済み、CI 待ち → G2 の依頼)
- Copilot: 依頼 1 回 / 未収束(G1 で新規 3 件)
- Codex: 依頼 1 回 / 未収束(G1 で新規 1 件)

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-08 19:10 | 0 | 起動チェック(main クリーン、品質チェック = composer lint → composer test、wp-env は不要) |
| 2026-10-08 19:30 | 1 | 計画承認(保存時エンコード除去 + 読み取り時に旧データ復元 + HTML 出力で esc_html + 両経路の PHPUnit) |
| 2026-10-08 20:10 | 2 | 実装コミット 1 件。PHPUnit 264 件 green、新規 10 件は修正前コードで 7 件 fail を確認。lint は main と同じ既存 2 件のみ |
| 2026-10-08 20:40 | 3 | review-loop R1: High 1 / Medium 2 / Low 1 を修正(973138d)、対象外 1 件を backlog。ミューテーション 2 件 CAUGHT。PHPUnit 266 件 green |
| 2026-10-08 21:00 | 3 | review-loop R2: APPROVE(R1 4 件すべて解消、新規 Critical/High なし)。新規 Low 2 件(コメント・テスト)を d5e757c で修正 |
| 2026-10-08 19:43 | 4 | upstream へ push(HEAD 24ed242、T=2026-10-08T10:43:30Z)、PR #230 作成 |
| 2026-10-08 19:50 | 5-6 | CI green。Codex(`@codex review`)・Copilot に同時依頼、両 bot が 24ed242 に応答 |
| 2026-10-08 20:30 | 7 | G1: Codex 1 / Copilot 3。復号の無条件適用(G1-1/G1-2)はユーザー判断で復号を削除(eabf0ec)。docs 2 件は状態ファイル更新で対応。確認ゲート通過 |

## 実装中の判明事項
- plain-text 分岐も末尾の `wp_kses_post()` が `&` を `&amp;` にしていた(保存形式と無関係)。計画では「plain-text 分岐は変更なし」としていたが、完了条件 2 を満たすため plain-text 分岐は `$output` をそのまま出力するよう変更(text/plain。理由付き `phpcs:ignore`、WooCommerce 本体の plain テンプレートと同じ扱い)。最初に試した `wp_strip_all_tags()` は `trim()` でブロック前後の空行を消し、`<` 以降を切るため review-loop R1 で不採用(R1-1 / R1-2)
- 計画の「旧データ(2.9.16 以前のクラシック注文)を読み取り時に復号する」は、G1 で Codex・Copilot が「実体文字列をそのまま含む正当な値も書き換わる」と指摘。`&` 等を含みうる「午前中」の表示名は 2.9.17 で初出荷のため、復号対象の旧データは実運用にほぼ存在しないと判断し、ユーザー承認のうえ復号を削除(G1-1 / G1-2)
- WooCommerce Store API(`CheckoutSchema::sanitize_additional_fields()`)は送信値を `wp_kses( $v, [] )` で処理した後に enum 照合するため、`&` を含む選択肢はブロックチェックアウトでは WooCommerce 自身が 400 で拒否する(`'` `"` は通る)。本プラグインの範囲外。両経路一致テストは `PM's "late"` で行う。要フォローアップ(別 issue 候補)
