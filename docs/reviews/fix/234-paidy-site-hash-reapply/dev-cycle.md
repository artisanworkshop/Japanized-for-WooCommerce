# dev-cycle 状態: fix/234-paidy-site-hash-reapply
- タスク: Paidy の申込設定を追加する経路(再申込)で、`paidy_site_hash` が既にあると `$site_hash` が未定義のまま申込が送られる件の修正(issue #234)
- 開始: 2026-10-11
- ベースブランチ: main
- PR: 未作成(head は upstream に push する予定。Codex が fork PR では応答しないため)
- オプション: auto-commit(確認ゲートなし)
- 現在のステップ: 4(push と PR 作成)
- Copilot: 依頼 0 回 / 未収束
- Codex: 依頼 0 回 / 未収束

## ログ
| 日時(JST) | ステップ | 内容 |
|---|---|---|
| 2026-10-11 | 0 | issue #234 を確認。ウィザードの保存は `currentStep: 2` を `/wp/v2/settings` に送り、オプションが無いと `add_option` 経由で `add_paidy_on_boarding_settings()` の step 2 分岐に入ること、`canceled` コールバックが申込設定だけを消して `paidy_site_hash` を残すことをコードで確認 |
| 2026-10-11 | 1 | 計画承認(private ヘルパー `get_or_create_site_hash()` に 3 か所を集約、保存は `update_option()`、新規テスト 4 件) |
| 2026-10-11 08:08 | 2 | 実装コミット 622bfde。新テスト 4 件のうち 2 件(再申込で保存済み hash を送る / 空の hash 行を作り直す)が main のコードで失敗することを確認(再申込は `site_hash` が `null`)。フル 358 件 OK、lint は main と同じ(97 / 30、変更ファイルは 0)、テストファイルは `--standard=WordPress` で 0 件 |
| 2026-10-11 | 3 | review-loop R1 APPROVE(Critical/High/Medium なし。自己レビュー + 独立サブエージェント。サブエージェントが調査用テストで、main では追加経路 step 1 → 入れ子の step 2 が初回申込でも `site_hash` = null を送っていたことと、修正後は全経路が保存値と同じ hash を 1 回だけ送ることを確認。Low 2 件・対象外 4 件を backlog へ) |
