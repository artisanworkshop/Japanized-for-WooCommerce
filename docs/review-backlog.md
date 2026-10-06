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
| 2026-10-06 | R1-L2 | Low | includes/class-jp4wc-cod-fee-handler.php | 常駐型ランタイム（FrankenPHP worker 等）での static の持ち越しは、チェックアウトリクエストの開始・終了で初期化・復元する実装で防いでいるが、実機では未確認。ルートの途中で `\Error` が抜けた場合は復元されない（R3-L1） | 未起票 |
| 2026-10-06 | R3-L1 | Low | includes/class-jp4wc-cod-fee-handler.php | チェックアウトルートの途中で `\Error` / TypeError が抜けると `rest_request_after_callbacks` が走らず、捕捉した支払い方法が static に残る。php-fpm では影響なし（常駐型ランタイムと、dispatch を Throwable ごと握りつぶす呼び出し側だけ）。リクエスト先頭での初期化で防げる | 未起票 |
| 2026-10-06 | R3-L2 | Low | tests/Unit/test-jp4wc-cod-fee-gateway-validation.php / test-jp4wc-cod-fee-store-api.php | 入れ子のチェックアウトリクエストを固定するテストが無い。既存注文の支払いのテストが status を見ていない | 未起票 |
| 2026-10-06 | R3-L3 | Low | includes/class-jp4wc-cod-fee-handler.php / class-jp4wc-cod-fee.php | 手数料を持つゲートウェイの一覧（`cod` / `cod2`）がガードと計算側で二重管理 | 未起票 |
| 2026-10-06 | R2-X1 | High | includes/admin/class-jp4wc-settings-api.php / class-jp4wc-cod-fee.php | 代引きの決済設定画面（WooCommerce > 設定 > 決済 > 代金引換）で手数料を設定した店舗が、Japanized for WooCommerce の設定画面で保存すると、存在しなかった `wc4jp-extra_charge_*` が `''` で作られて優先され、代引き手数料が請求されなくなる（設定画面は受け取った全キーを保存し直し、REST は未設定のキーを `''` で返す。wp-env で実測: 保存前 name=代引き手数料 / amount=550 / max=50000 → 保存後すべて空。どのタブの保存でも起きる）。元の値は `woocommerce_cod_settings` に残る。Pro 版 PR（jp4wc-pro #7）のレビュー中に確認。既存コード | 未起票（ユーザーに確認） |

