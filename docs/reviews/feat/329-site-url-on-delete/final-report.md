# dev-cycle 最終報告: feat/329-site-url-on-delete

## 開発内容
- タスク: #329 artws-security-hub: DELETE /site と GET /scan でも site_url を発行先と照合する（クライアントの変更も同じ PR。auto-commit）
- PR: #338 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/338（milestone Version 3.2.0）
- 承認された計画の要約:
  - DELETE /site は任意の JSON 本文 `{schema_version: 1, site_url}` を受け付ける（新スキーマ `site-delete-request.schema.json`）。不正なら 400、発行先と別のサイトなら 401 で、何も削除しない。本文の無い要求（3.1.x 以前）は今のとおり削除する。
  - GET /scan/{token} は任意のクエリ `site_url` を受け付ける。
  - サーバーは 0.7.0。JP4WC 3.2.0 のタグの前にデプロイする。
  - JP4WC は DELETE に今の `home_url()` を付けて送る。法務ページは変更しない。
- ユーザーの決定:
  - サーバーとクライアントを 1 本の PR にする。
  - URL 置換でドメインを移した本番の削除も 401 になることは許容する（旧登録は保存期間で消える）。
- コミット:
  - c53f94e feat(contract): add the site_url body of DELETE /site
  - 0fc2693 feat(security-hub): compare site_url on DELETE /site and GET /scan
  - a5c6847 feat: send the current site address with DELETE /site
  - f732edb docs: record the site_url check of DELETE /site
  - 72941f1 fix: do not report a refused deletion as done on wc.artws.info（R1-1・R1-4）
  - 7a12cbc test(security-hub): check the deletion body against its schema（R1-5）
  - 73c2815 docs: tell a dropped DELETE body apart and keep the rule true（R1-2・R1-3）
  - b44ae60 docs: name the GET command in the deploy body check（R2-1）
  - 記録: a8d1bc3・73d73ac・a5d42d8・40dd011・この最終報告
- 設計ドキュメントからの逸脱: なし。review-loop で 1 点だけ計画を詳しくした: 401 のとき `remote_deleted` を false にし、画面は「このサイトのデータを削除した」と出す（R1-1。計画は「401 の扱いは今のまま」で、手元のデータを消すことは変わらない。新しい翻訳文字列は無い）。

## review-loop（PR 前）
| ラウンド | 指摘 | 修正 | backlog |
|---|---|---|---|
| R1 | Medium 5 / Low 1 | 5 | 1（R1-L1） |
| R2 | R1 の 5 件はすべて解消。新規 Medium 1 / Low 2 | 1 | 2（R2-2・R2-3） |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | 0 | 0 | 収束 |
| G1 | Copilot | 2（本文のみ） | 0 | 2 | 未収束 |
| G2 | Copilot | 0（G1-1 の言い直しだけ） | 0 | 0 | 収束 |

### 修正した指摘
なし（ゲートでの修正は無い）。

### 修正しなかった指摘（スレッドが無いので、PR のラウンドのコメントに理由を書いた）
| ID | bot | 重大度 | 理由 | 場所 |
|---|---|---|---|---|
| G1-1 | Copilot | Low | リリースの関門はマージではなく JP4WC 3.2.0 のタグ。`DEPLOY.md` 8・10 と PR 本文の「マージ後に確認すること」が、タグの前のデプロイと確認を求めている | レビュー本文 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/338#pullrequestreview-5968948310 |
| G1-2 | Copilot | Low | readme の External Services に本文なしの例外が書いていない。ただし送るのは書いてあるより少ない（backlog。3.2.0 の bump PR で直してよい） | 同上 |

## 品質ゲート
- CI: green（a5d42d8・40dd011。Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/44453332154 、artws-security-hub https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/44453332201 ）
- 品質チェック:
  - ルート: `composer lint` 0 件、`composer test-local` 1140 件 green
  - サーバー: `composer lint` 0 件、`composer test-local` 500 件 green
  - 変更したテストファイル: phpcs 0 件

## 次にできること（人間の判断）
- 保留分の修正: G1-2 は 3.2.0 の bump PR で readme を直すのが手軽（`/dev-cycle fix G1-2` でもよい）。R1-L1・R2-2・R2-3 はコメントだけの Low。
- マージ（GitHub 上で人間が行う）→ `/post-merge`
- マージ後: wc.artws.info に artws-security-hub 0.7.0 をデプロイし、`DEPLOY.md` 8「無料診断の API」の 2・3 を確かめる（別のサイトの `site_url` が 401 になり、DELETE の本文が PHP まで届く）。JP4WC 3.2.0 のタグより前に行う。
- その後: `/release-bump start 3.2.0` と `add 338`（changelog に #329。readme の G1-2 もここで直せる）→ タグはユーザーの指示で。
