# レビューバックログ（今回対応しない指摘）
| 追記日 | ID | 重大度 | 場所 | 内容 | 起票状況 |
|---|---|---|---|---|---|
| 2026-08-28 | R1-X1 | Medium | includes/gateways/paidy/class-wc-paidy-admin-wizard.php:441-462 | `wp_remote_post` が WP_Error のとき応答コード `''` で第2分岐にも入り、ログと `consume_state_token()` が二重実行（既存コード） | 2.9.16リリース準備で対応済み（WP_Error分岐でearly return） |
| 2026-08-28 | R1-X2 | Low | includes/gateways/paidy/class-wc-paidy-apply-receiver.php | `paidy_received_data` に復号済み API キーが平文保存（既存コード） | 2.9.16リリース準備で対応済み（secret_live_key/secret_test_keyを`[redacted]`に置換して保存） |
| 2026-08-28 | R1-X3 | Low | uninstall.php | `paidy_site_hash` を意図的に残す理由（再インストール後の署名再送）がコメント化されていない | 未起票 |
| 2026-08-28 | R1-L1 | Low | src/js/paidy/main-hooks/form-info.jsx:134 | `nonWizardUrl` フォールバックの `/wp-admin/` ハードコード重複 | 未起票 |
| 2026-08-28 | R1-L2 | Low | includes/gateways/paidy/class-wc-paidy-admin-wizard.php:427 | `plugin_version` が standalone paidy-wc では常に空 | 未起票 |
| 2026-08-28 | R1-L3 | Low | includes/gateways/paidy/class-wc-paidy-apply-receiver.php:236 | リプレイガード transient はオブジェクトキャッシュ eviction 時にすり抜け（冪等なので実害なし） | PR #211 の Codex 指摘で対応済み（add_option による原子的クレームに変更） |
| 2026-08-28 | R2-L1 | Low | includes/gateways/paidy/class-wc-paidy-apply-receiver.php:370 | 署名失敗 warning はリクエストごとに出るためログ膨張の余地（レート制限や debug レベル化を検討） | PR #211 の Codex 指摘で対応済み（許容幅ごとに1件へ抑制） |
| 2026-10-06 | R1-X1 | Low | includes/class-jp4wc-cod-fee-handler.php（`add_gateway_fee_for_wc_blocks()`） | `$data['action']` を `isset` なしで参照。未認証エンドポイントに `action` なしで送ると PHP warning（既存行。PR #215 対応ブランチの R1） | 未起票 |
| 2026-10-06 | R1-X2 | Low | includes/class-jp4wc-cod-fee.php（`get_gateway_fee_value()`） | 無料版・Pro のどこからも呼ばれていないメソッド。`WC()->session` のガードも無い（既存コード） | 未起票 |
| 2026-10-06 | R1-X3 | Low | Store API 全般 | `payment_method` を持たない並行リクエストが、1 行保存のセッションの後勝ちで古い `chosen_payment_method` を書き戻しうる。WooCommerce 自身の PUT と同じ性質。注文確定は POST の値で計算されるため金額は正しい | 未起票 |
| 2026-10-06 | R1-L1 | Low | tests/e2e/checkout-blocks.spec.ts | Checkout Block の E2E に代引き手数料の検証と、#215 の症状（切替後の表示不一致）の回帰テストが無い。遅いサーバーの再現が必要 | 未起票 |
| 2026-10-06 | R1-L2 | Low | includes/class-jp4wc-cod-fee-handler.php | 常駐型ランタイム（FrankenPHP worker 等）での static の持ち越しは、チェックアウトリクエストの開始・終了で初期化・復元する実装で防いでいるが、実機では未確認 | 未起票 |

