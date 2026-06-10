# セキュリティ改善計画 — Japanized for WooCommerce

全体セキュリティ監査（2026-06-10 実施）の結果を基にした改善計画。
1 項目ずつ着手し、完了したらチェックボックスを更新する。

- 監査対象: プラグイン全 PHP / フロントエンド JS（`vendor/`・`node_modules/`・`*.min.*`・`tests/` を除く）
- 確定脆弱性（信頼度 8 以上）: **2 件**（いずれも HIGH / Paidy 決済バイパス）
- 要再確認（閾値未満・人手判断）: 5 件

---

## 優先度サマリー

| # | 区分 | 対象 | 深刻度 | 状態 |
|---|------|------|--------|------|
| 1 | 確定 | Paidy Webhook 未署名素通し | High | [x] 完了（2026-06-10） |
| 2 | 確定 | Paidy thank-you `transaction_id` バイパス | High | [x] 完了（2026-06-10） |
| 3 | 要確認 | Paidy apply-receiver の API キー上書き | Medium | [x] 完了（2026-06-10） |
| 4 | 要確認 | マルウェアスキャナの権限境界・情報開示 | Medium | [x] 完了（2026-06-10） |
| 5 | 要確認 | スキャン結果のベンダー宛メール送信 | Low | [x] 完了（2026-06-10・機能削除） |
| 6 | 要確認 | A8.net アフィリエイトタグの `<script>` 埋め込み | Low | [ ] 未対応 |
| 7 | 要確認 | `wc4jp_rated` AJAX の nonce 欠如 | Low | [ ] 未対応 |

> **共通方針**: #1 と #2 は「`payment_complete()` / 在庫減算の前に Paidy API で `order_ref`・`status`・金額を検証する」という共通の修正で一括対処できる。先にこの検証ヘルパーを用意してから両経路に適用すると効率的。

---

## #1【HIGH・確定】Paidy Webhook の未署名リクエスト素通し

- **ファイル**: `includes/gateways/paidy/class-wc-paidy-endpoint.php`
- **該当箇所**: `paidy_webhook_permission_check`（69-134 行）、`paidy_check_webhook`（213-221 行）
- **分類**: `missing_auth` / payment_bypass

### 問題

`POST /wp-json/paidy/v1/order` の権限コールバックは、`x-paidy-signature` ヘッダが**存在する場合のみ** HMAC 署名を検証する（74 行 `if ( ! empty( $signature ) )`）。ヘッダを省略し、かつ `paidy_webhook_allowed_ips` フィルタが未設定（デフォルト＝空配列）の場合は `return true` で素通しする（118-133 行）。その後、生 JSON ボディ（`order_ref` / `payment_id` / `status`）を信頼し、`status === 'authorize_success'` かつ注文が `pending`/`cancelled` のとき `wc_reduce_stock_levels()`（219 行）と `$order->payment_complete()`（221 行）を実行する。Paidy API への再確認は無し。

### 攻撃シナリオ

未認証の攻撃者が署名ヘッダなしで `{"order_ref":<推測した注文ID>,"payment_id":"pay_x","status":"authorize_success"}` を POST → 入金ゼロのまま注文が支払い済みになり在庫減・出荷フロー起動。注文 ID は連番で推測可能。

### 改善方針

- [x] ~~シークレットキーが設定されている場合は署名検証を**無条件で必須**にする（ヘッダ無し＝拒否）。~~ → **見送り（意図的判断）**。
  - 理由: 既存コメント・Paidy からの情報により、Paidy 本番 Webhook は `x-paidy-signature` を**付与しない**前提。署名を無条件必須にすると正規の Webhook を全件 403 で弾き、本番が停止する。代わりに「公式 IP 許可リスト（既定有効）＋サーバー間 API 検証」の二段防御で担保する。署名ヘッダが付与されている場合は従来どおり HMAC 検証する。
- [x] 署名なし・IP 許可リスト外の状態変更リクエストはデフォルトで拒否する（`return true` フォールスルーを廃止）。
  - 2026-06-10 対応: Paidy 提供の公式 IP 5 件を `WC_Paidy_Endpoint::PAIDY_WEBHOOK_IPS` 定数として追加し、`paidy_webhook_allowed_ips` フィルタのデフォルト値に採用。許可リストが既定で非空になり、Paidy 以外の IP からの未署名リクエストは 403 で拒否される。素通し（`return true`）はフィルタで明示的に空配列を返した場合のみ到達。
  - 登録 IP: `13.114.134.35` / `13.113.94.100` / `18.182.135.232` / `52.199.50.20` / `52.199.62.26`
