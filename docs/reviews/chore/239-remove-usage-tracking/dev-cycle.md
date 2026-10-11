# dev-cycle 状態: chore/239-remove-usage-tracking
- タスク: 利用状況トラッキングを新しい仕組みに作り直すため、現行の実装と、配送日・配送時間帯の通知メールの開発元宛て BCC を削除する(issue #239、2.9.17)
- 開始: 2026-10-11
- ベースブランチ: main
- PR: #240 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/240(head は upstream の `chore/239-remove-usage-tracking`。Codex が fork の PR では応答しないため)
- オプション: auto-commit(確認ゲートなし)
- 現在のステップ: 6(ゲート G2 依頼)
- Copilot: 依頼 1 回 / 未収束
- Codex: 依頼 1 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-11 | 0 | テスト環境を `composer test-install` で準備。main(f508a44)で PHPUnit 358 件 OK、lint 97 errors / 30 warnings(変更するファイルのうち既存の指摘は `uninstall.php` 1 件、`class-jp4wc-delivery.php` 2 errors / 7 warnings) |
| 2026-10-11 | 1 | 計画承認(トラッキングのクラスを削除、更新時に WP-Cron イベントと 2 つのオプションを片付け、設定キー・設定パネル・readme の項目を削除、PHP バージョン通知の売上条件を削除、通知メールの BCC を削除。テストを先に書いて main で失敗を確認する) |
| 2026-10-11 | 2 | テストを先に書き、main のコードで新しいテスト 5 件がアサーションで失敗することを確認(更新時・新規インストール時に `wc.artws.info` へ送信、WP-Cron の予約と 2 つのオプションが残る、設定 API が `tracking` を返す、通知メールに `Bcc: wp-admin@artws.info`)。実装コミット 757e670(トラッキングの削除)・a911418(BCC の削除)。`npx wp-scripts build` は main のソースでコミット済みのバンドルをそのまま再現し、変更後の差分は `assets/js/build/admin/settings.*` だけ。PHPUnit 363 件 OK、lint 94 errors / 12 warnings(main は 97 / 30。削除したファイルの分が減った。変更ファイルの既存指摘は基準値のまま) |
| 2026-10-11 | 3 | review-loop R1 APPROVE(Critical / High / Medium なし。独立サブエージェントの敵対的レビューを併用。Low 6 件〔2.6.10〜2.6.19 の別名 cron `wc4jp_tracker_send_event` の片付け漏れ、片付けが毎回の更新で走る、screenshot-1.png に追跡パネルが写ったまま、テストのコメント 2 か所、メールのテストが店舗宛てだけを見る、issue の grep 条件〕と対象外 2 件を backlog へ)。R1 での修正なしのため R2 は不要 |
| 2026-10-11 20:16 | 4 | 記録コミット 1e91f19、upstream へ初回 push(T=2026-10-11T11:16:40Z)、PR #240 を作成 |
| 2026-10-11 20:25 | 5–7 | CI 4 件 green の後に `@codex review` 投稿 + Copilot 依頼(gh pr edit、pending 一覧で登録を確認)。Codex は約 5 分、Copilot は約 6 分で 1e91f19 に応答。G1: Codex 1 件・Copilot 3 件(旧名の WP-Cron イベント `wc4jp_tracker_send_event` の片付け漏れ × 3、screenshot-1.png に追跡パネル × 1。いずれも R1 で Low として backlog に送った内容)。Medium に上げてすべて修正: 1edf554(旧名も消す + テスト。ミューテーション検証 CAUGHT)、681124f(画像からパネルの帯を切り取り)。PHPUnit 363 件 OK |
