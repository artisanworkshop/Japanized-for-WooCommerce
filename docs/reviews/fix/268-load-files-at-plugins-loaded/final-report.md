# dev-cycle 最終報告: fix/268-load-files-at-plugins-loaded

## 開発内容
- タスク: issue #268。プラグインの更新中に、plugins_loaded の後で読むファイルが無いと fatal error になる問題で、#266 の残りにあたる。
- PR: #269 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/269
- 計画: クラスのファイルはすべて plugins_loaded で読み、フックではインスタンスを作るだけにする。ファイルの名前と場所は変えない。
- issue からの追加:
  - Paidy のブロック対応。init 5 で全リクエストが読んでいた。main で更新を再現すると、最初に落ちるのはここだった。
  - 決済 4 種のブロック対応のガードを完全修飾名にした。
- コミット:
  - 39b7133 fix: check the payment block parent class by its full name
  - bc99c8c fix: load class files at plugins_loaded, not in later hooks
  - 7a0c958 fix: load the settings classes from the admin settings file(R1-1)
  - 3ea4c87 fix: skip a payment block support whose class was not declared(R1-2)
  - 8b77a63 fix: skip the Paidy block support when its class was not declared(G2-2 / G2-3)
  - docs: 2b880de / d90c77b / f4f7d3a / 91666d5 / e3facf8 と、この記録
- 設計ドキュメントからの逸脱: なし

## review-loop(PR 前)
| ラウンド | 指摘(C/H/M) | 修正 | backlog |
|---|---|---|---|
| R1 | 0 / 1 / 1 | 2(R1-1 版の混在で settings API と PHP 通知が読まれない、R1-2 逆向きの混在で決済ブロックの `new` が落ちる) | 対象外 2(R1-X1 読み込み順への依存と Boot Sequence の記述、R1-X2 paidy-wc)+ Low 2 |
| R2 | 0 / 0 / 0(APPROVE) | 0 | Low 1(R2-L1) |

## Codex / Copilot ゲート
| ラウンド | bot | 新規指摘 | 修正 | 保留 | 状態 |
|---|---|---|---|---|---|
| G1 | Codex | 0 | — | — | 収束 |
| G1 | Copilot | 0 → 訂正: 3(同じ HEAD への 2 回目のレビュー) | — | — | 訂正: 未収束 |
| G2 | Copilot | (G1 の 3 件に対応) | 2(8b77a63) | 0(対応不要 1 は Resolve) | 未収束(再依頼していない) |

G1 では、Copilot の 1 回目のレビュー(Approval recommended)だけを見て収束と判定した。37 秒後に同じ HEAD へ届いた 2 回目のレビューの 3 件は、2026-10-22 に `/fix-copilot-review 269` で拾った(G1.md の訂正、G2.md)。

### 修正した指摘
- G2-2 / G2-3: 新しいメインファイルが古い class-wc-paidy.php と一緒に動くと、Paidy のブロック対応のクラスが宣言されず、init 5 で fatal になる。登録コールバックでクラスを確かめてから登録するようにした。決済 5 種すべてに確認があることを見るテストを足した(8b77a63)。

### 修正しなかった指摘
- G2-1(対応不要、ユーザー承認のうえ Resolve):
  - 指摘: ロールバック時、2.9.18 の admin-settings の後からの読み込みが残る。
  - 判断: 失敗するのは同じリクエストの間に 2 回入れ替えが起きたときだけで、この PR で増えた処理でもない。

## 品質ゲート
- CI: Testing https://github.com/artisanworkshop/Japanized-for-WooCommerce/actions/runs/39564963234 green(91666d5)
- 品質チェック:
  - `composer test-local`: 445 件 green(8b77a63)
  - `composer lint`: main と同じ既存の 94 errors / 12 warnings。変更したファイルは 0 件。
  - テストの `phpcs --standard=WordPress`: 0 件
- 実証(scratchpad):
  - 更新の再現(plugins_loaded の最後で `includes/` を別名に移す): main は init 5 で Paidy のブロック対応の `require` が fatal になり、このブランチは最後まで通る。
  - 版の混在: 2.9.18 のルートファイルと新しい includes/、新しいルートファイルと 2.9.18 の includes/ の両方で、fatal にならない。
  - 新しいメインファイルと 2.9.18 の class-wc-paidy.php の組み合わせ: e3facf8 では `Class "WC_Payments_Paidy_Blocks_Support" not found` で止まる。8b77a63 では最後まで通り、Paidy だけがそのリクエストで登録されない。

## 次にできること(人間の判断)
- Copilot は未収束(依頼 1 回)。8b77a63 へ再依頼するか判断する。
- マージ(GitHub 上で人間が行う)。マージ後は `/post-merge` を実行する。
- 2.9.19 の bump PR で、changelog に #268 の行を入れる。
- paidy-wc への同期(backlog R1-X2)。
- backlog R1-X1 を issue にするか判断する。`jp4wc_blocks_support()` が、プラグインの読み込み順に依存している件である。
