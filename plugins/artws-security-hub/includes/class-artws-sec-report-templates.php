<?php
/**
 * Wording of the report.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Report_Templates
 *
 * The text of each finding, by check ID, in Japanese and English. The fix
 * steps follow the Security tab of JP4WC (security-labels.js and its
 * Japanese translation), so the report and the tab give the same advice.
 * Everything is plain text: the client escapes it.
 *
 * V-01 and V-03 are built from what the server found (texts()); the other
 * checks have a fixed text (CHECKS).
 */
class Artws_Sec_Report_Templates {

	/**
	 * Languages of the report.
	 */
	const LANGUAGES = array( 'ja', 'en' );

	/**
	 * Fixed text of the checks the client reports.
	 */
	const CHECKS = array(
		'V-02' => array(
			'ja' => array(
				'title'  => '更新が 180 日以上適用されていないプラグインがあります',
				'detail' => '更新が公開されているのに、180 日以上更新されていないプラグインがあります。',
				'impact' => '更新で修正された脆弱性が残ったままになり、公開された攻撃手法で狙われるおそれがあります。',
				'fix'    => array(
					'「プラグイン」画面でプラグインを更新し、使っていないプラグインは削除してください。',
				),
			),
			'en' => array(
				'title'  => 'Plugin updates left uninstalled for 180 days or more',
				'detail' => 'A plugin has an update available but has not been updated for 180 days or more.',
				'impact' => 'Vulnerabilities fixed by the update stay open and can be attacked with published exploits.',
				'fix'    => array(
					'Update the plugins on the Plugins screen, and delete the plugins you no longer use.',
				),
			),
		),
		'V-04' => array(
			'ja' => array(
				'title'  => 'プラグインや WordPress のセキュリティ更新が自動で適用されません',
				'detail' => '自動更新が無効のプラグインがあるか、WordPress のセキュリティリリースの自動適用が止められています。',
				'impact' => '脆弱性の修正が公開されてから適用されるまでの間、攻撃を受けやすくなります。',
				'fix'    => array(
					'「プラグイン」画面ですべてのプラグインの自動更新を有効にし、WordPress のセキュリティ更新の自動適用を止めないでください（WP_AUTO_UPDATE_CORE やフィルター）。',
				),
			),
			'en' => array(
				'title'  => 'Security updates of plugins or WordPress are not installed automatically',
				'detail' => 'Some plugins have automatic updates turned off, or the automatic security releases of WordPress are turned off.',
				'impact' => 'The site stays open to attack between the release of a fix and its installation.',
				'fix'    => array(
					'Turn on automatic updates for every plugin on the Plugins screen, and do not turn off the automatic security updates of WordPress (WP_AUTO_UPDATE_CORE or a filter).',
				),
			),
		),
		'A-01' => array(
			'ja' => array(
				'title'  => '「admin」という名前のユーザーがいます',
				'detail' => 'ユーザー名が admin のユーザーがいます。',
				'impact' => '攻撃者が最初に試すユーザー名なので、パスワードの総当たり攻撃が成功しやすくなります。',
				'fix'    => array(
					'別のユーザー名で管理者を作成してから admin ユーザーを削除し、そのコンテンツを新しい管理者に割り当ててください。',
				),
			),
			'en' => array(
				'title'  => 'A user is named "admin"',
				'detail' => 'There is a user whose user name is admin.',
				'impact' => 'It is the first user name attackers try, which makes password guessing attacks more likely to succeed.',
				'fix'    => array(
					'Create an administrator with another user name, then delete the admin user and attribute its content to the new one.',
				),
			),
		),
		'A-02' => array(
			'ja' => array(
				'title'  => 'ログインページが保護されていません',
				'detail' => 'ログインの試行回数の制限も、wp-login.php へのアクセス制限も見つかりませんでした。',
				'impact' => 'パスワードの総当たり攻撃を際限なく受けるおそれがあります。',
				'fix'    => array(
					'セキュリティプラグインでログインの試行回数を制限するか、サーバーの設定で wp-login.php へのアクセスを IP アドレスやパスワードで制限してください。',
				),
			),
			'en' => array(
				'title'  => 'The login page is not protected',
				'detail' => 'Neither a limit on login attempts nor a restriction of wp-login.php was found.',
				'impact' => 'Attackers can keep guessing passwords without limit.',
				'fix'    => array(
					'Limit login attempts with a security plugin, or restrict wp-login.php to your IP addresses or a password in the server configuration.',
				),
			),
		),
		'A-03' => array(
			'ja' => array(
				'title'  => '二要素認証が使われていません',
				'detail' => '二要素認証のプラグインが見つかりませんでした。',
				'impact' => '管理者のパスワードが漏れると、そのまま管理画面に入られてしまいます。',
				'fix'    => array(
					'二要素認証のプラグインを導入し、管理者に対して有効にしてください。',
				),
			),
			'en' => array(
				'title'  => 'Two-factor authentication is not used',
				'detail' => 'No two-factor authentication plugin was found.',
				'impact' => 'Anyone who learns an administrator\'s password can log in to the admin screens.',
				'fix'    => array(
					'Install a two-factor authentication plugin and turn it on for administrators.',
				),
			),
		),
		'A-04' => array(
			'ja' => array(
				'title'  => 'XML-RPC が外部から利用できます',
				'detail' => '外部から xmlrpc.php に到達でき、XML-RPC の呼び出しに応答しました。',
				'impact' => '1回の要求で多数のパスワードを試せるため、総当たり攻撃に使われるおそれがあります。',
				'fix'    => array(
					'XML-RPC を使う連携（WordPress のモバイルアプリ、Jetpack、一部のサービス）が無ければ、セキュリティプラグインやサーバーの設定で xmlrpc.php へのアクセスを止めてください。',
				),
			),
			'en' => array(
				'title'  => 'XML-RPC is open to anyone',
				'detail' => 'xmlrpc.php can be reached from outside and answered an XML-RPC call.',
				'impact' => 'One request can try many passwords, which makes it a tool for password guessing attacks.',
				'fix'    => array(
					'If nothing uses XML-RPC (the WordPress mobile apps, Jetpack and some services do), block xmlrpc.php with a security plugin or the server configuration.',
				),
			),
		),
		'A-05' => array(
			'ja' => array(
				'title'  => 'REST API でユーザー一覧を取得できます',
				'detail' => 'ログインしていない訪問者に、REST API（/wp-json/wp/v2/users）がユーザーの一覧を返しました。',
				'impact' => '管理者のユーザー名を知られ、ログインへの攻撃に使われるおそれがあります。',
				'fix'    => array(
					'セキュリティプラグインなどで、ログインしていない訪問者に REST API のユーザー一覧（/wp-json/wp/v2/users）を返さないようにしてください。',
				),
			),
			'en' => array(
				'title'  => 'The REST API lists users',
				'detail' => 'The REST API (/wp-json/wp/v2/users) listed users to a visitor who was not logged in.',
				'impact' => 'Attackers learn the administrators\' user names and use them to attack the login.',
				'fix'    => array(
					'Stop the users REST route (/wp-json/wp/v2/users) from listing users to visitors who are not logged in, for example with a security plugin.',
				),
			),
		),
		'D-01' => array(
			'ja' => array(
				'title'  => 'アップロードディレクトリのファイル一覧が表示されます',
				'detail' => 'アップロードディレクトリにアクセスすると、ファイルの一覧が表示されました。',
				'impact' => '公開していないファイルや、ファイル名から推測できる情報を第三者に見られるおそれがあります。',
				'fix'    => array(
					'サーバーの設定でディレクトリの一覧表示を無効にしてください（Apache なら .htaccess に Options -Indexes を追加）。',
				),
			),
			'en' => array(
				'title'  => 'The uploads directory lists its files',
				'detail' => 'Opening the uploads directory showed a list of its files.',
				'impact' => 'Anyone can find files that were not meant to be public, and learn from their names.',
				'fix'    => array(
					'Turn off directory listing in the server configuration (on Apache, add Options -Indexes to .htaccess).',
				),
			),
		),
		'D-02' => array(
			'ja' => array(
				'title'  => '公開ディレクトリにバックアップやログのファイルがあります',
				'detail' => 'ブラウザからダウンロードできる場所に、バックアップ・アーカイブ・データベースのダンプ・ログのファイルがあります。',
				'impact' => '顧客情報や wp-config.php の内容（データベースのパスワードなど）が漏えいするおそれがあります。',
				'fix'    => array(
					'WordPress・wp-content・uploads の各ディレクトリにあるバックアップ・アーカイブ・データベースのダンプ・ログのファイルを移動または削除するか、サーバーの設定でアクセスを止めてください。',
				),
			),
			'en' => array(
				'title'  => 'Backups or logs in public directories',
				'detail' => 'Backups, archives, database dumps or log files are where a browser can download them.',
				'impact' => 'Customer data or the contents of wp-config.php (such as the database password) can leak.',
				'fix'    => array(
					'Move or delete the backups, archives, database dumps and log files in the WordPress, wp-content and uploads directories, or block them in the server configuration.',
				),
			),
		),
		'D-03' => array(
			'ja' => array(
				'title'  => 'エラーメッセージがサイトに表示されます',
				'detail' => 'WP_DEBUG が有効で、エラーメッセージを画面に表示する設定になっています。',
				'impact' => 'サーバーのパスや設定などの内部情報が訪問者に見え、攻撃の手がかりになります。',
				'fix'    => array(
					'wp-config.php で WP_DEBUG_DISPLAY（または WP_DEBUG）を false にしてください。',
				),
			),
			'en' => array(
				'title'  => 'Error messages are shown on the site',
				'detail' => 'WP_DEBUG is on and error messages are set to be displayed.',
				'impact' => 'Visitors see internal details such as server paths and settings, which help attackers.',
				'fix'    => array(
					'Set WP_DEBUG_DISPLAY (or WP_DEBUG) to false in wp-config.php.',
				),
			),
		),
		'D-04' => array(
			'ja' => array(
				'title'  => 'WooCommerce のログファイルをダウンロードできます',
				'detail' => 'wp-content/uploads/wc-logs/ のログファイルにブラウザからアクセスできます。',
				'impact' => 'ログに含まれる注文や決済の情報が漏えいするおそれがあります。',
				'fix'    => array(
					'サーバーの設定で wp-content/uploads/wc-logs/ へのアクセスを止めてください（WooCommerce がそこに置く .htaccess を nginx は読みません）。',
				),
			),
			'en' => array(
				'title'  => 'WooCommerce log files can be downloaded',
				'detail' => 'The log files in wp-content/uploads/wc-logs/ can be opened in a browser.',
				'impact' => 'Order and payment details written to the logs can leak.',
				'fix'    => array(
					'Block wp-content/uploads/wc-logs/ in the server configuration (nginx does not read the .htaccess file WooCommerce puts there).',
				),
			),
		),
		'D-05' => array(
			'ja' => array(
				'title'  => '決済ページやマイアカウントページが HTTPS ではありません',
				'detail' => '決済ページまたはマイアカウントページが https:// で表示されていません。',
				'impact' => '入力された個人情報やログイン情報が、通信の途中で盗み見られたり書き換えられたりするおそれがあります。',
				'fix'    => array(
					'SSL 証明書を導入し、「設定」>「一般」の WordPress アドレスとサイトアドレスを https:// に変更してください。',
				),
			),
			'en' => array(
				'title'  => 'The checkout or My account page is not on HTTPS',
				'detail' => 'The checkout page or the My account page is not served over https://.',
				'impact' => 'Personal and login details entered there can be read or changed on the way.',
				'fix'    => array(
					'Install an SSL certificate and change the WordPress Address and Site Address in Settings > General to https://.',
				),
			),
		),
		'M-01' => array(
			'ja' => array(
				'title'  => '管理画面からのファイル編集が無効化されていません',
				'detail' => 'wp-config.php に DISALLOW_FILE_EDIT が設定されていません。',
				'impact' => '管理者アカウントが乗っ取られた場合に、テーマやプラグインのファイルを書き換えられます。',
				'fix'    => array(
					'wp-config.php に define( \'DISALLOW_FILE_EDIT\', true ); を追加してください。',
				),
			),
			'en' => array(
				'title'  => 'File editing in the admin screens is not turned off',
				'detail' => 'DISALLOW_FILE_EDIT is not set in wp-config.php.',
				'impact' => 'Anyone who takes over an administrator account can rewrite the files of themes and plugins.',
				'fix'    => array(
					'Add define( \'DISALLOW_FILE_EDIT\', true ); to wp-config.php.',
				),
			),
		),
		'M-02' => array(
			'ja' => array(
				'title'  => 'マルウェアのスキャンや WAF がありません',
				'detail' => 'マルウェアをスキャンするか攻撃を遮断するセキュリティプラグインが見つかりませんでした。',
				'impact' => 'サイトが改ざんされたりマルウェアを仕込まれたりしても、気付くのが遅れます。',
				'fix'    => array(
					'マルウェアをスキャンするか攻撃を遮断するセキュリティプラグインを導入してください。',
				),
			),
			'en' => array(
				'title'  => 'No malware scanning or web application firewall',
				'detail' => 'No security plugin that scans for malware or filters attacks was found.',
				'impact' => 'If the site is altered or infected, it takes longer to notice.',
				'fix'    => array(
					'Install a security plugin that scans for malware or filters attacks.',
				),
			),
		),
		'M-03' => array(
			'ja' => array(
				'title'  => 'アップロードディレクトリに PHP ファイルがあります',
				'detail' => '画像などを置くアップロードディレクトリに PHP ファイルがあります。',
				'impact' => 'マルウェア（バックドア）の可能性があり、サイトを遠隔操作されるおそれがあります。',
				'fix'    => array(
					'アップロードディレクトリにある PHP ファイルを確認し、心当たりの無いものは削除してください（マルウェアの可能性があります）。',
					'サーバーの設定で、このディレクトリでの PHP の実行を止めてください。',
				),
			),
			'en' => array(
				'title'  => 'PHP files in the uploads directory',
				'detail' => 'The uploads directory, meant for images and other media, has PHP files in it.',
				'impact' => 'They may be malware (a backdoor) that lets someone control the site remotely.',
				'fix'    => array(
					'Check the PHP files in the uploads directory and delete the ones you do not know, which may be malware.',
					'Stop PHP from running in that directory in the server configuration.',
				),
			),
		),
	);

