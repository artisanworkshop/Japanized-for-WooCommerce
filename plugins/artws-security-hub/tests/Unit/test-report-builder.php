<?php
/**
 * Tests for Artws_Sec_Report_Builder and Artws_Sec_Report_Templates.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Report_Builder_Test
 *
 * Builds reports from the contract's sample request against the contract's
 * vulnerability samples, the same fixtures the JP4WC client tests with.
 */
class Artws_Sec_Report_Builder_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * A scan token that fits the contract.
	 */
	const SCAN_TOKEN = 'scn_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

	/**
	 * Unix time of the reports.
	 */
	const NOW = 1792459800;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		self::publish_contract_feed();
	}

	/**
	 * Builds a report from the sample request with some keys replaced.
	 *
	 * @param array $overrides Top-level keys of the request to replace.
	 * @return array
	 */
	private static function build( array $overrides = array() ) {
		$request = Artws_Sec_Scan_Request::normalize( self::sample_request( $overrides ) );
		self::assertIsArray( $request );
		return Artws_Sec_Report_Builder::build( $request, Artws_Sec_Feed_Updater::get_state(), Artws_Sec_Feed_Updater::get_eol(), self::SCAN_TOKEN, self::NOW );
	}

	/**
	 * Returns the findings of a check.
	 *
	 * @param array  $report   Report.
	 * @param string $check_id Check ID.
	 * @return array
	 */
	private static function findings_of( array $report, $check_id ) {
		return array_values( array_filter( $report['findings'], static fn( $f ) => $check_id === $f['check_id'] ) );
	}

	/**
	 * Returns a software item for the request.
	 *
	 * @param string $slug    Slug.
	 * @param string $version Version.
	 * @param string $type    plugin or theme.
	 * @return array
	 */
	private static function software( $slug, $version, $type = 'plugin' ) {
		return array(
			'type'    => $type,
			'slug'    => $slug,
			'version' => $version,
			'active'  => true,
		);
	}

	/**
	 * The report of the contract's sample request matches the report schema.
	 */
	public function test_sample_report_matches_the_schema() {
		$report = self::build();

		$this->assert_matches_schema( json_decode( wp_json_encode( $report ) ), 'report' );
		$this->assertSame( self::SCAN_TOKEN, $report['token'] );
		$this->assertSame( gmdate( 'Y-m-d\TH:i:s\Z', self::NOW ), $report['generated_at'] );
		$this->assertSame( self::FEED_VERSION, $report['feed_version'] );
		$this->assertSame( 'Vulnerability data: Wordfence Intelligence', $report['attribution'] );
	}

	/**
	 * The sample site has Sample Plugin 1.2.3, which the first sample record affects.
	 */
	public function test_known_vulnerability_of_a_plugin() {
		$findings = self::findings_of( self::build(), 'V-01' );

		$this->assertCount( 1, $findings );
		$this->assertSame(
			array(
				'check_id'    => 'V-01',
				'severity'    => 'high',
				'requirement' => 'vuln',
				'title'       => 'プラグイン sample-plugin 1.2.3 に既知の脆弱性があります',
				'detail'      => 'Sample Plugin <= 1.2.3 - Cross-Site Scripting (fixture)（CVSS 7.2 High）',
				'impact'      => 'サイトの改ざんや情報の漏えいにつながるおそれがあります。',
				'fix'         => array(
					'「プラグイン」画面で sample-plugin を 1.2.4 以上に更新してください。',
					'更新後に、もう一度診断を実行して脆弱性が残っていないことを確かめてください。',
				),
				'reference'   => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/00000000-0000-4000-8000-000000000001',
				'software'    => array(
					'type'    => 'plugin',
					'slug'    => 'sample-plugin',
					'version' => '1.2.3',
				),
			),
			$findings[0]
		);
	}

	/**
	 * Installed versions and the vulnerabilities they get, with the version to update to.
	 *
	 * @return array<string, array{string, string, string|null}>
	 */
	public function version_provider() {
		return array(
			'below the first range'           => array( 'sample-multi-range', '0.9', null ),
			'start of the first range'        => array( 'sample-multi-range', '1.0', '1.4.10' ),
			'end of the first range'          => array( 'sample-multi-range', '1.4.9', '1.4.10' ),
			'patched in the first branch'     => array( 'sample-multi-range', '1.4.10', null ),
			'exclusive start of the second'   => array( 'sample-multi-range', '1.5.0', null ),
			'in the second range'             => array( 'sample-multi-range', '1.5.2', '1.5.4' ),
			'patched in the second branch'    => array( 'sample-multi-range', '1.5.4', null ),
			'exclusive end of a range'        => array( 'sample-plugin', '2.0.5', null ),
			'inclusive start of a range'      => array( 'sample-plugin', '2.0.0', '2.0.5' ),
			'no patch'                        => array( 'sample-plugin-unpatched', '9.9', '' ),
			'no Version header is not looked' => array( 'sample-plugin-unpatched', '', null ),
		);
	}

	/**
	 * Matching follows the contract's range rule, and the fix names the lowest patched version that is not affected.
	 *
	 * @dataProvider version_provider
	 *
	 * @param string      $slug    Slug.
	 * @param string      $version Installed version.
	 * @param string|null $fixed   Version to update to, '' for no patch, null for no finding.
	 */
	public function test_vulnerability_ranges( $slug, $version, $fixed ) {
		$findings = self::findings_of( self::build( array( 'software' => array( self::software( $slug, $version ) ) ) ), 'V-01' );

		if ( null === $fixed ) {
			$this->assertSame( array(), $findings );
			return;
		}
		$this->assertCount( 1, $findings );
		if ( '' === $fixed ) {
			$this->assertSame( array( "修正版はまだ公開されていません。{$slug} を停止して削除し、代わりのプラグインを検討してください。" ), $findings[0]['fix'] );
		} else {
			$this->assertSame( "「プラグイン」画面で {$slug} を {$fixed} 以上に更新してください。", $findings[0]['fix'][0] );
		}
	}

	/**
	 * The range check is the client's: the same answers as JP4WC_Security_Scanner::is_affected().
	 */
	public function test_range_check_matches_the_client() {
		require_once dirname( ARTWS_SEC_CONTRACT_DIR, 3 ) . '/includes/class-jp4wc-security-scanner.php';
		$this->assertTrue( class_exists( 'JP4WC_Security_Scanner', false ) );

		$ranges = array(
			array(
				array(
					'from_version'   => '*',
					'from_inclusive' => true,
					'to_version'     => '1.2.3',
					'to_inclusive'   => true,
				),
			),
			array(
				array(
					'from_version'   => '1.0',
					'from_inclusive' => false,
					'to_version'     => '2.0',
					'to_inclusive'   => false,
				),
				array(
					'from_version'   => '3.0',
					'from_inclusive' => true,
					'to_version'     => '*',
					'to_inclusive'   => true,
				),
			),
		);
		foreach ( $ranges as $range ) {
			foreach ( array( '', '0.1', '1.0', '1.0.1', '1.2.3', '1.2.4', '2.0', '2.0-beta', '3.0', '10.0', 'abc' ) as $version ) {
				$this->assertSame(
					JP4WC_Security_Scanner::is_affected( $version, $range ),
					Artws_Sec_Vuln_Index::is_affected( $version, $range ),
					"Version '$version'"
				);
			}
		}
	}

	/**
	 * Themes and WordPress itself are matched too; a record without CVSS counts as medium.
	 */
	public function test_theme_and_core_vulnerabilities() {
		$report   = self::build(
			array(
				'wp_ver'   => '6.5.2',
				'software' => array( self::software( 'sample-theme', '3.0.9', 'theme' ) ),
			)
		);
		$findings = self::findings_of( $report, 'V-01' );

		$this->assertCount( 2, $findings );
		$by_type = array_column( $findings, null, 'title' );
		$theme   = $by_type['テーマ sample-theme 3.0.9 に既知の脆弱性があります'];
		$this->assertSame( 'medium', $theme['severity'] );
		$this->assertSame( 'Sample Theme < 3.1.0 - Reflected Cross-Site Scripting (fixture)', $theme['detail'] );
		$this->assertSame( '「外観」>「テーマ」で sample-theme を 3.1.0 以上に更新してください。', $theme['fix'][0] );

		$core = $by_type['WordPress 6.5.2 に既知の脆弱性があります'];
		$this->assertSame(
			array(
				'type'    => 'core',
				'slug'    => 'wordpress', // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- The core slug.
				'version' => '6.5.2',
			),
			$core['software']
		);
		$this->assertSame( '「ダッシュボード」>「更新」で WordPress を 6.5.5 以上に更新してください。', $core['fix'][0] );
		$this->assert_matches_schema( json_decode( wp_json_encode( $report ) ), 'report' );
	}

	/**
	 * The server decides V-01 and V-03; what the site reported for them is not used.
	 */
	public function test_reported_v01_and_v03_are_ignored() {
		$report = self::build(
			array(
				'software'       => array( self::software( 'sample-plugin', '1.2.4' ) ),
				'local_findings' => array(
					array(
						'check_id' => 'V-01',
						'status'   => 'fail',
					),
					array(
						'check_id' => 'V-03',
						'status'   => 'fail',
					),
				),
			)
		);

		$this->assertSame( array(), $report['findings'] );
		$this->assertSame( 100, $report['score']['total'] );
	}

	/**
	 * V-03 names each version older than the oldest supported one.
	 */
	public function test_unsupported_versions() {
		$report   = self::build(
			array(
				'php_ver' => '8.1.2',
				'wc_ver'  => '9.9.5',
			)
		);
		$findings = self::findings_of( $report, 'V-03' );

		$this->assertCount( 1, $findings );
		$this->assertSame( 'high', $findings[0]['severity'] );
		$this->assertSame( '次のバージョンはサポートが終了しています: PHP 8.1.2（サポート対象は 8.2 以上）、WooCommerce 9.9.5（サポート対象は 10.2 以上）。', $findings[0]['detail'] );
		$this->assertSame(
			array(
				'サポート中の PHP のバージョンへの切り替え方法をサーバー会社に確認してください。',
				'「プラグイン」画面で WooCommerce を更新してください。',
			),
			$findings[0]['fix']
		);
		// V-01 (sample-plugin, High) and V-03 (high) take 15 points each.
		$this->assertSame( 70, $report['score']['vuln'] );
	}

	/**
	 * The score is the Security tab's: 25 / 15 / 8 / 3 points per failed check, V-01 once.
	 */
	public function test_score_of_the_sample() {
		$report = self::build();

		// V-01 fails (sample-plugin, High) for 15 and M-01 (low) for 3; A-04 is unknown and costs nothing.
		$this->assertSame(
			array(
				'total'   => 85,
				'vuln'    => 85,
				'admin'   => 100,
				'data'    => 100,
				'malware' => 97,
			),
			$report['score']
		);
	}

	/**
	 * Several vulnerabilities count once against the score, at the most severe one's points.
	 */
	public function test_vulnerabilities_count_once() {
		$report = self::build(
			array(
				'software'       => array(
					self::software( 'sample-plugin', '1.2.3' ),
					self::software( 'sample-multi-range', '1.0' ),
					self::software( 'sample-plugin-unpatched', '1.0' ),
				),
				'local_findings' => array(),
			)
		);

		$this->assertCount( 3, self::findings_of( $report, 'V-01' ) );
		$this->assertSame( 75, $report['score']['vuln'] );
	}

	/**
	 * Every failed check of a requirement counts, and the total is the lowest requirement.
	 */
	public function test_score_of_every_data_check_failing() {
		$findings = array();
		foreach ( array( 'D-01', 'D-02', 'D-03', 'D-04', 'D-05' ) as $check_id ) {
			$findings[] = array(
				'check_id' => $check_id,
				'status'   => 'fail',
			);
		}
		$report = self::build( array( 'local_findings' => $findings ) );

		// 15 + 25 + 8 + 15 + 25.
		$this->assertSame( 12, $report['score']['data'] );
		$this->assertSame( 12, $report['score']['total'] );
	}

	/**
	 * Combinations of CVSS ratings (null for a record without one).
	 *
	 * @return array<string, array{array}>
	 */
	public function rating_provider() {
		return array(
			'one Low and one unrated'     => array( array( 'Low', null ) ),
			'only unrated'                => array( array( null, null ) ),
			'None'                        => array( array( 'None' ) ),
			'High and Critical'           => array( array( 'High', 'Critical' ) ),
			'Medium, unrated and Low'     => array( array( 'Medium', null, 'Low' ) ),
			'unrated and High'            => array( array( null, 'High' ) ),
			'one Medium'                  => array( array( 'Medium' ) ),
			'unknown rating and one None' => array( array( 'Severe', 'None' ) ),
		);
	}

	/**
	 * V-01 takes the severity the Security tab gives it for the same records.
	 *
	 * @dataProvider rating_provider
	 *
	 * @param array $ratings CVSS ratings of the records found.
	 */
	public function test_vulnerability_severity_matches_the_client( array $ratings ) {
		require_once dirname( ARTWS_SEC_CONTRACT_DIR, 3 ) . '/includes/class-jp4wc-security-checks.php';

		$records         = array();
		$vulnerabilities = array();
		foreach ( $ratings as $rating ) {
			$records[]         = null === $rating ? array( 'cvss' => null ) : array(
				'cvss' => array(
					'score'  => 5.0,
					'rating' => $rating,
				),
			);
			$vulnerabilities[] = array(
				'rating' => null === $rating ? '' : $rating,
				'score'  => null === $rating ? null : 5.0,
			);
		}
		$client = JP4WC_Security_Checks::run_check(
			'V-01',
			array(
				'vulnerabilities' => array(
					'items' => array( array( 'vulnerabilities' => $vulnerabilities ) ),
					'stale' => false,
				),
			)
		);

		$this->assertSame( $client['severity'], Artws_Sec_Report_Builder::vulnerability_severity( $records ) );
	}

	/**
	 * The checks, severities and points are those of the client's JP4WC_Security_Checks.
	 */
	public function test_checks_match_the_client() {
		require_once dirname( ARTWS_SEC_CONTRACT_DIR, 3 ) . '/includes/class-jp4wc-security-checks.php';
		$this->assertTrue( class_exists( 'JP4WC_Security_Checks', false ) );

		$client = array();
		foreach ( JP4WC_Security_Checks::CHECKS as $check_id => $check ) {
			$client[ $check_id ] = array( $check['requirement'], $check['severity'] );
		}
		$this->assertSame( $client, Artws_Sec_Report_Builder::CHECKS );
		$this->assertSame( JP4WC_Security_Checks::POINTS, Artws_Sec_Report_Builder::POINTS );
		$this->assertSame( JP4WC_Security_Checks::REQUIREMENTS, Artws_Sec_Report_Builder::REQUIREMENTS );
	}

	/**
	 * Every check the site reports has a text in both languages; the built texts have both too.
	 */
	public function test_templates_cover_every_check() {
		foreach ( array_keys( Artws_Sec_Report_Builder::CHECKS ) as $check_id ) {
			foreach ( Artws_Sec_Report_Templates::LANGUAGES as $language ) {
				$text = Artws_Sec_Report_Templates::check( $check_id, $language );
				if ( in_array( $check_id, Artws_Sec_Report_Builder::SERVER_CHECKS, true ) ) {
					$this->assertNull( $text, $check_id );
					continue;
				}
				$this->assertIsArray( $text, "$check_id $language" );
				foreach ( array( 'title', 'detail', 'impact' ) as $key ) {
					$this->assertNotSame( '', $text[ $key ], "$check_id $language $key" );
				}
				$this->assertNotEmpty( $text['fix'] );
			}
		}
		$this->assertSame( array_keys( Artws_Sec_Report_Templates::TEXTS['ja'] ), array_keys( Artws_Sec_Report_Templates::TEXTS['en'] ) );
	}

	/**
	 * Every failed check becomes a finding that fits the schema, in Japanese and English.
	 */
	public function test_every_check_fits_the_schema() {
		$findings = array();
		foreach ( array_keys( Artws_Sec_Report_Builder::CHECKS ) as $check_id ) {
			$findings[] = array(
				'check_id' => $check_id,
				'status'   => 'fail',
			);
		}
		foreach ( array( 'ja', 'en_US' ) as $locale ) {
			$report = self::build(
				array(
					'locale'         => $locale,
					'php_ver'        => '7.4.33',
					'software'       => array(
						self::software( 'sample-plugin', '1.2.3' ),
						self::software( 'sample-plugin-unpatched', '1.0' ),
						self::software( 'sample-theme', '1.0', 'theme' ),
					),
					'wp_ver'         => '6.4.2',
					'local_findings' => $findings,
				)
			);

			$this->assertEqualsCanonicalizing( array_keys( Artws_Sec_Report_Builder::CHECKS ), array_values( array_unique( array_column( $report['findings'], 'check_id' ) ) ) );
			$this->assert_matches_schema( json_decode( wp_json_encode( $report ) ), 'report' );
		}
	}

	/**
	 * The report is in Japanese for a ja locale and in English otherwise.
	 */
	public function test_language() {
		$this->assertSame( 'ja', Artws_Sec_Report_Templates::language( 'ja' ) );
		$this->assertSame( 'ja', Artws_Sec_Report_Templates::language( 'ja_JP' ) );
		$this->assertSame( 'en', Artws_Sec_Report_Templates::language( 'en_US' ) );
		$this->assertSame( 'en', Artws_Sec_Report_Templates::language( 'jam' ) );

		$report = self::build( array( 'locale' => 'en_US' ) );
		$this->assertSame( 'The plugin sample-plugin 1.2.3 has a known vulnerability', self::findings_of( $report, 'V-01' )[0]['title'] );
		$this->assertSame( 'File editing in the admin screens is not turned off', self::findings_of( $report, 'M-01' )[0]['title'] );
	}

	/**
	 * Findings are ordered by severity, then by check, then by CVSS score.
	 */
	public function test_order() {
		$report = self::build(
			array(
				'wp_ver'         => '6.4.2',
				'software'       => array(
					self::software( 'sample-plugin', '1.2.3' ),
					self::software( 'sample-multi-range', '1.0' ),
					self::software( 'sample-plugin-unpatched', '1.0' ),
				),
				'local_findings' => array(
					array(
						'check_id' => 'M-01',
						'status'   => 'fail',
					),
					array(
						'check_id' => 'A-03',
						'status'   => 'fail',
					),
					array(
						'check_id' => 'D-02',
						'status'   => 'fail',
					),
				),
			)
		);

		$this->assertSame(
			array(
				'critical V-01 sample-multi-range',
				'critical D-02 ',
				'high V-01 sample-plugin-unpatched',
				'high V-01 sample-plugin',
				'high V-03 ',
				'high A-03 ',
				'medium V-01 wordpress', // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- The core slug.
				'low M-01 ',
			),
			array_map( static fn( $f ) => $f['severity'] . ' ' . $f['check_id'] . ' ' . ( $f['software']['slug'] ?? '' ), $report['findings'] )
		);
	}

	/**
	 * A check reported twice takes its worst status; checks the server does not know are skipped.
	 */
	public function test_reported_statuses() {
		$report = self::build(
			array(
				'local_findings' => array(
					array(
						'check_id' => 'A-01',
						'status'   => 'pass',
					),
					array(
						'check_id' => 'A-01',
						'status'   => 'fail',
					),
					array(
						'check_id' => 'A-01',
						'status'   => 'unknown',
					),
					array(
						'check_id' => 'A-99',
						'status'   => 'fail',
					),
				),
			)
		);

		$this->assertSame( array( 'V-01', 'A-01' ), array_column( $report['findings'], 'check_id' ) );
	}

	/**
	 * More than 500 findings are cut to the 500 most urgent.
	 */
	public function test_findings_are_capped() {
		$records = array();
		for ( $i = 0; $i < 520; $i++ ) {
			$records[] = wp_json_encode(
				array(
					'id'                => sprintf( '30000000-0000-4000-8000-%012d', $i ),
					'title'             => "Many Plugin record $i",
					'software'          => array(
						'type' => 'plugin',
						'slug' => 'many-plugin',
					),
					'affected_versions' => array(
						array(
							'from_version'   => '*',
							'from_inclusive' => true,
							'to_version'     => '*',
							'to_inclusive'   => true,
						),
					),
					'patched'           => false,
					'patched_versions'  => array(),
					'remediation'       => '',
					'cvss'              => array(
						'score'  => 0 === $i % 2 ? 9.8 : 5.0,
						'rating' => 0 === $i % 2 ? 'Critical' : 'Medium',
					),
					'published'         => null,
					'reference'         => sprintf( 'https://www.wordfence.com/threat-intel/vulnerabilities/id/30000000-0000-4000-8000-%012d', $i ),
				)
			);
		}
		Artws_Sec_Vuln_Index::write(
			array(
				'plugin' => array( 'many-plugin' => $records ),
				'core'   => array( 'wordpress' => array( '{}' ) ), // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- The core slug.
			),
			'many'
		);
		$state                  = Artws_Sec_Feed_Updater::get_state();
		$state['index_version'] = 'many';
		$request                = Artws_Sec_Scan_Request::normalize( self::sample_request( array( 'software' => array( self::software( 'many-plugin', '1.0' ) ) ) ) );

		$report = Artws_Sec_Report_Builder::build( $request, $state, Artws_Sec_Feed_Updater::get_eol(), self::SCAN_TOKEN, self::NOW );

		$this->assertCount( 500, $report['findings'] );
		$severities = array_count_values( array_column( $report['findings'], 'severity' ) );
		$this->assertSame( 260, $severities['critical'] );
		// M-01 (low) is cut, since 240 medium vulnerabilities fill the rest.
		$this->assertSame( array( 'critical', 'medium' ), array_keys( $severities ) );
		$this->assert_matches_schema( json_decode( wp_json_encode( $report ) ), 'report' );
	}

	/**
	 * Next steps without findings.
	 */
	public function test_next_steps_without_findings() {
		$report = self::build(
			array(
				'software'       => array(),
				'local_findings' => array(),
			)
		);

		$this->assertSame(
			array(
				'要対応の項目は見つかりませんでした。',
				'対応が終わったら、もう一度診断を実行して結果を確かめてください（診断は1時間に1回まで実行できます）。',
			),
			$report['next_steps']
		);
	}

	/**
	 * A large store gets the urgency step when there are critical or high findings.
	 */
	public function test_next_steps_of_a_large_store() {
		$step = '取引の規模が大きいストアは攻撃の標的になりやすいため、重大度が「緊急」と「高」の項目はできるだけ早く対応してください。';

		$this->assertNotContains( $step, self::build()['next_steps'] );
		$this->assertContains(
			$step,
			self::build(
				array(
					'revenue_band' => 'B1',
					'order_band'   => 'O3',
				)
			)['next_steps']
		);
		$this->assertContains(
			$step,
			self::build(
				array(
					'revenue_band' => 'B4',
					'order_band'   => 'O0',
				)
			)['next_steps']
		);

		// Only the low M-01 left: nothing urgent.
		$report = self::build(
			array(
				'revenue_band' => 'B5',
				'order_band'   => 'O4',
				'software'     => array(),
			)
		);
		$this->assertNotContains( $step, $report['next_steps'] );
	}

	/**
	 * Unknown checks are listed; stale data turns "no vulnerability found" into unknown.
	 */
	public function test_next_steps_with_unknown_checks_and_stale_data() {
		self::publish_contract_feed( true );

		$report = self::build( array( 'software' => array() ) );

		$this->assertSame(
			array(
				'要対応の項目は優先順に並んでいます。上から順に対応してください。',
				'確認できなかった項目が 2 件あります（V-01, A-04）。JP4WC の「セキュリティ」タブで、もう一度チェックしてください。',
				'脆弱性データの更新が遅れているため、最近公開された脆弱性が含まれていない可能性があります。',
				'対応が終わったら、もう一度診断を実行して結果を確かめてください（診断は1時間に1回まで実行できます）。',
			),
			$report['next_steps']
		);
		$this->assertSame( 100, $report['score']['vuln'] );
	}

	/**
	 * An index that has not caught up with the feed for longer than the stale limit is stale data.
	 */
	public function test_index_behind_the_feed() {
		$state            = Artws_Sec_Feed_Updater::get_state();
		$state['version'] = '20261023.031500';
		$written          = gmmktime( 3, 15, 0, 10, 20, 2026 );
		$request          = Artws_Sec_Scan_Request::normalize( self::sample_request( array( 'software' => array() ) ) );
		$step             = '脆弱性データの更新が遅れているため、最近公開された脆弱性が含まれていない可能性があります。';

		$recent = Artws_Sec_Report_Builder::build( $request, $state, Artws_Sec_Feed_Updater::get_eol(), self::SCAN_TOKEN, $written + 2 * DAY_IN_SECONDS );
		$this->assertNotContains( $step, $recent['next_steps'] );
		$this->assertSame( self::FEED_VERSION, $recent['feed_version'] );

		$behind = Artws_Sec_Report_Builder::build( $request, $state, Artws_Sec_Feed_Updater::get_eol(), self::SCAN_TOKEN, $written + 2 * DAY_IN_SECONDS + 1 );
		$this->assertContains( $step, $behind['next_steps'] );
		$this->assertContains( '確認できなかった項目が 2 件あります（V-01, A-04）。JP4WC の「セキュリティ」タブで、もう一度チェックしてください。', $behind['next_steps'] );

		$this->assertFalse(
			Artws_Sec_Report_Builder::is_stale(
				array(
					'version'       => 'x',
					'index_version' => 'x',
					'stale'         => false,
				),
				PHP_INT_MAX
			)
		);
		$this->assertTrue(
			Artws_Sec_Report_Builder::is_stale(
				array(
					'version'       => 'x',
					'index_version' => 'not-a-time',
					'stale'         => false,
				),
				0
			)
		);
		$this->assertTrue(
			Artws_Sec_Report_Builder::is_stale(
				array(
					'version'       => 'x',
					'index_version' => 'x',
					'stale'         => true,
				),
				0
			)
		);
	}

	/**
	 * Vulnerabilities found in stale data still count.
	 */
	public function test_stale_data_still_finds_vulnerabilities() {
		self::publish_contract_feed( true );

		$this->assertCount( 1, self::findings_of( self::build(), 'V-01' ) );
	}
}
