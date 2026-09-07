<?php
/**
 * Tests for the COD/COD2 fee Store API gateway-ID validation.
 *
 * The `jp4wc-add-gateway-fee` Store API extension endpoint is unauthenticated
 * by design (anonymous shoppers must be able to update their cart), so any
 * value it accepts for `gateway_id` is fully client-controlled. Before the
 * fix under test, a non-empty but bogus gateway_id was stored verbatim and
 * could suppress the merchant's configured COD/COD2 surcharge for an order
 * that ultimately completes with a real gateway (security review finding).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_COD_Fee_Handler_Gateway_Validation_Test
 */
class JP4WC_COD_Fee_Handler_Gateway_Validation_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_COD_Fee_Handler' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-cod-fee-handler.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_COD_Fee_Handler' ) );

		WC()->session->set( 'jp4wc_gateway_id', null );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		WC()->session->__unset( 'jp4wc_gateway_id' );
		delete_option( 'woocommerce_cod_settings' );
		WC()->payment_gateways()->init();
		parent::tearDown();
	}

	/**
	 * A gateway_id that does not match any currently available payment
	 * gateway must not be stored — it must be treated the same as an empty
	 * value (session key cleared).
	 */
	public function test_bogus_gateway_id_is_not_stored() {
		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'this-gateway-does-not-exist',
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	/**
	 * A gateway_id matching a real, currently available gateway is stored
	 * as before. Enable WooCommerce core's built-in "cod" gateway so at
	 * least one gateway is actually available regardless of test-env
	 * defaults.
	 */
	public function test_valid_gateway_id_is_stored() {
		$settings            = get_option( 'woocommerce_cod_settings', array() );
		$settings['enabled'] = 'yes';
		update_option( 'woocommerce_cod_settings', $settings );
		WC()->payment_gateways()->init();

		$available = WC()->payment_gateways->get_available_payment_gateways();
		$this->assertArrayHasKey( 'cod', $available, 'Test fixture expects WooCommerce core "cod" gateway to be available once enabled.' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'cod',
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	/**
	 * An empty gateway_id still clears the session key (unchanged behavior).
	 */
	public function test_empty_gateway_id_clears_session() {
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => '',
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
	}
}
