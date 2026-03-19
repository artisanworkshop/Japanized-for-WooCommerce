<?php
/**
 * Automatic address entry from zip code using Yahoo API
 *
 * The Yahoo API to use is as follows
 * https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/zipcodesearch.html
 *
 * @version     2.5.11
 * @package     Japanized for WooCommerce
 * @category    Automatic address entry from zip code using Yahoo API
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class JP4WC_Address_Yahoo_Auto_Entry
 */
class JP4WC_Address_Yahoo_Auto_Entry {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		require_once 'class-jp4wc-yahoo-api-endpoint.php';
		// FrontEnd CSS file
		// add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style'), 99 );
		// Automatic address registration by postal code.
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'auto_zip2address_billing' ), 10 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'auto_zip2address_shipping' ), 10 );
		add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'auto_zip2address_billing' ), 10 );
		add_action( 'woocommerce_after_edit_address_form_shipping', array( $this, 'auto_zip2address_shipping' ), 10 );
	}

	// FrontEnd CSS file function.
	/** Enqueue styles for the order received page and account page.
	 * This function checks if the current page is the order received page or the account page,
	 * and enqueues the appropriate CSS file for each page.
	 */
	public function frontend_enqueue_style() {
		if ( is_order_received_page() ) {
			wp_register_style( 'custom_order_received_jp4wc', JP4WC_URL_PATH . 'assets/css/order-received-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'custom_order_received_jp4wc' );
		}
		if ( is_account_page() ) {
			wp_register_style( 'edit_account_jp4wc', JP4WC_URL_PATH . 'assets/css/edit-account-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'edit_account_jp4wc' );
		}
	}

	/**
	 * Automatic input from postal code to Address for billing
	 *
	 * @return void
	 */
	public function auto_zip2address_billing() {
		$this->auto_zip2address( 'billing' );
	}

	/**
	 * Automatic input from postal code to Address for shipping.
	 */
	public function auto_zip2address_shipping() {
		$this->auto_zip2address( 'shipping' );
	}

	/**
	 * Display JavaScript code for automatic registration of address by zip code.
	 *
	 * @param string $method 'billing' or 'shipping'.
	 */
	public function auto_zip2address( $method ) {
		$state_id     = 'select2-' . $method . '_state-container';
		$endpoint_url = esc_url( get_rest_url( null, 'yahoo/v1/postcode/' ) );
		$nonce        = wp_create_nonce( 'wp_rest' );
		if ( get_option( 'wc4jp-zip2address' ) ) {
			?>
<script type="text/javascript">
	// Method to automatically insert hyphen in postal code
	jQuery(function($) {
		// Method to automatically insert hyphen in postal code
		function insertStr(input){
			return input.slice(0, 3) + '-' + input.slice(3,input.length);
		}

		$(document).ready(function(){
			$("#<?php echo esc_js( $method ); ?>_postcode").keyup(function(e){
				let zip = $("#<?php echo esc_js( $method ); ?>_postcode").val(),
					zipCount = zip.length;

				// Control the delete key so that the hyphen addition process does not work (8 is Backspace, 46 is Delete)
				let key = e.keyCode || e.charCode;
				if( key === 8 || key === 46 ){
					return false;
				}

				if(zipCount === 3){
					$("#<?php echo esc_js( $method ); ?>_postcode").val(insertStr(zip));
				}else if( zipCount > 7) {
					const url = "<?php echo esc_url( get_rest_url( null, 'yahoo/v1/postcode/' ) ); ?>";
					let param = {
						post_code: $("#<?php echo esc_js( $method ); ?>_postcode").val()
					};
					$.ajax({
						url: url,
						type: "POST",
						data: param,
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( $nonce ); ?>');
						},
					}).done(function(result,textStatus,jqXHR) {
						jQuery("#<?php echo esc_js( $method ); ?>_state").val(result["state_code"]);
						jQuery("#<?php echo esc_js( $method ); ?>_city").val(result["city"]);
						document.getElementById("<?php echo esc_js( $method ); ?>_state").value = result["state_code"];
						document.getElementById("<?php echo esc_js( $state_id ); ?>").innerHTML = result["state"];
					});
				}
			});
		});
	});
</script>
			<?php
		}
	}
}
// Yahoo Auto Postcode Class load.
if ( ! get_option( 'wc4jp-no-ja' ) ) {
	new JP4WC_Address_Yahoo_Auto_Entry();
}