	/**
	 * Texts built from what the server found; sprintf() arguments are noted.
	 */
	const TEXTS = array(
		'ja' => array(
			// Slug, version.
			'vuln_title_plugin'   => 'プラグイン %1$s %2$s に既知の脆弱性があります',
			'vuln_title_theme'    => 'テーマ %1$s %2$s に既知の脆弱性があります',
			'vuln_title_core'     => 'WordPress %2$s に既知の脆弱性があります',
			// Record title, CVSS score, CVSS rating.
			'vuln_detail_cvss'    => '%1$s（CVSS %2$s %3$s）',
			'vuln_impact'         => array(
				'critical' => '認証なしでサイトを乗っ取られるなど、深刻な被害につながるおそれがあります。すでに攻撃に使われている可能性もあります。',
				'high'     => 'サイトの改ざんや情報の漏えいにつながるおそれがあります。',
				'medium'   => '条件がそろうと、サイトの改ざんや情報の漏えいにつながるおそれがあります。',
				'low'      => '影響は限られますが、他の弱点と組み合わせて悪用されるおそれがあります。',
			),
			// Slug, fixed version.
			'vuln_fix_plugin'     => '「プラグイン」画面で %1$s を %2$s 以上に更新してください。',
			'vuln_fix_theme'      => '「外観」>「テーマ」で %1$s を %2$s 以上に更新してください。',
			'vuln_fix_core'       => '「ダッシュボード」>「更新」で WordPress を %2$s 以上に更新してください。',
			// Slug.
			'vuln_nofix_plugin'   => '修正版はまだ公開されていません。%1$s を停止して削除し、代わりのプラグインを検討してください。',
			'vuln_nofix_theme'    => '修正版はまだ公開されていません。別のテーマに切り替えてから %1$s を削除してください。',
			'vuln_nofix_core'     => '修正版はまだ公開されていません。WordPress の更新が公開されたらすぐに適用してください。',
			'vuln_after_update'   => '更新後に、もう一度診断を実行して脆弱性が残っていないことを確かめてください。',
			'eol_title'           => 'サポートが終了したバージョンを使っています',
			// Software name, running version, oldest supported version.
			'eol_item'            => '%1$s %2$s（サポート対象は %3$s 以上）',
			// List of eol_item.
			'eol_detail'          => '次のバージョンはサポートが終了しています: %s。',
			'eol_impact'          => 'サポートが終了したバージョンには、見つかった脆弱性の修正が提供されません。',
			'eol_fix_wordpress'   => '「ダッシュボード」>「更新」で WordPress を更新してください。',
			'eol_fix_woocommerce' => '「プラグイン」画面で WooCommerce を更新してください。',
			'eol_fix_php'         => 'サポート中の PHP のバージョンへの切り替え方法をサーバー会社に確認してください。',
			'eol_list_separator'  => '、',
			'step_order'          => '要対応の項目は優先順に並んでいます。上から順に対応してください。',
			'step_none'           => '要対応の項目は見つかりませんでした。',
			'step_large_store'    => '取引の規模が大きいストアは攻撃の標的になりやすいため、重大度が「緊急」と「高」の項目はできるだけ早く対応してください。',
			// Count, check IDs.
			'step_unknown'        => '確認できなかった項目が %1$d 件あります（%2$s）。JP4WC の「セキュリティ」タブで、もう一度チェックしてください。',
			'step_stale'          => '脆弱性データの更新が遅れているため、最近公開された脆弱性が含まれていない可能性があります。',
			'step_rescan'         => '対応が終わったら、もう一度診断を実行して結果を確かめてください（診断は1時間に1回まで実行できます）。',
		),
		'en' => array(
			'vuln_title_plugin'   => 'The plugin %1$s %2$s has a known vulnerability',
			'vuln_title_theme'    => 'The theme %1$s %2$s has a known vulnerability',
			'vuln_title_core'     => 'WordPress %2$s has a known vulnerability',
			'vuln_detail_cvss'    => '%1$s (CVSS %2$s %3$s)',
			'vuln_impact'         => array(
				'critical' => 'It can lead to serious damage, such as the site being taken over without logging in. It may already be attacked.',
				'high'     => 'It can lead to the site being altered or data leaking.',
				'medium'   => 'Under some conditions, it can lead to the site being altered or data leaking.',
				'low'      => 'Its impact is limited, but it can be combined with other weaknesses.',
			),
			'vuln_fix_plugin'     => 'Update %1$s to %2$s or later on the Plugins screen.',
			'vuln_fix_theme'      => 'Update %1$s to %2$s or later in Appearance > Themes.',
			'vuln_fix_core'       => 'Update WordPress to %2$s or later in Dashboard > Updates.',
			'vuln_nofix_plugin'   => 'No fixed version has been released. Deactivate and delete %1$s, and look for a replacement.',
			'vuln_nofix_theme'    => 'No fixed version has been released. Switch to another theme, then delete %1$s.',
			'vuln_nofix_core'     => 'No fixed version has been released. Install the WordPress update as soon as it is released.',
			'vuln_after_update'   => 'After updating, run the check again to make sure the vulnerability is gone.',
			'eol_title'           => 'A version that is no longer supported is in use',
			'eol_item'            => '%1$s %2$s (supported from %3$s)',
			'eol_detail'          => 'These versions are no longer supported: %s.',
			'eol_impact'          => 'Vulnerabilities found in an unsupported version are not fixed.',
			'eol_fix_wordpress'   => 'Update WordPress in Dashboard > Updates.',
			'eol_fix_woocommerce' => 'Update WooCommerce on the Plugins screen.',
			'eol_fix_php'         => 'Ask your hosting provider how to switch to a supported PHP version.',
			'eol_list_separator'  => ', ',
			'step_order'          => 'The items that need action are listed most urgent first. Work through them from the top.',
			'step_none'           => 'No items that need action were found.',
			'step_large_store'    => 'Stores with a large volume of business are frequent targets, so deal with the critical and high items as soon as you can.',
			'step_unknown'        => '%1$d items could not be checked (%2$s). Run the checks again on the Security tab of JP4WC.',
			'step_stale'          => 'The vulnerability data is out of date, so recently disclosed vulnerabilities may be missing.',
			'step_rescan'         => 'When you have dealt with the items, run the check again to see the result (the check can run once an hour).',
		),
	);

