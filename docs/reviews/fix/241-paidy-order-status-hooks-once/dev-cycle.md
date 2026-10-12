# dev-cycle 状態: fix/241-paidy-order-status-hooks-once
- タスク: 管理画面で Paidy の注文を処理中からキャンセルにすると close が 3 回送られ、取消は成功しているのに失敗の注文メモが 2 件残る(issue #241、2.9.17)
- 開始: 2026-10-12
- ベースブランチ: main
- PR: #242 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/242(head は upstream の `fix/241-paidy-order-status-hooks-once`。Codex が fork の PR では応答しないため)
- オプション: auto-commit(確認ゲートなし)
- 現在のステップ: 完了(Codex・Copilot とも G1 で収束。マージは人間が行う)
- Copilot: 依頼 1 回 / 収束(G1 でスレッド 0 件・本文 0 open findings)
- Codex: 依頼 1 回 / 収束(G1 で「Didn't find any major issues」)

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-12 | 0 | テスト環境を `composer test-install` で準備。main(7fa70fd)で PHPUnit 363 件 OK、lint 94 errors / 12 warnings(変更するファイルの既存の指摘は `class-wc-paidy.php` 7 errors、`woocommerce-for-japan.php` 6 errors、`class-wc-gateway-paidy.php` 0) |
| 2026-10-12 | 1 | 計画承認(注文ステータスの 3 つのフックをコンストラクタから外し、static ハンドラーで 1 回だけ登録して WooCommerce の登録済みインスタンスに委ねる。`paidy_redirect_to_wizard()` はゲートウェイを生成せず設定値を読む。訳文の修正と changelog。`WC_Paidy_Endpoint` のインスタンス・返金ガード B-36・単体版への同期は対象外) |
| 2026-10-12 | 2 | テストを先に書き、main のコードで新しいテスト 7 件中 6 件が失敗することを確認(close・capture・refund がそれぞれ 4 回送られる。インスタンスにコールバックが付く。ウィザードの確認でゲートウェイが生成される。残る 1 件は新メソッドが無いためのエラー)。実装コミット f996097(static ハンドラーで 1 回だけ登録、ウィザードは設定値を読む)・64c4af5(訳文、`.mo` の差分はこの 1 件だけ)・9489d28(changelog)。登録の呼び出しを消すミューテーションで 5 件が失敗(CAUGHT)。PHPUnit 370 件 OK、lint 94 errors / 12 warnings(main と同じ)、新しいテストファイルの phpcs 0 件。translate.wordpress.org の stable の訳文も同じ誤訳(language pack が優先されるため、そちらの修正が別に要る) |
| 2026-10-12 | 3 | review-loop R1: 独立サブエージェントの敵対的レビュー(Low 6 件・対象外 1 件)を裏取りして判定し直し、Medium 3 件を修正 — R1-1 一覧が未読み込みのリクエストと他の決済の注文のテストが無い(2 件追加、ミューテーションで CAUGHT)、R1-2 `has_action()` のガードとそのテストが無意味(WP_Hook がキーで上書きする。削除)、R1-3 changelog の「訳を直した」は language pack が優先されるサイトで効かない(「bundled」に)。コミット 60a2019・9a3625d。Low 1 件・対象外 2 件を backlog へ。R2: サブエージェントの検証と突き合わせ、R1 の 3 件とも解消、新規は Low 2 件(テストのメール用フィルタが無意味、PayPal の static を戻さない)で backlog へ → APPROVE。PHPUnit 371 件 OK、lint は main と同じ |
| 2026-10-12 21:37 | 4 | 記録コミット ee16c7a、upstream へ初回 push(T=2026-10-12T12:37:18Z)、PR #242 を作成 |
| 2026-10-12 21:44 | 5–7 | CI green(3 ジョブ)の後に `@codex review` を投稿し、Copilot に依頼(gh pr edit、pending 一覧で登録を確認)。Copilot は約 3 分、Codex は約 4 分で ee16c7a に応答。G1: Codex は「Didn't find any major issues」→ 収束。Copilot はスレッド 0 件・本文「0 open findings」→ 収束(見出し「Needs a closer look」はステージング確認の要求で、PR 本文の「マージ後」に既出。マージ前に確認できるよう `dist/` に ZIP を作って依頼する)。修正なし |
| 2026-10-12 | 8 | 最終報告を記録。完了 |
