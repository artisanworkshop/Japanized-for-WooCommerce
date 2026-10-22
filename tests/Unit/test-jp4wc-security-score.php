<?php
/**
 * Tests for the score of the security check (issue #258).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Score_Test
 */
class JP4WC_Security_Score_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Checks' ), 'The plugin loads the local checks at plugins_loaded.' );
	}

	/**
	 * A check result.
	 *
	 * @param string $requirement Requirement.
	 * @param string $severity    Severity.
	 * @param string $status      Status.
	 * @return array
	 */
	private static function check( $requirement, $severity, $status = 'fail' ) {
		return array(
			'check_id'    => 'X-00',
			'requirement' => $requirement,
			'severity'    => $severity,
			'status'      => $status,
		);
	}

	/**
	 * Nothing failed: every score is 100.
	 */
	public function test_no_failure_scores_100() {
		$this->assertSame(
			array(
				'total'   => 100,
				'vuln'    => 100,
				'admin'   => 100,
				'data'    => 100,
				'malware' => 100,
			),
			JP4WC_Security_Checks::score( array( self::check( 'vuln', 'critical', 'pass' ) ) )
		);
		$this->assertSame( 100, JP4WC_Security_Checks::score( array() )['total'] );
	}

	/**
	 * Each severity takes its points off its own requirement.
	 */
	public function test_failures_take_points_by_severity() {
		$score = JP4WC_Security_Checks::score(
			array(
				self::check( 'vuln', 'critical' ),
				self::check( 'admin', 'high' ),
				self::check( 'data', 'medium' ),
				self::check( 'malware', 'low' ),
				self::check( 'malware', 'medium' ),
			)
		);

		$this->assertSame( 75, $score['vuln'] );
		$this->assertSame( 85, $score['admin'] );
		$this->assertSame( 92, $score['data'] );
		$this->assertSame( 89, $score['malware'] );
	}

	/**
	 * The total is the lowest requirement score.
	 */
	public function test_total_is_the_lowest_requirement() {
		$score = JP4WC_Security_Checks::score(
			array(
				self::check( 'admin', 'low' ),
				self::check( 'data', 'high' ),
				self::check( 'data', 'low' ),
			)
		);

		$this->assertSame( 82, $score['data'] );
		$this->assertSame( 97, $score['admin'] );
		$this->assertSame( 82, $score['total'] );
	}

	/**
	 * A score does not go below 0.
	 */
	public function test_score_stops_at_zero() {
		$score = JP4WC_Security_Checks::score( array_fill( 0, 5, self::check( 'data', 'critical' ) ) );

		$this->assertSame( 0, $score['data'] );
		$this->assertSame( 0, $score['total'] );
		$this->assertSame( 100, $score['vuln'] );
	}

	/**
	 * Unknown checks cost no points.
	 */
	public function test_unknown_costs_nothing() {
		$score = JP4WC_Security_Checks::score(
			array(
				self::check( 'admin', 'critical', 'unknown' ),
				self::check( 'admin', 'high', 'unknown' ),
				self::check( 'admin', 'low' ),
			)
		);

		$this->assertSame( 97, $score['admin'] );
	}

	/**
	 * Malformed entries are ignored.
	 */
	public function test_malformed_entries_are_ignored() {
		$score = JP4WC_Security_Checks::score(
			array(
				'fail',
				array( 'status' => 'fail' ),
				self::check( 'network', 'critical' ),
				self::check( 'data', 'severe' ),
				self::check( array( 'data' ), 'high' ),
				self::check( 'data', 'low', 'FAIL' ),
			)
		);

		$this->assertSame( 100, $score['total'] );
	}

	/**
	 * The worst case of the checks themselves, every check failing.
	 */
	public function test_every_check_failing() {
		$checks = array();
		foreach ( JP4WC_Security_Checks::CHECKS as $check_id => $check ) {
			$checks[] = array(
				'check_id'    => $check_id,
				'requirement' => $check['requirement'],
				'severity'    => $check['severity'],
				'status'      => 'fail',
			);
		}

		$this->assertSame(
			array(
				'total'   => 12,
				'vuln'    => 59,
				'admin'   => 58,
				'data'    => 12,
				'malware' => 74,
			),
			JP4WC_Security_Checks::score( $checks )
		);
	}
}
