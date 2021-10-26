<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.3.1
 * @package 	Affiliate Setting
 * @author 		ArtisanWorkshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Affiliates{
	
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$currency = get_woocommerce_currency();
		// A8.net tracking settings
		if( get_option( 'wc4jp-affiliate-a8' ) && $currency == 'JPY') {
			add_action( 'wp_head', array( $this, 'jp4wc_a8_js' ), 10 );
			// Show on order detail at thanks page (frontend)
			add_action( 'woocommerce_before_thankyou', array( $this, 'jp4wc_a8_thankyou' ) );
		}
	}

	// Add A8.net tracking javascript
	public function jp4wc_a8_js(){
		?>
<script src="//statics.a8.net/a8sales/a8sales.js"></script>
		<?php
	}

    /**
     * Display Delivery date select at Checkout
     *
     * @throws
     * @param int Order ID
     */
    function jp4wc_a8_thankyou( $order_id ){
		$order = wc_get_order( $order_id );
	    $pid = ( get_option( 'wc4jp-affiliate-a8-test' ) ) ? 's00000000062001':get_option( 'wc4jp-affiliate-a8-pid' );
	    $items = '';
	    $total_price = 0;
		foreach($order->get_items() as $item) {
			$product_variation_id = $item->get_variation_id();
			$product = $item->get_product();
            print_r($item);
			if ( $product_variation_id != 0 ) {
				$product_id = $product_variation_id;
			} elseif($product->get_sku()) {
				$product_id = $product->get_sku();
			} else {
				$product_id = $item->get_product_id();
			}
			$items .= '    {
      "code": "' . $product_id . '",
      "price": ' . round( $item->get_subtotal() ) . ',
      "quantity": 1
    },
';
			$total_price += round( $item->get_subtotal() );
		}
		echo '<span id="a8sales"></span>
<script src="//statics.a8.net/a8sales/a8sales.js"></script>
<script>
a8sales({
  "pid": "'. $pid .'",
  "order_number": "'. $order->get_order_number() .'",
  "currency": "JPY",
  "items": [
'. $items .'
  ],
   "total_price": '. $total_price .'
});
</script>';
	}
}

new JP4WC_Affiliates();
