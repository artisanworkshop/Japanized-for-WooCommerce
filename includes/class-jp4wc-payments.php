<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.2.19
 * @category    Payments setting for Japan
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Payments{

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		// Set Cash on Delivery icon for Japan.
		add_filter( 'woocommerce_cod_icon', array( $this, 'jp4wc_cod_icon'));
    }

    /**
     *
     */
    public function jp4wc_cod_icon(){
        return JP4WC_URL_PATH . '/assets/images/jp4wc-cash-on-delivery.png';
    }
}
// JP4WC_Payments Class load
new JP4WC_Payments();
