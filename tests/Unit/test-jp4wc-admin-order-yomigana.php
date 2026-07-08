<?php
/**
 * Class JP4WC_Admin_Order_Yomigana_Test
 *
 * Tests deduplication of yomigana fields on the admin order edit screen.
 * WooCommerce core (CheckoutFieldsAdmin) injects Additional Checkout Fields API
 * yomigana fields with `show => true`, duplicating the yomigana already embedded
 * in the formatted address, and doubling the edit-form inputs.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Admin_Order_Yomigana_Test test case.
 */
class JP4WC_Admin_Order_Yomigana_Test extends WP_UnitTestCase {

	/**
	 * Address fields instance under test.
	 *
	 * @var JP4WC_Address_Fields
	 */
	private $address_fields;

	/**
	 * A preparation method that is always called before each test is run.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->address_fields = new JP4WC_Address_Fields();
	}

	/**
	 * Build the admin fields array as it looks after WC core injected the
	 * Additional Checkout Fields API yomigana fields and the plugin added its
	 * classic pair with `show => false`. CheckoutFieldsAdmin inserts its fields
	 * via array_splice(), which does not preserve string keys, so the injected
	 * fields carry numeric keys and are identified by their 'id'.
	 *
	 * @param string $group      Address group: 'billing' or 'shipping'.
	 * @param string $last_name  Value for the block last name field.
	 * @param string $first_name Value for the block first name field.
	 * @return array
	 */
	private function get_admin_fields_with_block_yomigana( $group = 'billing', $last_name = 'やまだ', $first_name = 'たろう' ) {
		return array(
			'last_name'           => array( 'label' => 'Last name' ),
			'first_name'          => array( 'label' => 'First name' ),
			'yomigana_last_name'  => array(
				'label' => 'Last Name Yomigana',
				'show'  => false,
			),
			'yomigana_first_name' => array(
				'label' => 'First Name Yomigana',
				'show'  => false,
			),
			'state'               => array( 'label' => 'State' ),
			0                     => array(
				'id'    => '_wc_' . $group . '/jp4wc/yomigana_last_name',
				'label' => 'Last Name ( Yomigana )',
				'value' => $last_name,
				'show'  => true,
			),
			1                     => array(
				'id'    => '_wc_' . $group . '/jp4wc/yomigana_first_name',
				'label' => 'First Name ( Yomigana )',
				'value' => $first_name,
				'show'  => true,
			),
			'city'                => array( 'label' => 'City' ),
		);
	}

	/**
	 * Find a field by its 'id' in the admin fields array.
	 *
	 * @param array  $fields The admin fields array.
	 * @param string $id     The field id to look for.
	 * @return array|null The field, or null when absent.
	 */
	private function find_field_by_id( $fields, $id ) {
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && $id === $field['id'] ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Create an order with block-format (Additional Checkout Fields API) yomigana meta.
	 *
	 * @param string $group Address group: 'billing' or 'shipping'.
	 * @return WC_Order
	 */
	private function create_block_order( $group = 'billing' ) {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_' . $group . '/jp4wc/yomigana_last_name', 'やまだ' );
		$order->update_meta_data( '_wc_' . $group . '/jp4wc/yomigana_first_name', 'たろう' );
		$order->save();
		return $order;
	}

	/**
	 * Create an order with classic-format yomigana meta.
	 *
	 * @param string $group Address group: 'billing' or 'shipping'.
	 * @return WC_Order
	 */
	private function create_classic_order( $group = 'billing' ) {
		$order = new WC_Order();
		$order->update_meta_data( '_' . $group . '_yomigana_last_name', 'やまだ' );
		$order->update_meta_data( '_' . $group . '_yomigana_first_name', 'たろう' );
		$order->save();
		return $order;
	}

	/**
	 * Block order: WC-injected fields must be hidden from view mode (show => false)
	 * and the classic pair removed from the edit form.
	 */
	public function test_block_order_hides_view_rows_and_removes_classic_pair() {
		$order  = $this->create_block_order( 'billing' );
		$fields = $this->address_fields->admin_billing_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'billing' ),
			$order
		);

		$block_last  = $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_last_name' );
		$block_first = $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_first_name' );

		$this->assertNotNull( $block_last, 'Block yomigana last name should be kept' );
		$this->assertNotNull( $block_first, 'Block yomigana first name should be kept' );
		$this->assertFalse( $block_last['show'], 'Block yomigana last name should be hidden in view mode' );
		$this->assertFalse( $block_first['show'], 'Block yomigana first name should be hidden in view mode' );
		$this->assertSame( 'やまだ', $block_last['value'], 'Block field value should be preserved' );
		$this->assertArrayNotHasKey( 'yomigana_last_name', $fields, 'Classic yomigana last name should be removed from the edit form' );
		$this->assertArrayNotHasKey( 'yomigana_first_name', $fields, 'Classic yomigana first name should be removed from the edit form' );

