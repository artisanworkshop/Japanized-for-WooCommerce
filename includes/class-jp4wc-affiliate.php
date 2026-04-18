<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.3.2
 * @package     Affiliate Setting
 * @author      ArtisanWorkshop
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class JP4WC_Affiliates {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$currency = get_woocommerce_currency();
		// A8.net tracking settings
		if ( get_option( 'wc4jp-affiliate-a8' ) && $currency == 'JPY' ) {
			add_action( 'wp_head', array( $this, 'jp4wc_a8_js' ), 10 );
			// Show on order detail at thanks page (frontend)
			add_action( 'woocommerce_before_thankyou', array( $this, 'jp4wc_a8_thankyou' ) );
		}
		// felmat tracking settings
		if ( get_option( 'wc4jp-affiliate-felmat' ) && $currency == 'JPY' ) {
			add_action( 'wp_head', array( $this, 'jp4wc_felmat_js' ), 10 );
			// Show on order detail at thanks page (frontend)
			add_action( 'woocommerce_before_thankyou', array( $this, 'jp4wc_felmat_thankyou' ) );
		}
	}

	/**
	 * Add A8.net tracking javascript
	 */
	public function jp4wc_a8_js() {
		?>
<script src="//statics.a8.net/a8sales/a8sales.js"></script>
		<?php
	}

	/**
	 * Display A8 affiliate conversion tag
	 *
	 * @throws
	 * @param int Order ID
	 */
	function jp4wc_a8_thankyou( $order_id ) {
		$order       = wc_get_order( $order_id );
		$pid         = ( get_option( 'wc4jp-affiliate-a8-test' ) ) ? 's00000000062001' : get_option( 'wc4jp-affiliate-a8-pid' );
		$items_data  = array();
		$total_price = 0;
		foreach ( $order->get_items() as $item ) {
			$product_variation_id = $item->get_variation_id();
			$product              = $item->get_product();
			if ( $product_variation_id != 0 ) {
				$product_id = $product_variation_id;
			} elseif ( $product->get_sku() ) {
				$product_id = $product->get_sku();
			} else {
				$product_id = $item->get_product_id();
			}
			$items_data[] = array(
				'code'     => (string) $product_id,
				'price'    => (int) round( $item->get_subtotal() ),
				'quantity' => 1,
			);
			$total_price += round( $item->get_subtotal() );
		}
		$payload = array(
			'pid'          => (string) $pid,
			'order_number' => (string) $order->get_order_number(),
			'currency'     => 'JPY',
			'items'        => $items_data,
			'total_price'  => (int) $total_price,
		);
		echo '<span id="a8sales"></span>
<script src="//statics.a8.net/a8sales/a8sales.js"></script>
<script>a8sales(' . wp_json_encode( $payload ) . ');</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode handles escaping
	}

	/**
	 * Add A8.net tracking javascript
	 */
	public function jp4wc_felmat_js() {
		?>
		<script type="text/javascript" src="https://js.crossees.com/csslp.js" async></script>
		<?php
	}
	/**
	 * Display felmat affiliate conversion tag
	 *
	 * @throws
	 * @param int Order ID
	 */
	/**
	 * Display felmat affiliate conversion tag.
	 *
	 * @param int $order_id Order ID.
	 */
	public function jp4wc_felmat_thankyou( $order_id ) {
		$order     = wc_get_order( $order_id );
		$item_line = '';
		foreach ( $order->get_items() as $item ) {
			$product_variation_id = $item->get_variation_id();
			$product              = $item->get_product();
			if ( 0 !== $product_variation_id ) {
				$product_id = $product_variation_id;
			} elseif ( $product->get_sku() ) {
				$product_id = $product->get_sku();
			} else {
				$product_id = $item->get_product_id();
			}
			$product_price = $item->get_subtotal() / $item->get_quantity();
			$item_line    .= rawurlencode( (string) $product_id ) . '.' . absint( $item->get_quantity() ) . '.' . (int) round( $product_price ) . ':';
		}
		$item_line = rtrim( $item_line, ':' );
		$pid       = get_option( 'wc4jp-affiliate-felmat-pid' );
		$src       = 'https://js.felmat.net/fmcv.js?adid=' . rawurlencode( (string) $pid ) . '&uqid=' . absint( $order->get_id() ) . '&item=' . $item_line;
		echo '<script type="text/javascript" src="' . esc_url( $src ) . '"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- third-party conversion tag requires inline output
	}
}

new JP4WC_Affiliates();
