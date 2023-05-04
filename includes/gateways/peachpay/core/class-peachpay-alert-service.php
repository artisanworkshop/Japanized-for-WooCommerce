<?php
/**
 * Peachpay_Alert_Service class
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Displays various admin alerts for PeachPay. Different from the dependency service as none of these will need PeachPay to abort operations.
 * Constructor adds the WordPress action responsible for running the alert code.
 */
class PeachPay_Alert_Service {

	/**
	 * Constructor method. This PHP magic method is called automatically as the class is instantiated.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_alerts' ), 11 );
	}

	/**
	 * The base function for displaying admin alerts. This should be used for any alerts caused by code only run on the cart page, such
	 * as checkout field rendering.
	 */
	public function display_alerts() {
		// If third party field editors are being used, display a warning.
		if ( self::third_party_field_plugins_exist() ) {
			?>
			<div class="notice peachpay-notice <?php echo esc_attr( 'notice-warning' ); ?>">
				<p><b><?php echo esc_html_e( 'PeachPay for WooCommerce', 'peachpay-for-woocommerce' ); ?></b></p>
				<p>
				<?php
				echo esc_html_e( "Looks like you're using a third party plugin to handle additional checkout fields for your store. PeachPay now has its own field editor built in! While we will do our best to convert any third party fields, it is recommended to make the switch to our native editor for the best experience. Unsupported fields may cause issues with completing orders. Check out our field editor ", 'peachpay-for-woocommerce' );
				echo '<a href="' . esc_url_raw( site_url( '/wp-admin/admin.php?page=peachpay&tab=field&section=shipping' ) ) . '">';
				echo esc_html_e( 'here.', 'peachpay-for-woocommerce' );
				echo '</a>';
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Returns true if any of this list of third party plugins is activated, false otherwise.
	 */
	private function third_party_field_plugins_exist() {
		if ( is_plugin_active( 'woo-checkout-field-editor-pro/checkout-form-designer.php' ) ||
			is_plugin_active( 'flexible-checkout-fields/flexible-checkout-fields.php' ) ||
			is_plugin_active( 'woocommerce-checkout-manager/woocommerce-checkout-manager.php' ) ) {

			return true;
		}
		return false;
	}
}
