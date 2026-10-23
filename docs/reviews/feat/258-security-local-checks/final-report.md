# dev-cycle 最終報告: feat/258-security-local-checks

## 開発内容
- タスク: issue #258 セキュリティ診断 Phase 2a — ローカルチェック（V/A/D/M の 17 項目）とスコア計算
- PR: #277 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277
- 計画の要約: `JP4WC_Security_Checks`（17 チェックの登録表・実行・スコア）と `JP4WC_Security_Probe`（自サイトへの HEAD・12 時間キャッシュ）を追加し、`JP4WC_Security_Feed::rescan()` で V-01 照合と合わせて `jp4wc_security_findings` に `checks` / `score` を 1 回で書く。フィードが無くても動き、すべて WP-Cron で実行
- コミット:
  | sha | メッセージ |
  |---|---|
  | 594c7fc | feat: add the local security checks and their score |
  | 0908031 | feat: run the local checks with the vulnerability match |
  | 35a0aa1 | fix: treat the site's own requests as unknown when the home page refuses them |
  | f0939f5 | fix: avoid false results of the htaccess, PHP file and backup checks |
  | c752a90 | test: answer only the checked URL with a malformed response |
  | 0d95940 | fix: read PHP files in uploads far enough to judge them |
  | a901cba | fix: treat the usual direct-request guard as harmless in uploads PHP files |
  | 1cb0348 | fix: count only declare( ... ); as a declaration in uploads PHP files |
  | 0b72987 | fix: do not call uploads PHP with a short open tag harmless |
  | ほか | docs: review-loop / ゲートラウンドの記録 |
- 設計ドキュメントからの逸脱（計画承認時に合意）: V-02 は mtime 方式（ユーザー決定）、V-01 の severity は照合結果で可変、`JP4WC_Security` は未完成のコードなので考慮しない、A-02 / D-01 / D-02 / M-03 の判定方法の小さな拡張、自己 HTTP の前にトップページを 1 回確認（review-loop R1-1）

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 7・Low 10・対象外 1 | 7 | 11 |
| R2 | R1 全件解消・新規 High 1・Medium 3・Low 4 | 4 | 4 |
| R3 | R2 全件解消・Low 1 → APPROVE | 0 | 1 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 2 | 1 | 1 | 未収束 |
| G1 | Copilot | インライン 3・本文のみ 3 | 0 | 6 | 未収束 |
| G2 | Codex | 0 | — | — | 収束 |
| G2 | Copilot | 1 | 1 | 0 | 未収束 |
| G3 | Copilot | 1 | 1 | 0 | 上限（3 回目でも新規指摘あり） |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-2 | Codex | Medium | `defined( 'ABSPATH' ) \|\| exit;` 等のガードで始まる uploads の PHP を M-03 が fail にしていた | a901cba | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354837672 |
| G2-1 | Copilot | High | `declare(ticks=1) system(...);` を宣言だけのファイルとして無害にしていた | 1cb0348 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354922330 |
| G3-1 | Copilot | Medium | CLI の short_open_tag が Off だと短縮タグのコードを無害にしていた（backlog R1-L6） | 0b72987 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354974903 |

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Codex | Low | `xmlrpc_enabled` が false なら資格情報を使うメソッドはすべて止まり、A-04（総当たり）の対象は塞がる。pingback は HEAD で区別できない | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354837661 |
| G1-3 | Copilot | Low | 自己 HTTP の証明書を検証しないのは WordPress 本体のループバックと同じ。偽の pass にはループバックへの中間者攻撃が要る | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354844010 |
| G1-4 | Copilot | Low | V-02 で `filemtime()` が失敗するのは更新の最中くらいで、次の実行で正しくなる | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354844038 |
| G1-5 | Copilot | Low | 12 時間のキャッシュは issue の仕様。強制再取得は #259 の再チェックで入れる | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#discussion_r4354844057 |
| G1-6 | Copilot | — | レビュー本文の見出しだけ（詳細なし）の 3 項目。確かめた内容は G1.md とサマリーコメント | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/277#pullrequestreview-5622410122 |

## 品質ゲート
- CI: https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39879381679 green（0b72987）
- 品質チェック: `composer lint` は main と同数（既存ファイルの既存エラーのみ）、`composer test-local` green（PHPUnit 832 件）

## 次にできること（人間の判断）
- 保留分の修正: `/dev-cycle fix G1-3 G1-4` など、または `/fix-copilot-review 277`
- Copilot は上限で終えたので、数時間後に `/fix-copilot-review 277` で遅れて届いた指摘が無いか確かめることを推奨（Codex は収束済み）
- backlog のユーザー確認事項: R1-L4（V-04 を「機能が使えるか」でなく「自動更新がオンか」で見るか）、R1-X1（未完成の `JP4WC_Security` を消すか完成させるか）
- マージ（GitHub 上で人間が行う）→ マージ後は `/post-merge`