		$order->delete( true );
	}

	/**
	 * Classic order: the WC-injected block pair must be removed entirely and the
	 * classic pair kept for the edit form.
	 */
	public function test_classic_order_removes_block_pair_and_keeps_classic_pair() {
		$order  = $this->create_classic_order( 'billing' );
		$fields = $this->address_fields->admin_billing_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'billing', '', '' ),
			$order
		);

		$this->assertNull( $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_last_name' ), 'Block yomigana last name should be removed for classic orders' );
		$this->assertNull( $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_first_name' ), 'Block yomigana first name should be removed for classic orders' );
		$this->assertArrayHasKey( 'yomigana_last_name', $fields, 'Classic yomigana last name should be kept for classic orders' );
		$this->assertArrayHasKey( 'yomigana_first_name', $fields, 'Classic yomigana first name should be kept for classic orders' );

		$order->delete( true );
	}

	/**
	 * When both meta formats exist, classic wins, matching jp4wc_billing_address().
	 */
	public function test_order_with_both_meta_prefers_classic_pair() {
		$order = new WC_Order();
		$order->update_meta_data( '_billing_yomigana_last_name', 'すずき' );
		$order->update_meta_data( '_wc_billing/jp4wc/yomigana_last_name', 'やまだ' );
		$order->save();

		$fields = $this->address_fields->admin_billing_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'billing' ),
			$order
		);

		$this->assertNull( $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_last_name' ), 'Block pair should be removed when classic meta exists' );
		$this->assertArrayHasKey( 'yomigana_last_name', $fields, 'Classic pair should be kept when classic meta exists' );

		$order->delete( true );
	}

	/**
	 * Shipping group uses `_wc_shipping/jp4wc/*` meta and the shipping wrapper.
	 */
	public function test_shipping_block_order_hides_view_rows_and_removes_classic_pair() {
		$order  = $this->create_block_order( 'shipping' );
		$fields = $this->address_fields->admin_shipping_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'shipping' ),
			$order
		);

		$block_last = $this->find_field_by_id( $fields, '_wc_shipping/jp4wc/yomigana_last_name' );

		$this->assertNotNull( $block_last, 'Block yomigana should be kept in the shipping fields' );
		$this->assertFalse( $block_last['show'], 'Block yomigana should be hidden in shipping view mode' );
		$this->assertArrayNotHasKey( 'yomigana_last_name', $fields, 'Classic yomigana should be removed from the shipping edit form' );

		$order->delete( true );
	}

	/**
	 * Without an order (e.g. the initial get_billing_fields() call in the meta box),
	 * the fields must pass through unchanged.
	 */
	public function test_no_order_returns_fields_unchanged() {
		$fields = $this->get_admin_fields_with_block_yomigana( 'billing' );

		$this->assertSame( $fields, $this->address_fields->admin_billing_dedupe_yomigana_fields( $fields, null ) );
		$this->assertSame( $fields, $this->address_fields->admin_billing_dedupe_yomigana_fields( $fields, false ) );
	}

	/**
	 * Without WC-injected block fields (classic-only environment), the fields must
	 * pass through unchanged.
	 */
	public function test_fields_without_block_fields_are_unchanged() {
		$order  = $this->create_classic_order( 'billing' );
		$fields = array(
			'last_name'           => array( 'label' => 'Last name' ),
			'yomigana_last_name'  => array(
				'label' => 'Last Name Yomigana',
				'show'  => false,
			),
			'yomigana_first_name' => array(
				'label' => 'First Name Yomigana',
				'show'  => false,
			),
		);

		$this->assertSame( $fields, $this->address_fields->admin_billing_dedupe_yomigana_fields( $fields, $order ) );

		$order->delete( true );
	}

	/**
	 * Order without any yomigana meta on a classic checkout page keeps the classic
	 * pair and removes the block pair.
	 */
	public function test_empty_order_on_classic_checkout_keeps_classic_pair() {
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Checkout',
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[woocommerce_checkout]',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $page_id );

		$order = new WC_Order();
		$order->save();

		$fields = $this->address_fields->admin_billing_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'billing', '', '' ),
			$order
		);

		$this->assertNull( $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_last_name' ), 'Block pair should be removed on classic checkout setups' );
		$this->assertArrayHasKey( 'yomigana_last_name', $fields, 'Classic pair should be kept on classic checkout setups' );

		$order->delete( true );
		wp_delete_post( $page_id, true );
		delete_option( 'woocommerce_checkout_page_id' );
	}

	/**
	 * Order without any yomigana meta on a block checkout page keeps the block pair
	 * (hidden in view mode) and removes the classic pair.
	 */
	public function test_empty_order_on_block_checkout_keeps_block_pair() {
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Checkout',
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $page_id );

		$order = new WC_Order();
		$order->save();

		$fields = $this->address_fields->admin_billing_dedupe_yomigana_fields(
			$this->get_admin_fields_with_block_yomigana( 'billing', '', '' ),
			$order
		);

		$block_last = $this->find_field_by_id( $fields, '_wc_billing/jp4wc/yomigana_last_name' );

		$this->assertNotNull( $block_last, 'Block pair should be kept on block checkout setups' );
		$this->assertFalse( $block_last['show'], 'Block pair should stay hidden in view mode' );
		$this->assertArrayNotHasKey( 'yomigana_last_name', $fields, 'Classic pair should be removed on block checkout setups' );

		$order->delete( true );
		wp_delete_post( $page_id, true );
		delete_option( 'woocommerce_checkout_page_id' );
	}

	/**
	 * The dedupe filters must be registered at priority 20, after WC core's
	 * CheckoutFieldsAdmin injection at priority 10.
	 */
	public function test_filters_registered_at_priority_20() {
		$this->assertSame(
			20,
			has_filter( 'woocommerce_admin_billing_fields', array( $this->address_fields, 'admin_billing_dedupe_yomigana_fields' ) ),
			'Billing dedupe filter should be registered at priority 20'
		);
		$this->assertSame(
			20,
			has_filter( 'woocommerce_admin_shipping_fields', array( $this->address_fields, 'admin_shipping_dedupe_yomigana_fields' ) ),
			'Shipping dedupe filter should be registered at priority 20'
		);
	}
}