	/**
	 * Names of the software V-03 checks.
	 */
	const EOL_NAMES = array(
		'php'         => 'PHP',
		'wordpress'   => 'WordPress', // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Array key, not text.
		'woocommerce' => 'WooCommerce',
	);

	/**
	 * Returns the report language for a WordPress locale.
	 *
	 * @param string $locale Locale from the request (ja, en_US, ...).
	 * @return string ja or en.
	 */
	public static function language( $locale ) {
		return 1 === preg_match( '/^ja(?:_|$)/iD', (string) $locale ) ? 'ja' : 'en';
	}

	/**
	 * Returns the fixed text of a check.
	 *
	 * @param string $check_id Check ID.
	 * @param string $language ja or en.
	 * @return array{title: string, detail: string, impact: string, fix: string[]}|null Null for a check without one.
	 */
	public static function check( $check_id, $language ) {
		return self::CHECKS[ $check_id ][ $language ] ?? null;
	}

	/**
	 * Returns a text, formatted with sprintf() when arguments are given.
	 *
	 * @param string $key      Key of TEXTS.
	 * @param string $language ja or en.
	 * @param array  $args     sprintf() arguments.
	 * @return string
	 */
	public static function text( $key, $language, array $args = array() ) {
		$text = self::TEXTS[ $language ][ $key ];
		return array() === $args ? $text : vsprintf( $text, $args );
	}

	/**
	 * Returns the impact of a vulnerability of a severity.
	 *
	 * @param string $severity critical, high, medium or low.
	 * @param string $language ja or en.
	 * @return string
	 */
	public static function vulnerability_impact( $severity, $language ) {
		return self::TEXTS[ $language ]['vuln_impact'][ $severity ];
	}
}
