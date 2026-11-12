# dev-cycle 最終報告: feat/318-hide-user-names

## 開発内容
- タスク: issue #318（3.1.0）ユーザー名の列挙対策を REST API のユーザー一覧以外にも広げる。auto-commit で実行
- PR: #331 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/331（milestone Version 3.1.0）
- 承認された計画:
  - 別の設定 `wc4jp-hide-user-names`（既定オフ）を足す。ログインしていない訪問者には、著者アーカイブを 404 にし、著者リンク・ユーザーのサイトマップ・コメントの class・oEmbed の著者からユーザー名を外す。誰に対しても、ログイン・パスワードリセット・REST の Basic 認証のエラーを揃える。
  - チェック A-06（admin / 低）を新設し、サーバー（artws-security-hub 0.5.0）にも同じ ID と ja / en の文言を足す。
  - 翻訳、readme、screenshot-8 を更新する。
- コミット:
  - b29e7e5 feat: add a setting that keeps user names from visitors
  - 0d1e9c7 feat: add security check A-06 for user names found from author IDs
  - 19d86ef feat: show the user name setting and check A-06 on the Security tab
  - 0e88f26 i18n: add the Japanese strings of the user name setting and check A-06
  - 35ae157 docs: describe the user name setting and retake screenshot 8
  - 290978e fix: close the remaining ways around the user name setting（review-loop R1）
  - b660aaf fix: keep a login page request out of the WooCommerce reset path（review-loop R2）
  - 26270a8 fix: find user-dependent error codes anywhere in a login or REST error（G1-1）
  - 記録用の docs コミット: 01bd17a、52364a6、47554e4、21a0610、ded4337、本報告
- 設計ドキュメントからの逸脱: なし

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | High 1 / Medium 3 / Low 4 | 4（290978e） | 4（R1-L1〜L4） |
| R2 | R1-2 の残り 1 / Low 2 | 2（b660aaf: R1-2、R2-1） | 1（R2-2） |
| R3 | 0（APPROVE） | 0 | 0 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 2 | 1 | 1 | — |
| G2 | Copilot | 0（本文は既知の事柄のみ） | 0 | 0 | 収束 |

### 修正した指摘
| ID | bot | 重大度 | 内容 | コミット | スレッド |
|---|---|---|---|---|---|
| R1-1 | review-loop | High | 空の `rest_route` を付けると、著者アーカイブが訪問者に開いた | 290978e | — |
| R1-2 | review-loop | Medium | WPS Hide Login の構成でリセットの判定から漏れた。`wc_reset_password` を足した要求も含む | 290978e、b660aaf | — |
| R1-3 | review-loop | Medium | `lostpassword_errors` の CAPTCHA を、未登録ユーザーだけが素通りした | 290978e | — |
| R1-4 | review-loop | Medium | 未登録のメールアドレスと他のエラーが重なると「アカウントがありません」が残った | 290978e | — |
| R2-1 | review-loop | Low | R1-1 のページ全体のテストが、修正前のコードでも通っていた | b660aaf | — |
| G1-1 | Copilot | Medium | ログインと REST のエラーで、先頭のコードしか見ていなかった | 26270a8 | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/331#discussion_r4548101532 |

### 修正しなかった指摘（PR 上で未解決のまま残してある）
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-2 | Copilot | — | 誤検知。コアは `invalidcombo` を `lostpassword_errors` の後で足すので、判定の時点では存在しない（テストと tests サイトで確認）。WooCommerce はこのコードを使わない | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/331#discussion_r4548101577 |

backlog（docs/review-backlog.md、2026-11-12）: R1-L1（A-06 の要求先選びのずれ）、R1-L2（ホームのパスと nicename の一致で誤 fail）、R1-L3（REST のユーザー一覧が別の設定だと説明に無い）、R1-L4（特権管理者だけのサイトで A-06 が unknown）、R2-2（lost password の処理中に別のプラグインが呼ぶ `retrieve_password()`）。

## 品質ゲート
- CI（ded4337）: すべて green
  - Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/43185447727
  - artws-security-hub https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/43185447693
- 品質チェック: green
  - PHPUnit 1128 件
  - composer lint 0、テストの phpcs 0、lint-js 0、npm run build
  - サーバー lint 0 / PHPUnit 424 件
- wp-env の tests サイトで確かめたこと（設定オン）:
  - `?author=1` が 404
  - ログインのエラーが同じで、入力欄も残る
  - 未登録のリセットが `checkemail=confirm` へ送られる
  - REST の Basic 認証が同じ 401
- 確かめられなかったこと: A-06 の Web 側の再チェック。tests サイトが自分自身の `localhost:8891` に接続できないため（判定は単体テストで確かめた）

## 次にできること（人間の判断）
- G1-2 の再確認: 不要（誤検知）。見直すなら `/fix-copilot-review 331`
- backlog の Low の修正: `/dev-cycle fix` の対象外（PR 上のスレッドではない）。必要なら別 issue に
- マージ（GitHub 上で人間が行う）→ マージ後は `/post-merge`
- マージ後:
  - `/release-bump add 331`（#328 の changelog）
  - artws-security-hub 0.5.0 を wc.artws.info に反映する（DEPLOY.md の 0.4.1 → 0.5.0。3.1.0 のタグより前）
  - translate.wordpress.org の翻訳（新しい文字列 6 件）
