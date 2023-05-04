<?php
/**
 * Peachpay_Test_Mode_Service class
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Displays a banner on the PeachPay settings page if test mode is enabled.
 * Constructor adds the WordPress action responsible for running the banner code.
 */
class PeachPay_Test_Mode_Service {

	/**
	 * Constructor method. This PHP magic method is called automatically as the class is instantiated.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_test_banner' ), 11 );
	}

	/**
	 * Checks that both test mode is enabled and the current page is the PeachPay settings page.
	 * If so, displays the test mode banner at the top of the page.
	 */
	public function display_test_banner() {
		$screen_name = get_current_screen()->id;

		if ( peachpay_is_test_mode() && 'toplevel_page_peachpay' === $screen_name ) {
			?>
				<div class='pp-test-notice-wrapper'>
					<div class='pp-test-notice'>
						<?php echo esc_html_e( 'TEST MODE', 'peachpay-for-woocommerce' ); ?>
					</div>
				</div>
			<?php
		}
	}
}
