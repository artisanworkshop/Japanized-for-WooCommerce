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
| 2026-10-07 | R1-X1 | Medium | WC `CheckoutFieldsAdmin` / `WC_Meta_Box_Order_Data::save` | 管理画面で注文を保存すると、ブロック注文の `_wc_other/jp4wc/delivery-time` が現在の選択肢だけの select として送られ、保存済みの値が今の選択肢に無い（時間帯の削除、「午前中」の OFF・表示名変更）と先頭の選択肢で上書きされる。既存の挙動（#221 の R1）。PR #222 の G1-1（Codex）が同じ現象を指摘し、固定値化は保留（改名は先頭＝改名後の午前中で意味が保たれ、OFF は固定値でも防げない） | 未起票（ユーザーに確認） |
| 2026-10-07 | R1-X2 | Medium | jp4wc-pro `includes/class-jp4wc-pro-yamato-exporter.php` | ヤマト B2 出力の時間帯対応表に「午前中」が無く、Undefined array key の警告と時間帯コード空欄になる。`jp4wc_get_delivery_time_morning_label()` と一致する値を `0812` に対応させ `isset` で守る（別リポジトリ。#221 の R1。PR #222 の G2-1 で Codex も指摘） | artisanworkshop/jp4wc-pro#8 で起票済み |
| 2026-10-07 | R1-X3 | Low | class-jp4wc.php | ブロック用フィールドの登録（init 優先度 0）が textdomain の読み込み（init 優先度 1）より前。言語パックが無いサイトでは既存のラベルもブロック側だけ英語になる | 未起票 |
| 2026-10-07 | R1-L1 | Low | includes/jp4wc-common-functions.php | 「午前中」の表示名が登録済みの時間帯の値（例 `08:00-12:00`）と同じだと選択肢が重複する | 未起票 |
| 2026-10-07 | R1-L2 | Low | includes/admin/class-jp4wc-settings-api.php | 表示名の `<` は `sanitize_text_field` で `&lt;` になり、ブロックではそのまま見える | 未起票 |
| 2026-10-07 | R1-L3 | Low | includes/admin/class-jp4wc-settings-api.php | 表示名 `'0'` は保存されるが、チェックアウトでは既定値に置き換わり設定画面と食い違う（PR #222 の G2-2 で Copilot も指摘し、G3 でも再指摘。保留） | 未起票 |
| 2026-10-07 | R1-L4 | Low | tests/Unit/test-jp4wc-delivery-time-morning.php | Store API の拒否テストがステータス 400 しか見ていない | 未起票 |
| 2026-10-07 | R1-L5 | Low | src/js/jp4wc/admin/settings/components/ShipmentSettings.js | 表示名を空にして配送設定タブ以外で保存すると、PHP の実行時フォールバック（言語パック・ロケール依存）に戻る | PR #222 の G1-3（Copilot）で対応済み（補完を全タブ共通の saveSettings に移動） |
| 2026-10-07 | R2-L1 | Low | src/js/jp4wc/admin/settings/components/ShipmentSettings.js | 「午前中」の既定値は操作する管理者のユーザーロケールの JS 翻訳で決まる（プロフィール言語が英語なら「Morning」が保存される。入力欄に値が出るので気づける） | 未起票 |
| 2026-10-08 | G3-1 | Low | includes/class-jp4wc-delivery.php:350,406 | クラシックの保存は時間帯を `esc_attr( htmlspecialchars() )` で変換して保存し、ブロックは変換しない。表示名に `&` `'` `"` を含めるとクラシックの注文だけ `&amp;` などで保存され、テキストメールにそのまま出る。保存時の変換をやめるには、変換済みを前提に値をそのまま出す表示（650 行目など）を先に出力時エスケープへ直す必要がある（既存コード。PR #222 の G3 で Copilot が指摘。保留） | 未起票 |
| 2026-10-08 | G3-2 | Low | src/js/jp4wc/admin/settings/components/Settings.js（`withMorningLabel()`） | タグだけの表示名（例 `<b></b>`）は JS の空判定を通り、`sanitize_text_field()` で空になって保存される。空の間はチェックアウトが PHP の実行時フォールバック（言語パック次第で「Morning」）になり、次の保存で「午前中」が補完される（PR #222 の G3 で Copilot が指摘。R1-L3 と同種。保留） | 未起票 |
