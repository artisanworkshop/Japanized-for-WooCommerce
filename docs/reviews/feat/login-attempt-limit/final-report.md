# dev-cycle 最終報告: feat/login-attempt-limit
## 開発内容
- タスク: ログイン試行回数の制限（IP + ユーザー名、WordPress と WooCommerce のログイン、他の対策があれば眠る、A-02 の pass 条件、Security タブの設定、既定オフ）。issue #289
- PR #290 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/290（3.1.0 向け。**3.0.0 のタグ push の後にマージ**）
- 承認された計画: `JP4WC_Login_Limiter`（`authenticate` 50 / `wp_login_failed` / `wp_login`）、閾値 3 つを Security タブで設定、既知プラグイン・`.htaccess`・Jetpack の総当たり防御があれば眠る、`JP4WC_LOGIN_LIMIT_DISABLE`、A-02 の pass 条件、REST ビュー `login_limit`、アンインストール
- コミット: d673827 feat（実装）/ 8b7b476 fix（review-loop R1）/ 38f0e89 fix（R2）/ 3b4e52a fix（G1）+ docs 各回
- 設計ドキュメントからの逸脱: transient → 失敗 1 件 = 1 option 行（R1-5、RMW の並列取りこぼし）。`application_password_failed_authentication` のフック削除（R1-1、Basic 認証付きサイトの REST 要求を数えて全員を締め出す）。`wp_login` で IP を消さない（R1-3）

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | High 2・Medium 5・Low 7・対象外 2 | 7 + Low 3（8b7b476） | 6 |
| R2 | R1 全件解消・新規 Medium 1・Low 2 | 3（38f0e89） | 0 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Copilot | 4 | 2 | 2（誤検知） | 未収束 |
| G1 | Codex | 1 | 1 | 0 | 未収束 |
| G2 | Copilot | 0 | — | — | 収束 |
| G2 | Codex | 0 | — | — | 収束 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| G1-1 / G1-5 | Copilot / Codex | Medium | `mb_strtolower` の有無でキーが変わる → 既存ユーザーは保存された `user_login` に解決、ASCII の `strtolower()` のみ | 3b4e52a | #discussion_r4388081638 / #discussion_r4388178477 |
| G1-2 | Copilot | Medium | 失敗のたびに全体の期限切れを DELETE → 同じキーだけ、全体は日次 | 3b4e52a | #discussion_r4388081697 |

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-3 | Copilot | — | 誤検知: `wp_login_failed` は `wp_authenticate()` 自身が発火（XML-RPC も通る） | #discussion_r4388081736 |
| G1-4 | Copilot | — | 誤検知: JS 用 JSON に 15 文字列の日本語を追加済み | #discussion_r4388081810 |

## 品質ゲート
- CI: green（G1・G2 とも `--wait-ci` で確認）/ `composer test-local` 929 件 green / `composer lint` main と同じ件数 / 変更ファイル phpcs・lint-js 0 件

## 次にできること（人間の判断）
- Copilot の G2 総評（人間確認の要請）= backlog R1-X2: Jetpack / Jetpack Protect の `Brute_Force_Protection::is_enabled()` が WordPress.com 接続中だけ true になるか、未接続でも true になるかを jetpack-waf のソースで確認する。未接続で true なら、効いていない防御の陰で limiter が眠る
- 保留分の修正: `/dev-cycle fix G1-3 G1-4`（どちらも誤検知なので不要の見込み）
- マージ（GitHub 上で人間が行う。**3.0.0 のタグ push の後**）→ マージ後は `/post-merge` → 3.1.0 の bump PR で `/release-bump add 290`
