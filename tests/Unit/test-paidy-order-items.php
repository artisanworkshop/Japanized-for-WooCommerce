<?php
/**
 * Tests for the order items WC_Gateway_Paidy::paidy_make_order() passes to Paidy Checkout.
 *
 * The receipt (order-pay) page prints a script that launches Paidy Checkout
 * with the order's line items, coupons and fees as `order.items`. That list
 * used to be built by string concatenation that always appended "}," even
 * when an entry was skipped, so an order with a zero-discount coupon (e.g. a
 * free-shipping coupon) or a line item without a product ID printed a script
 * with a syntax error and the customer could not pay with Paidy (issue #232).
 * Fee names were only HTML-escaped, so a newline or backslash in a fee name
 * broke the script too, and esc_js() showed "&" as "&amp;" on Paidy's screen.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Order_Items_Test
 */
class WC_Paidy_Order_Items_Test extends WP_UnitTestCase {

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

		// paidy_make_order() prints the checkout script only for an enabled
		// gateway with a public key and a pending order.
		$this->gateway                      = new WC_Gateway_Paidy();
		$this->gateway->enabled             = 'yes';
		$this->gateway->environment         = 'sandbox';
		$this->gateway->test_api_public_key = 'pk_test_dummy';

		wp_set_current_user( 0 );
	}

	/**
	 * Render the receipt page script for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string Printed output.
	 */
	private function render( $order ) {
		ob_start();
		$this->gateway->paidy_make_order( $order->get_id() );
		return (string) ob_get_clean();
	}

	/**
	 * Extract `order.items` from the printed script and decode it.
	 *
	 * The items are printed as one JSON array, which is also a valid
	 * JavaScript array literal; any stray "}," or broken string literal makes
	 * the decode throw.
	 *
	 * @param string $output Printed output.
	 * @return array Decoded items.
	 */
	private function decode_items( $output ) {
		$this->assertSame( 1, preg_match( '/"items":\s*(.*?),\s*"order_ref"/s', $output, $matches ), 'The script should contain order.items.' );

		return json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Create a pending order with one product line.
	 *
	 * @param string $name     Line item name.
	 * @param int    $quantity Quantity.
	 * @param float  $price    Unit price.
	 * @return array{0: WC_Order, 1: int} The order and the product ID.
	 */
	private function create_order_with_product( $name = 'Tea', $quantity = 2, $price = 1000 ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Tea' );
		$product->set_regular_price( (string) $price );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_product_id( $product->get_id() );
		$item->set_name( $name );
		$item->set_quantity( $quantity );
		$item->set_subtotal( (string) ( $price * $quantity ) );
		$item->set_total( (string) ( $price * $quantity ) );
		$order->add_item( $item );
		$order->set_status( 'pending' );
		$order->calculate_totals( false );
		$order->save();

		return array( $order, $product->get_id() );
	}

	/**
	 * A zero-discount coupon such as a free-shipping coupon is left out, and
	 * the items stay a valid array (the scenario in issue #232).
	 */
	public function test_zero_discount_coupon_is_left_out() {
		list( $order, $product_id ) = $this->create_order_with_product();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'freeship' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( true );
		$coupon->save();
		$this->assertTrue( $order->apply_coupon( 'freeship' ) );
		$this->assertCount( 1, $order->get_items( 'coupon' ) );

		$items = $this->decode_items( $this->render( wc_get_order( $order->get_id() ) ) );

		$this->assertSame(
			array(
				array(
					'id'         => (string) $product_id,
					'quantity'   => 2,
					'title'      => 'Tea',
					'unit_price' => 1000,
				),
			),
			$items
		);
	}

	/**
	 * A coupon with a discount is passed as a negative unit price, and the
	 * tax is still the order total less the items and shipping.
	 */
	public function test_coupon_with_discount_is_a_negative_item() {
		list( $order ) = $this->create_order_with_product();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'save300' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 300 );
		$coupon->save();
		$this->assertTrue( $order->apply_coupon( 'save300' ) );
		$order->set_total( 1870 );
		$order->save();

		$output = $this->render( wc_get_order( $order->get_id() ) );
		$items  = $this->decode_items( $output );

		$this->assertCount( 2, $items );
		$this->assertSame(
			array(
				'id'         => 'save300',
				'quantity'   => 1,
				'title'      => 'save300',
				'unit_price' => -300,
			),
			$items[1]
		);
		// 1870 - (2 * 1000 - 300) - 0 shipping.
		$this->assertMatchesRegularExpression( '/"tax":\s*170\s/', $output );
	}

	/**
	 * Fee names are passed as entered, even with characters that broke the
	 * old string literal (newline, backslash, double quote) or were shown as
	 * HTML entities (&).
	 */
	public function test_fee_names_are_passed_as_entered() {
		list( $order ) = $this->create_order_with_product();

		$names = array(
			"Handling\nfee",
			'Rush \\ "express" & co',
		);
		foreach ( $names as $name ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( $name );
			$fee->set_amount( '330' );
			$fee->set_total( '330' );
			$order->add_item( $fee );
		}
		$order->save();

		$items = $this->decode_items( $this->render( wc_get_order( $order->get_id() ) ) );

		$this->assertCount( 3, $items );
		$this->assertSame(
			array(
				'id'         => 'fee1',
				'quantity'   => 1,
				'title'      => "Handling\nfee",
				'unit_price' => 330,
			),
			$items[1]
		);
		$this->assertSame( 'fee2', $items[2]['id'] );
		$this->assertSame( 'Rush \\ "express" & co', $items[2]['title'] );
	}

	/**
	 * Item names are passed as entered (no "&amp;"), and markup in a name
	 * cannot close the inline script.
	 */
	public function test_item_names_are_passed_as_entered_and_cannot_close_the_script() {
		$name          = 'Tom & Jerry </script><script>alert(1)</script><!--';
		list( $order ) = $this->create_order_with_product( $name );

		$output = $this->render( $order );
		$items  = $this->decode_items( $output );

		$this->assertSame( $name, $items[0]['title'] );
		$this->assertSame( 1, substr_count( $output, '</script>' ), 'Only the closing tag of the script itself should be printed.' );
		$this->assertStringNotContainsString( '<!--', $output );
	}

	/**
	 * A line item without a product ID is left out, and the items stay a
	 * valid array.
	 */
	public function test_line_item_without_product_is_left_out() {
		list( $order, $product_id ) = $this->create_order_with_product();

		$orphan = new WC_Order_Item_Product();
		$orphan->set_name( 'Deleted product' );
		$orphan->set_quantity( 1 );
		$orphan->set_subtotal( '500' );
		$orphan->set_total( '500' );
		$order->add_item( $orphan );
		$order->save();

		$items = $this->decode_items( $this->render( wc_get_order( $order->get_id() ) ) );

		$this->assertCount( 1, $items );
		$this->assertSame( (string) $product_id, $items[0]['id'] );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}
}