- [x] 状態変更の直前に `paidy_get_payment_data($payment_id)` で Paidy API に問い合わせ、`order_ref`・`status`・金額（`$order->get_total()`）の一致を確認してから `payment_complete()` を呼ぶ（サーバー間検証）。
  - 2026-06-10 対応: `WC_Gateway_Paidy::paidy_verify_payment_for_order( $order, $transaction_id )` を新設。`paidy_get_payment_data()` で Paidy API に照会し、(1) `id` 一致、(2) `order.order_ref` = 注文ID、(3) `amount` = 注文合計、(4) `status` が `authorized`/`active`/`closed`（`paidy_verify_allowed_statuses` フィルタで調整可）の 4 点を検証。Webhook 経路（`paidy_check_webhook`）と thank-you 経路（`thankyou_completed`）の両方で `payment_complete()` の直前に呼び、不一致なら完了処理をスキップ（Webhook は 403）。
  - 併せて `paidy_get_payment_data()` を WP_Error 安全化（`is_wp_error` ガード＋`wp_remote_retrieve_body`）。

### 検証

- [ ] 署名ヘッダなしの POST が 401/403 で拒否されること（または金額・status 不一致時に注文が完了しないこと）。
- [ ] 正規の署名付き Webhook では従来どおり注文が完了すること。
- [ ] `composer test` / `composer lint` 通過。

> ⚠️ 互換性注意: 過去バージョン（< 2.9.13）の挙動復元として「未署名許可」が意図的に入れられた経緯がある（docblock / commit `c4efa27`）。修正前に Paidy 本番 Webhook が実際に署名を付与するかを確認すること。署名が来ない仕様なら、サーバー間再確認（API 問い合わせ）方式を主軸にする。

---

## #2【HIGH・確定】thank-you ページの `$_GET['transaction_id']` 決済バイパス

- **ファイル**: `includes/gateways/paidy/class-wc-gateway-paidy.php`
- **該当箇所**: `thankyou_completed`（735-751 行）
- **分類**: payment_bypass

### 問題

`process_payment()`（381 行付近）は Paidy API を呼ばず注文を `pending` のまま受領ページへ誘導する。`woocommerce_thankyou_paidy` で発火する `thankyou_completed()` は、注文が `pending`/`cancelled` かつ `transaction_id` 未設定のとき、`$_GET['transaction_id']` をサニタイズするだけで `payment_complete()` を実行し、**金額・認可状態・取引の真正性を検証しない**。同クラスに検証ヘルパー `paidy_get_payment_data()`（1068 行・返金処理では使用）が存在するのに、この経路では未使用。受領ページのアクセス制御は注文キーのみで、購入者は自分のキーを知っている。

### 攻撃シナリオ

購入者が Paidy 選択 → ウィジェットを閉じる/キャンセル（注文は `pending`）→ 自分の order-received URL に `&transaction_id=anything` を付けて手動アクセス → `wc_reduce_stock_levels()` と `payment_complete('anything')` が走り、未払いのまま出荷される。

### 改善方針

- [x] `payment_complete()` の前に `paidy_get_payment_data($transaction_id)` をサーバー側で呼ぶ。
- [x] 当該取引が当該注文（`order_ref` / `order_id`）に紐づくことを確認。
- [x] `status` が `authorized`/`active` であることを確認。
- [x] `amount === $order->get_total()`（通貨・金額一致）を確認。
- [x] いずれか不一致なら `payment_complete()` を呼ばず、注文を保留のままにしてログ＋通知。
  - 2026-06-10 対応: #1 と共通の `paidy_verify_payment_for_order()` を `thankyou_completed()` に組み込み済み。検証失敗時は `payment_complete()`・在庫減算を行わず early return（デバッグログ出力）。在庫減算を検証通過後に移動。

### 検証

- [ ] 偽の `transaction_id` を付けた受領 URL アクセスで注文が完了しないこと。
- [ ] 正規の Paidy 認可後リダイレクトでは従来どおり完了すること。
- [ ] `composer test` / `composer lint` 通過。

> 💡 #1 と同じ検証ロジックを共通プライベートメソッド（例: `verify_paidy_payment( $order, $transaction_id ): bool`）に切り出し、Webhook 経路と thank-you 経路の両方から呼ぶと重複を避けられる。

