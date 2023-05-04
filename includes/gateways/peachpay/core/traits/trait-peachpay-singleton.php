<?php
/**
 * PeachPay singleton trait.
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

trait PeachPay_Singleton {
	/**
	 * The single instance of the class.
	 *
	 * @var mixed $instance The core singleton instance.
	 */
	private static $instance = null;


	/**
	 * Get a singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Cloning is forbidden', 'peachpay-for-woocommerce' ),
			'1.0.0'
		);
	}

	/**
	 * Deserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Deserializing instances of this class is forbidden', 'peachpay-for-woocommerce' ),
			'1.0.0'
		);
	}
}
