# dev-cycle 状態: fix/224-delivery-meta-html-encoding
- タスク: Issue #224 — クラシックチェックアウトの配達日・配送時間帯・出荷日を HTML エンコードせずに保存し、出力時にエスケープする
- 開始: 2026-10-08
- PR: 未作成
- 現在のステップ: 3(review-loop)
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-08 19:10 | 0 | 起動チェック(main クリーン、品質チェック = composer lint → composer test、wp-env は不要) |
| 2026-10-08 19:30 | 1 | 計画承認(保存時エンコード除去 + 読み取り時に旧データ復元 + HTML 出力で esc_html + 両経路の PHPUnit) |
| 2026-10-08 20:10 | 2 | 実装コミット 1 件。PHPUnit 264 件 green、新規 10 件は修正前コードで 7 件 fail を確認。lint は main と同じ既存 2 件のみ |
| 2026-10-08 20:40 | 3 | review-loop R1: High 1 / Medium 2 / Low 1 を修正(973138d)、対象外 1 件を backlog。ミューテーション 2 件 CAUGHT。PHPUnit 266 件 green |

## 実装中の判明事項
- plain-text 分岐も末尾の `wp_kses_post()` が `&` を `&amp;` にしていた(保存形式と無関係)。計画では「plain-text 分岐は変更なし」としていたが、完了条件 2 を満たすため `wp_strip_all_tags()` に変更(WPCS はエスケープ関数と見なさないので理由付き `phpcs:ignore`。WooCommerce 本体の plain テンプレートと同じ扱い)
- WooCommerce Store API(`CheckoutSchema::sanitize_additional_fields()`)は送信値を `wp_kses( $v, [] )` で処理した後に enum 照合するため、`&` を含む選択肢はブロックチェックアウトでは WooCommerce 自身が 400 で拒否する(`'` `"` は通る)。本プラグインの範囲外。両経路一致テストは `PM's "late"` で行う。要フォローアップ(別 issue 候補)
