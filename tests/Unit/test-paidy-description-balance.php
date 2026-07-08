<?php
/**
 * Tests for balancing the Paidy description HTML.
 *
 * The paidy_description setting is admin-editable HTML pre-filled with a
 * multi-line explanation (div + ul + li). wp_kses() does not fix unbalanced
 * tags, so a stray closing tag saved in this field escaped the payment box
 * when the classic checkout payment fragment was re-rendered via
 * update_order_review, leaving an orphaned place-order row behind on every
 * checkout update (duplicated order buttons). Covers the force_balance_tags()
 * fix on both the save path and the output path.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Description_Balance_Test
 */
class WC_Paidy_Description_Balance_Test extends WP_UnitTestCase {

	/**
	 * Gateway instance under test.
	 *
	 * @var WC_Gateway_Paidy
	 */
	private $gateway;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );
		$this->gateway = new WC_Gateway_Paidy();
	}

	/**
	 * A stray closing tag must be removed on save.
	 */
	public function test_validate_removes_stray_closing_tag() {
		$result = $this->gateway->validate_paidy_description_field(
			'paidy_description',
			'<div>Paidyで翌月払い</div></div>'
		);

		$this->assertSame( '<div>Paidyで翌月払い</div>', $result );
	}

	/**
	 * An unclosed tag must be closed on save.
	 */
	public function test_validate_closes_unclosed_tag() {
		$result = $this->gateway->validate_paidy_description_field(
			'paidy_description',
			'<div><ul><li>登録不要'
		);

		$this->assertSame( '<div><ul><li>登録不要</li></ul></div>', $result );
	}

	/**
	 * Disallowed tags must be stripped while allowed markup is kept.
	 */
	public function test_validate_strips_disallowed_tags() {
		$result = $this->gateway->validate_paidy_description_field(
			'paidy_description',
			'<div><script>alert(1)</script><strong>OK</strong></div>'
		);

		$this->assertStringNotContainsString( '<script', $result );
		$this->assertStringContainsString( '<strong>OK</strong>', $result );
	}

	/**
	 * The default explanation must survive validation unchanged in structure.
	 */
	public function test_validate_keeps_default_explanation_balanced() {
		$default = $this->gateway->get_form_fields()['paidy_description']['default'];

		$result = $this->gateway->validate_paidy_description_field( 'paidy_description', $default );

		$this->assertSame(
			substr_count( $result, '<div' ),
			substr_count( $result, '</div' ),
			'div tags should stay balanced'
		);
		$this->assertStringContainsString( '<ul>', $result );
		$this->assertSame(
			substr_count( $result, '<li' ),
			substr_count( $result, '</li' ),
			'li tags should stay balanced'
		);
	}

	/**
	 * payment_fields() must emit balanced HTML even when the saved setting
	 * already contains a stray closing tag (pre-fix data).
	 */
	public function test_payment_fields_output_is_balanced_with_broken_setting() {
		$this->gateway->paidy_description = '<div>Paidyで翌月払い</div></div>';

		ob_start();
		$this->gateway->payment_fields();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<div>Paidyで翌月払い</div>', $output );
		$this->assertSame(
			substr_count( $output, '<div' ),
			substr_count( $output, '</div' ),
			'payment_fields output must not leak unbalanced closing tags into the checkout fragment'
		);
	}

	/**
	 * payment_fields() must fall back to the built-in explanation when the
	 * setting is empty, and that output must be balanced too.
	 */
	public function test_payment_fields_default_output_is_balanced() {
		$this->gateway->paidy_description = '';

		ob_start();
		$this->gateway->payment_fields();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<ul>', $output );
		$this->assertSame(
			substr_count( $output, '<div' ),
			substr_count( $output, '</div' )
		);
	}
}