---

## #3【MEDIUM・要確認】Paidy apply-receiver による API キー上書き

- **ファイル**: `includes/gateways/paidy/class-wc-paidy-apply-receiver.php`
- **該当箇所**: `check_permissions`（53-97 行）、`handle_receive_data`（195-243 行）
- **分類**: missing_auth / improper_access_control
- **判定**: 信頼度 6（トークン入手が前提のため確定には至らず／人手で再評価）

### 問題

`paidy-receiver/v1/receive`（GET+POST）は未認証で、`paidy_status === 'approved'` のときリクエストボディ由来の API キー（live/test の public/secret）を `woocommerce_paidy_settings` に書き込み、本番決済認証情報を上書きする。唯一のゲートは 32 文字の一回限り `state` トークン（transient・7 日間有効）＋ `paidy_site_hash` 非空のみ。`paidy_status` 自体は AES 復号の対象外で生のまま（196 行）。

### 改善方針

- [x] `state` トークンの有効期間を短縮する。
  - 2026-06-10 対応: `class-wc-paidy-admin-wizard.php` の `set_transient` を `7 * DAY_IN_SECONDS` → `2 * DAY_IN_SECONDS` に変更。Paidy の審査が通常数時間〜1 営業日で完了する前提。2 日を超えるケースが判明した場合は戻す。
- [x] `application_id` + site_hash 由来の HMAC でペイロード全体を検証し、transient 存在チェックだけに依存しない。
  - **見送り（プロトコル上の制約）**: HMAC を含めるには中継サーバー（`paidy.artws.info`）側のプロトコル変更が必要。プラグイン側のみでは実装不可。
- [x] 復号後のキーが既知のマーカー等で妥当か検証し、偽の `approved` + ジャンクキーを拒否する。
  - 2026-06-10 対応: `paidy_status === 'approved'` の際、復号後の 4 キーフィールド（`public_live_key`/`secret_live_key`/`public_test_key`/`secret_test_key`）がすべて非空であることを検証するバリデーションを追加（`class-wc-paidy-apply-receiver.php`）。いずれか空の場合は 400 エラーで拒否し、既存クレデンシャルを上書きしない。
- [x] `state` トークンがリダイレクト/Referer に露出しないことを確認する。
  - コードレビュー済み: `state` トークンはウィザードから中継サーバーへの POST **ボディ** に含まれる（URL パラメータ不使用）。Referer ヘッダへの露出なし ✓

### 信頼モデル（整理）

- `site_hash`（16 文字ランダム文字列）は `paidy.artws.info` に POST 送信される。中継サーバーがこれを AES 鍵として API キーを暗号化して返す設計。
- 外部攻撃者が `state` トークンのみを入手した場合: `rejected`/`canceled` の送付（キー削除 = DoS）は可能だが、DoS はスコープ外。偽 API キーの注入には `site_hash` も必要。
- `paidy.artws.info` 自体が侵害された場合は `state` トークン＋`site_hash` の両方が漏洩し得るが、それはプラグインのコード改善では対応不可の信頼境界。

---

## #4【MEDIUM・要確認】マルウェアスキャナの権限境界とソース/パス開示

- **ファイル**: `includes/admin/class-jp4wc-check-security.php`（548-687 行）、`includes/admin/class-jp4wc-malware-check.php`（360-407 行）
- **分類**: 情報開示 / broken access control
- **判定**: 信頼度 6（出力はパターン限定で任意ファイル読取ではない／人手で再評価）

### 問題

スキャン用 REST ルート（`/jp4wc/v1/security-start-scan`, `/jp4wc/v1/security-process-scan-batch`）の権限が `current_user_can('manage_woocommerce')` のみ。Shop Manager（管理者より低権限・通常ファイル/ソース閲覧不可）がスキャンを起動でき、`wp-content/` 配下 PHP のパターン一致行（`line_code`）と絶対パスを REST レスポンスで取得できる。

### 改善方針

- [x] スキャン系 REST の `permission_callback` を `manage_options`（管理者相当）へ引き上げる。
  - 2026-06-10 対応: `class-jp4wc-check-security.php` の 3 箇所（メニュー登録 `wc_admin_register_page` の `capability`、`/security-start-scan` の `permission_callback`、`/security-process-scan-batch` の `permission_callback`）を `manage_woocommerce` → `manage_options` に変更。Shop Manager はスキャンページへのアクセスも REST 呼び出しも不可になった。
