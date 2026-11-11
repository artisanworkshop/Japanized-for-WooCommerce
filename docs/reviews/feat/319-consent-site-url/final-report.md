# dev-cycle 最終報告: feat/319-consent-site-url

## 開発内容
- タスク: #319 無料診断の同意をサイトの URL に紐付ける（プラグイン側。サーバー側は #329 に分けた）
- PR: #330 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/330（milestone Version 3.1.0、`Closes #319`）
- 承認された計画の要約:
  - 同意をサイトの URL に紐付け、URL が違う同意は「同意していない」として扱う。
  - 3.0.x の URL の無い同意は、トークンの URL で判定する。トークンが無ければ同意していない扱い。
  - 削除の確認文と完了通知を、何も送らない場合に合わせる（backlog 2026-10-28 R1-L4 の後半）。
  - サーバー側は別 issue に分ける。
- コミット:
  - 67dbe24 feat: bind the free check consent to the site URL
  - 0cc67a6 fix: say when deleting the check data sends nothing to wc.artws.info
  - 60957c8 docs: update the review backlog for #319
  - 1b5b4bb fix: keep a hash of the site URL with the free check consent
  - a2a2721 / 95d3658 / 534006e / 88d97a9 / 6fa3680 docs: review-loop・dev-cycle の記録
- 計画からの変更（review-loop R1-1 でユーザー判断）:
  - 同意には URL を平文では保存せず、`site_hash`（`sha256( site_key( home_url() ) )`）を保存する。ステージングを作る URL 置換は、シリアライズされた option の中の URL も書き換えるため。
  - 旧形式の同意は、3.1.0 への更新時（`JP4WC_Install::install()` → `upgrade_consent()`）にロックを取って DB から読み、今のサイトの同意と分かればハッシュを書き込む。
  - トークンとレポートはこの PR では変えていない。URL 置換した複製からの削除で本番のデータが消える既存の問題（R1-X1）は #329 に追記した。

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 1（R1-1）、Low 4、対象外 1 | R1-1（ユーザー判断で方式を決定）。あわせて Low 2 件（docblock の限界、トークンのキャッシュ破棄）を修正 | Low 4、対象外 1（R1-X1 は #329 に追記） |
| R2 | 新規の Critical/High/Medium なし（APPROVE） | — | Low 2 |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 1 | 0 | 1 | 未収束 |
| G2 | Copilot | 1（レビュー本文のみ） | 0 | 1 | 未収束 |
| G3 | Copilot | 0 | 0 | 0 | 収束 |

### 修正した指摘
なし（ゲートでは修正なし）。

### 修正しなかった指摘
| ID | bot | 重大度 | 理由 | スレッド |
|---|---|---|---|---|
| G1-1 | Copilot | — | 誤検知。同梱 JSON は 0cc67a6 で更新済みで、新しい 2 件とも入っている（1 行のファイルなので差分が 1 行に見える）。Copilot の G2 は「resolved since last review」と扱った | https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/330#discussion_r4538862633（未解決のまま） |
| G2-1 | Copilot | Low | 更新時にロックが取られていると移行が飛ぶ。3.0.x の診断と重なる場合だけに限られるため保留。backlog 2026-11-11 G2-1 | レビュー本文 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/330#pullrequestreview-5865409675 |

## 品質ゲート
- CI:
  - run 43034631406（534006e）の attempt 1・2 は、PHPUnit 3 ジョブが「Install subversion」で落ちた。ランナー（ubuntu-26.04）が archive/security.ubuntu.com を名前解決できず、コードは原因ではない。
  - attempt 3（2026-11-11 21:08 UTC）で PHPUnit 3 ジョブと PHPCS が green。https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/43034631406
  - 534006e より後のコミットは docs だけなので、CI は走らない（`paths-ignore`）。
- 品質チェック:
  - `composer lint` OK、テストファイルの phpcs OK。
  - `composer test-local` OK（1107 件）。
  - `npm run build`、`wp-scripts lint-js` OK。
  - 翻訳ファイルのテスト OK。
  - E2E（ローカルの wp-env）`admin-security-scan.spec.ts` 3 件 green。R1 の修正は JS を変えていない。
- ミューテーション: 同意のハッシュ比較、移行の判定・ロック・キャッシュ破棄、`install()` からの呼び出しで、すべて CAUGHT。

## 次にできること（人間の判断）
- 保留分の修正: `/dev-cycle fix G2-1`、または `/fix-copilot-review 330`。
- マージ（GitHub 上で人間が行う）。マージ後は `/post-merge`、続けて `/release-bump add 330` で #328 に changelog を足す。
  - readme External Services の「Delete check data は DELETE を送る」を正確にするか（backlog R1-L4）も、bump で判断する。
  - 更新時の移行（3.0.x の同意にハッシュを足す）は、changelog に書くか判断する。
- 3.1.0 のタグの前に確認する: 新しい文言 2 件を translate.wordpress.org にも入れるか（同梱の翻訳は wp.org の言語パックに負ける）。
- 次の issue: #318（ユーザー列挙の対策）。#319 と同じく、1 行の JSON と `.mo` を触るので、この PR のマージ後に始める。
