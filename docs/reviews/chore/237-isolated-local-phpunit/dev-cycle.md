# dev-cycle 状態: chore/237-isolated-local-phpunit
- タスク: ローカルの PHPUnit 環境をほかのプロジェクトと共有せず、`composer test-local` 1 つで何度でも流せるようにする(issue #237)
- 開始: 2026-10-14
- ベースブランチ: main
- PR: #249 https://github.com/artisanworkshop/Japanized-for-WooCommerce/pull/249(head は upstream のブランチ)
- オプション: auto-commit(確認ゲートなし)
- 現在のステップ: 6(G2 の依頼待ち)
- Copilot: 依頼 1 回 / 未収束
- Codex: 依頼 1 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-14 | 0 | 127.0.0.1:3306 は omotegae-project の MySQL が使用中(issue の想定どおりの状態)。共有の `$TMPDIR/wordpress-tests-lib` は macOS の削除で消えていた |
| 2026-10-14 | 1 | 計画承認(既定ポート 10104 = dev-env 台帳 slot 10 の予備 +4、置き場所 `~/.cache/jp4wc-tests`、コンテナ `jp4wc-phpunit-db`。issue の「既定 3306」「置き場所の候補」からの変更はユーザーの回答による) |
| 2026-10-14 | 2 | 実装コミット b779e44(setup-test-env.sh: `latest` をバージョン番号に解決して core とテストライブラリを揃える・`wp-tests-config.php` を毎回書き出す・コンテナ名の完全一致・`JP4WC_TEST_CONTAINER`)・4a8e64f(`bin/test-local.sh` と `composer test-local`)・9b8b081(CLAUDE.md)。**計画外の修正 1 件**: 初回実行で `latest` の core 7.1.3 と trunk のテストライブラリの組み合わせが bootstrap で `WP_Secrets_Store` 不在により失敗(既存の不具合)。CI の install-wp-tests.sh と同じくバージョン番号に解決するよう直した(issue の完了条件「テストが通る」に必須)。確認: 3306 指定で omotegae-project-mysql-1 を名指しして exit 1 / 新規環境で 380 件 OK / `-- --filter Paidy` で 194 件 OK・コンテナ残存 / 自前の `wp-tests-config.php` を 33306 に書き換えても次の実行で 10104 に戻り OK / `docker stop` 後に再起動して OK / ポート変更でコンテナを作り直して OK / 既存環境ではバージョン照会なし。shellcheck: test-local.sh 0 件、setup-test-env.sh は変更外の既存 2 件のみ。lint は main と同じ。既定の `~/.cache/jp4wc-tests` は修正前の初回実行で trunk のライブラリが入ったままなので、ユーザーに削除を依頼する(`rm -rf` は自動モードで拒否された) |
| 2026-10-14 | 3 | review-loop R1: 自己レビュー + 独立サブエージェント(実測込み)。Critical/High 0、Medium 2(R1-M1 既存環境では `latest` のピン留めが効かない / R1-M2 初回セットアップの失敗が見えず直らない)を修正 1079323、Low 7・対象外 1 を backlog。R2: 独立サブエージェントの検証で M1・M2 解消、新規 Medium R2-1(svn export の途中失敗でも記録を書く)を 910e22d で修正、Low 2 を backlog → **APPROVE**。フルスイート 380 件 OK |
| 2026-10-14 22:45 | 4 | upstream へ初回 push(T=2026-10-14T13:44:53Z)、PR #249 を作成 |
| 2026-10-14 22:53 | 5–7 | CI green の後に依頼。G1: Codex 2 件(G1-1 空白を含むパス / G1-2 サンプルのダウンロード失敗が残る)を修正、Copilot はスレッド無し・本文の要約 5 項目(修正 2 は Codex と重複、保留 3)。既定の `~/.cache/jp4wc-tests`(ユーザーが削除済み)を作り直して 380 件 OK。修正 push T=2026-10-14T13:55:42Z、返信・Resolve・サマリ投稿 |