- [x] REST レスポンスから `line_code` と絶対パスの扱いを検討する。
  - **存続（変更なし）**: 権限を `manage_options`（管理者）に引き上げたことで、`line_code` とパスを見られるのは管理者のみになった。管理者はもともとプラグインファイルエディタ等でソースコードへのアクセス権を持つため、`line_code` をレスポンスから除去する必要はない（React UI での表示も維持）。

### 確認事項

- [x] 設定 UI（React 管理画面）が想定する権限と整合するか確認。
  - `controls.jsx` でも `line_code` を表示しており、管理者専用ツールとして `manage_options` が適切と判断。

---

## #5【LOW・要確認】スキャン結果のベンダー宛メール送信

- **ファイル**: `includes/admin/class-jp4wc-check-security.php`（644-653 行）、定数 `includes/admin/class-jp4wc-malware-check.php`（21 行 `JP4WC_MALWARE_ALERT_EMAIL = 'wp-admin@artws.info'`）
- **分類**: 情報開示（プライバシー / 透明性）
- **判定**: 信頼度 3（送信対象は自サイトのコードで PII/秘密ではないため除外寄り）

### 問題

スキャン完了時、疑わしいファイルのパス＋ソース行を含むペイロードをハードコードされたベンダーアドレス `wp-admin@artws.info` へ無言で送信する。

### 対応内容

- [x] 2026-06-10: **マルウェアスキャン機能全体を削除**（利用者がほぼいないため）。
  - 削除ファイル: `includes/admin/class-jp4wc-check-security.php`、`includes/admin/class-jp4wc-malware-check.php`、`src/js/jp4wc/admin/security/`（全 JS ソース）、`assets/js/build/admin/security.*`（ビルド成果物）
  - `class-jp4wc.php` の require/instantiate を削除。`webpack.config.js` の `admin/security` エントリを削除。
  - これにより #4（スキャナ権限境界）と #5（ベンダー宛メール）の両問題が根本解消された。

---

## #6【LOW・要確認】A8.net アフィリエイトタグの `<script>` 埋め込み

- **ファイル**: `includes/class-jp4wc-affiliate.php`（87 行）
- **分類**: 潜在的 XSS（script コンテキスト脱出）
- **判定**: 信頼度 3（SKU/注文番号が `</script>` を含む場合のみ・通常は顧客非制御）

### 改善方針（ハードニング）

- [ ] `wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )` を使用する。

---

## #7【LOW・要確認】`wc4jp_rated` AJAX の nonce 欠如

- **ファイル**: `class-jp4wc.php`（登録 47 行 / 関数 256-263 行）
- **分類**: CSRF（影響は管理者フッターの「評価依頼」表示抑止のみ）
- **判定**: 信頼度 2（ハードニング扱い・対応は任意）

### 改善方針（任意）

- [ ] `admin_footer_text`（240 行付近）のインライン JS に `wp_create_nonce('wc4jp_rated')` を出力し、`jp4wc_rated()` 冒頭で `check_ajax_referer('wc4jp_rated', 'security')` を検証する。

---

## 進め方

1. 上の優先度サマリーの順（#1 → #7）で 1 項目ずつ着手する。
2. 各項目の「改善方針」チェックボックスを埋めながら実装。
3. 実装後に `composer lint`・`composer test` を通し、該当する「検証」項目を確認。
4. 完了したら優先度サマリーの「状態」を `[x] 完了` に更新する。
5. コミットは英語、1 脆弱性 = 1 コミット（または 1 PR）を基本とする。

### 監査対象外の確認済み（再掲・対応不要）

- COD / bank-jp / postofficebank / atstore ゲートウェイ: 出力は `wp_kses_post` でエスケープ済み、攻撃者制御の状態変更なし。
- 設定 REST API（`class-jp4wc-settings-api.php`）: 読み書きとも `manage_woocommerce` で保護。
- 配送・読み仮名・住所フィールド、`jp4wc_law` ショートコード、メール描画: 一貫してエスケープ済み。
- `uninstall.php` / `class-jp4wc-install.php`: ユーザー入力・ファイル操作・SQL なし。
- Yahoo 郵便番号 REST（`__return_true`）: ホスト/プロトコルは固定で SSRF 不成立、App ID はサーバー側。
