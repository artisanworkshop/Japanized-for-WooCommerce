<?php
/**
 * Tests that the block checkout support is registered once, whichever plugin loads first.
 *
 * WooCommerce fires woocommerce_blocks_loaded from its own plugins_loaded 10
 * callback. When that callback runs before this plugin's (WooCommerce
 * network-activated on a multisite, and this test suite, whose bootstrap loads
 * WooCommerce first), the action has already fired when JP4WC::init() runs, and
 * the payment method blocks and checkout fields used to be missing (#320).
 *
 * @package Japanized_For_WooCommerce
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * JP4WC_Blocks_Support_Test
 */
class JP4WC_Blocks_Support_Test extends WP_UnitTestCase {

	/**
	 * Payment method block names and the options that enable them.
	 */
	const PAYMENT_OPTIONS = array(
		'postofficebank' => 'wc4jp-postofficebank',
		'bankjp'         => 'wc4jp-bankjp',
		'atstore'        => 'wc4jp-atstore',
		'cod2'           => 'wc4jp-cod2',
	);

	/**
	 * The hooks jp4wc_blocks_support() adds, with each callback and priority.
	 *
	 * @return array
	 */
	private function callbacks() {
		return array(
			'woocommerce_blocks_payment_method_type_registration' => array( array( 'JP4WC', 'jp4wc_register_payment_method_types' ), 10 ),
			'woocommerce_init' => array( array( 'JP4WC', 'jp4wc_register_checkout_fields' ), 5 ),
			'woocommerce_blocks_checkout_block_registration' => array( array( 'JP4WC', 'jp4wc_register_checkout_block_integrations' ), 10 ),
		);
	}

	/**
	 * Count the callbacks of a hook that belong to a class: its methods and the closures defined in it.
	 *
	 * @param string $hook       Hook name.
	 * @param string $class_name Class name.
	 * @return int
	 */
	private function count_callbacks( $hook, $class_name ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if ( $function instanceof Closure ) {
					$scope = ( new ReflectionFunction( $function ) )->getClosureScopeClass();
					$owner = $scope ? $scope->getName() : '';
				} elseif ( is_array( $function ) ) {
					$owner = is_object( $function[0] ) ? get_class( $function[0] ) : $function[0];
				} else {
					$owner = '';
				}
				if ( $class_name === $owner ) {
					++$count;
				}
			}
		}
		return $count;
	}

	/**
	 * Enable or disable the four payment gateways.
	 *
	 * @param string $value Option value, '1' or ''.
	 */
	private function set_payment_options( $value ) {
		foreach ( self::PAYMENT_OPTIONS as $option ) {
			update_option( $option, $value );
		}
	}

	/**
	 * Fire the payment method registration on a new registry, as WooCommerce does at init 5.
	 *
	 * PaymentMethodRegistry::initialize() fires the same action and then initializes
	 * every registered method, which needs the gateway in WooCommerce's gateway list.
	 *
	 * @return PaymentMethodRegistry
	 */
	private function fire_payment_method_type_registration() {
		$registry = new PaymentMethodRegistry();
		do_action( 'woocommerce_blocks_payment_method_type_registration', $registry );
		return $registry;
	}

	/**
	 * Clean up the options.
	 */
	public function tear_down() {
		foreach ( self::PAYMENT_OPTIONS as $option ) {
			delete_option( $option );
		}
		parent::tear_down();
	}

	/**
	 * The bootstrap loads WooCommerce first, and jp4wc_blocks_support() still ran, once.
	 */
	public function test_blocks_support_ran_although_woocommerce_fired_blocks_loaded_first() {
		$this->assertGreaterThan( 0, did_action( 'woocommerce_blocks_loaded' ) );

		foreach ( $this->callbacks() as $hook => list( $callback, $priority ) ) {
			$this->assertSame( $priority, has_action( $hook, $callback ), $hook );
		}
		// Called directly, and not also left on the action, which could fire again.
		$this->assertFalse( has_action( 'woocommerce_blocks_loaded', array( 'JP4WC', 'jp4wc_blocks_support' ) ) );
		// woocommerce_init (init 0) ran the checkout field registration during the bootstrap.
		$this->assertTrue( $GLOBALS['jp4wc_delivery_init_done'] ?? false );
		$this->assertTrue( $GLOBALS['jp4wc_yomigana_init_done'] ?? false );
	}

	/**
	 * A second call of jp4wc_blocks_support() registers nothing twice.
	 */
	public function test_a_second_call_adds_no_callbacks() {
		JP4WC::jp4wc_blocks_support();

		foreach ( array_keys( $this->callbacks() ) as $hook ) {
			$this->assertSame( 1, $this->count_callbacks( $hook, 'JP4WC' ), $hook );
		}

		// A payment method registered twice is a _doing_it_wrong() notice, which fails the test.
		$this->set_payment_options( '1' );
		$registry = $this->fire_payment_method_type_registration();
		foreach ( array_keys( self::PAYMENT_OPTIONS ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), $name );
		}
	}

	/**
	 * The payment method blocks of the enabled gateways are registered.
	 */
	public function test_payment_method_blocks_are_registered_for_enabled_gateways() {
		$this->set_payment_options( '1' );

		$registry = $this->fire_payment_method_type_registration();

		foreach ( array_keys( self::PAYMENT_OPTIONS ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), $name );
		}
	}

	/**
	 * The payment method blocks of disabled gateways are not registered.
	 */
	public function test_payment_method_blocks_are_not_registered_for_disabled_gateways() {
		$this->set_payment_options( '' );

		$registry = $this->fire_payment_method_type_registration();

		foreach ( array_keys( self::PAYMENT_OPTIONS ) as $name ) {
			$this->assertFalse( $registry->is_registered( $name ), $name );
		}
	}

	/**
	 * The Checkout block gets the delivery and yomigana integrations.
	 */
	public function test_checkout_block_integrations_are_registered() {
		// Fires woocommerce_blocks_checkout_block_registration, as the Checkout block type does.
		$registry = new IntegrationRegistry();
		$registry->initialize( 'checkout_block' );

		$this->assertTrue( $registry->is_registered( 'jp4wc-delivery' ) );
		$this->assertTrue( $registry->is_registered( 'jp4wc-yomigana' ) );
	}

	/**
	 * Before WooCommerce fires woocommerce_blocks_loaded (this plugin loaded first), it waits for the action.
	 */
	public function test_blocks_support_waits_for_blocks_loaded_when_it_has_not_fired() {
		// The test case restores the hooks and the action counts afterwards.
		unset( $GLOBALS['wp_actions']['woocommerce_blocks_loaded'] );
		foreach ( $this->callbacks() as $hook => list( $callback, $priority ) ) {
			remove_action( $hook, $callback, $priority );
		}

		( new ReflectionMethod( 'JP4WC', 'hook_blocks_support' ) )->invoke( null );

		$this->assertSame( 10, has_action( 'woocommerce_blocks_loaded', array( 'JP4WC', 'jp4wc_blocks_support' ) ) );
		$this->assertFalse( has_action( 'woocommerce_init', array( 'JP4WC', 'jp4wc_register_checkout_fields' ) ) );
	}
}
