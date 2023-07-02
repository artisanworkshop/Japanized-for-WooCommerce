<?php
/**
 * PeachPay address autocomplete settings page.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay address autocomplete settings page.
 */
final class PeachPay_Address_Autocomplete_Settings extends PeachPay_Admin_Tab {

	/**
	 * The id to reference the stored settings with.
	 *
	 * @var string
	 */
	public $id = 'address_autocomplete';

	/**
	 * Gets the section url key.
	 */
	public function get_section() {
		return 'address_autocomplete';
	}

	/**
	 * Gets the tab url key.
	 */
	public function get_tab() {
		return 'settings';
	}

	/**
	 * Gets the tab title.
	 */
	public function get_title() {
		return __( 'Address autocomplete', 'peachpay-for-woocommerce' );
	}

	/**
	 * Gets the tab title.
	 */
	public function get_description() {
		return __(
			'Make it easier for your shoppers to enter their address by enabling address autocomplete powered by Google Maps. When a shopper begins to enter their street address, we’ll suggest the closest matching address and automatically fill in the street, city, state or province, postal code, and country.
		',
			'peachpay-for-woocommerce'
		);
	}

	/**
	 * Include dependencies here.
	 */
	protected function includes() {}

	/**
	 * Attach to enqueue scripts hook.
	 */
	protected function enqueue_admin_scripts() {
		PeachPay::enqueue_style(
			'peachpay-premium-styles',
			'core/admin/assets/css/premium.css',
			array()
		);
	}

	/**
	 * Register form fields here. This is optional but required if you want to display settings.
	 */
	protected function register_form_fields() {
		return array(
			'enabled'          => array(
				'type'    => 'checkbox',
				'title'   => __( 'Enable', 'peachpay-for-woocommerce' ),
				'label'   => __( 'Enable address autocomplete', 'peachpay-for-woocommerce' ),
				'default' => 'no',
				'class'   => 'toggle',
			),
			'active_locations' => array(
				'type'    => 'select',
				'title'   => __( 'Location', 'peachpay-for-woocommerce' ),
				'text'    => __( 'Where to display', 'peachpay-for-woocommerce' ),
				'label'   => __( 'cry', 'peachpay-for-woocommerce' ),
				'options' => array(
					'default'               => __( 'Both checkout page and Express Checkout', 'peachpay-for-woocommerce' ),
					'checkout_page_only'    => __( 'Checkout page only', 'peachpay-for-woocommerce' ),
					'express_checkout_only' => __( 'Express Checkout only', 'peachpay-for-woocommerce' ),
				),
			),
		);
	}

	/**
	 * Renders the Admin page.
	 */
	public function do_admin_view() {
		?>
			<h1>
				<?php echo esc_html( $this->get_title() ); ?>
			</h1>
			<p>
				<?php echo esc_html( $this->get_description() ); ?>
			</p>
			<?php parent::do_admin_view(); ?>
		<?php
	}

}
