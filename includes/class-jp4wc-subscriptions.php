<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.6.0
 * @package     Japanized_For_WooCommerce
 * @category    WooCommerce Subscriptions for Japan
 * @author      Artisan Workshop
 */

use ArtisanWorkshop\PluginFramework\v2_0_14 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * JP4WC_Subscriptions Class.
 *
 * Handles WooCommerce subscription functionality for Japanese market.
 *
 * @since 2.7.1
 */
class JP4WC_Subscriptions {

	/**
	 * Japanized for WooCommerce Framework.
	 *
	 * @var object
	 */
	public $jp4wc_plugin;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var string
	 */
	public $prefix;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->jp4wc_plugin = new Framework\JP4WC_Framework();
		$this->prefix       = 'wc4jp-';
		// Add subscription pricing fields on edit product page.
		add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'jp4wc_subscription_price_string' ), 10, 2 );
	}

	/**
	 * Display of price in product display of subscription
	 *
	 * @param string $subscription_string Subscription price string.
	 * @param object $product Product object.
	 * @return mixed
	 */
	public function jp4wc_subscription_price_string( string $subscription_string, $product ) {
		$price_string = $product->get_meta( '_subscription_price_string', true );
		if ( $price_string ) {
			return $price_string;
		}
		return $subscription_string;
	}
}

if ( class_exists( 'WC_Subscriptions' ) ) {
	new JP4WC_Subscriptions();
}
