<?php
/**
 * Japan correspondence of COD payments
 *
 * @class       JP4WC_COD_Fee
 * @extends     WC_Gateway_COD
 * @version     2.6.22
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Japan correspondence of COD payments
 *
 * @class       JP4WC_COD_Fee
 * @extends     WC_Gateway_COD
 * @version     2.6.22
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */
class JP4WC_COD_Fee extends WC_Gateway_COD {

	/**
	 * Current gateway.
	 *
	 * @var string
	 */
	public $current_gateway;

	/**
	 * Current extra charge amount.
	 *
	 * @var double
	 */
	public $current_extra_charge_amount;

	/**
	 * COD fee settings
	 *
	 * @var array
	 */
	public $extra_charge_terms_of_use;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->current_gateway             = null;
		$this->current_extra_charge_amount = 0;

		parent::__construct();
		$this->add_form_fields();

		// BACS account fields shown on the thanks page and in emails.
		$this->extra_charge_terms_of_use = get_option(
			'woocommerce_cod_fees',
			array(
				array(
					'cod_fee' => $this->get_option( 'cod_fee' ),
					'cod_max' => $this->get_option( 'cod_max' ),
				),
			)
		);

		// Hooks & Filters.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_frontend' ) );
		add_action( 'woocommerce_update_options_payment_gateways_cod', array( $this, 'save_account_details' ) );
		add_filter( 'woocommerce_cart_calculate_fees', array( $this, 'jp4wc_calculate_order_totals' ) );
	}

	/**
	 * Enqueues scripts and styles for the frontend.
	 *
	 * Handles the registration and enqueuing of JavaScript and CSS files
	 * required for the COD (Cash on Delivery) fee functionality on the frontend.
	 *
	 * @return void
	 */
	public function enqueue_scripts_frontend() {
		$min = ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ? '.min' : '';

		if ( ! is_checkout() ) {
			return; }

		wp_enqueue_script( 'wc-pf-checkout', JP4WC_URL_PATH . 'assets/js/checkout' . $min . '.js', array( 'jquery' ), JP4WC_VERSION, true );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function add_form_fields() {
		foreach ( $this->form_fields as $key => $value ) {
			$current_fields[ $key ] = $value;
		}
		$current_fields['extra_cod_title']             = array(
			'title' => __( 'Extra charge for COD method', 'woocommerce-for-japan' ),
			'type'  => 'title',
		);
		$current_fields['extra_charge_name']           = array(
			'title'       => __( 'Fee name', 'woocommerce-for-japan' ),
			'type'        => 'text',
			'description' => '',
			'default'     => __( 'COD Payment method fee', 'woocommerce-for-japan' ),
		);
		$current_fields['extra_charge_amount']         = array(
			'title' => __( 'Extra charge amount', 'woocommerce-for-japan' ),
			'type'  => 'number',
			'css'   => 'width:70px;',
		);
		$current_fields['extra_charge_max_cart_value'] = array(
			'title'       => __( 'Maximum cart value to which adding fee', 'woocommerce-for-japan' ),
			'type'        => 'number',
			'css'         => 'width:70px;',
			'description' => __( 'If you dont need this setting, please set empty, 0.', 'woocommerce-for-japan' ),
		);
		$current_fields['extra_charge_calc_taxes']     = array(
			'title'   => __( 'Includes taxes', 'woocommerce-for-japan' ),
			'type'    => 'select',
			'options' => array(
				'no-tax'   => __( 'Do not calculate taxes', 'woocommerce-for-japan' ),
				'tax-incl' => __( 'The fee is taxes included', 'woocommerce-for-japan' ),
				'tax-excl' => __( 'The fee is taxes excluded', 'woocommerce-for-japan' ),
			),
		);
		$current_fields['extra_charge_terms_of_use']   = array(
			'type' => 'terms_of_use_details',
		);
		$this->form_fields                             = $current_fields;
	}

	/**
	 * Generate account details html.
	 *
	 * @return string
	 */
	public function generate_terms_of_use_details_html() {

		ob_start();

		?>
		<tr valign="top" id="terms_of_use_details">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Charge amount of details:', 'woocommerce-for-japan' ); ?></th>
			<td class="forminp" id="bacs_accounts">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th><?php esc_html_e( 'Charge amount of COD', 'woocommerce-for-japan' ); ?></th>
							<th><?php esc_html_e( 'Max', 'woocommerce-for-japan' ); ?></th>
						</tr>
						</thead>
						<tbody class="accounts">
						<?php
						$i = -1;
						if ( $this->extra_charge_terms_of_use ) {
							foreach ( $this->extra_charge_terms_of_use as $cod_fee ) {
								++$i;

								echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $cod_fee['cod_fee'] ) ) . '" name="cod_fee[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $cod_fee['cod_max'] ) ) . '" name="cod_max[' . esc_attr( $i ) . ']" /></td>
									</tr>';
							}
						}
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add Charge amount', 'woocommerce-for-japan' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected Charge amount(s)', 'woocommerce-for-japan' ); ?></a></th>
						</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#bacs_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bacs_accounts').find('tbody .account').length;

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="cod_fee[' + size + ']" /></td>\
									<td><input type="text" name="cod_max[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

							return false;
						});
					});
				</script>
				<p class="cod-charge-note"><?php esc_html_e( 'Note : This function is only available to PRO purchasers.', 'woocommerce-for-japan' ); ?></p>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Save account details table.
	 */
	public function save_account_details() {

		$fees = array();

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( isset( $_POST['cod_fee'] ) && isset( $_POST['cod_max'] ) && wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {

			$cod_fees = wc_clean( array_map( 'sanitize_text_field', wp_unslash( $_POST['cod_fee'] ) ) );
			$cod_maxs = wc_clean( array_map( 'sanitize_text_field', wp_unslash( $_POST['cod_max'] ) ) );

			foreach ( $cod_fees as $i => $name ) {
				if ( ! isset( $cod_fees[ $i ] ) ) {
					continue;
				}

				$fees[] = array(
					'cod_fee' => $cod_fees[ $i ],
					'cod_max' => $cod_maxs[ $i ],
				);
			}
		}
		update_option( 'woocommerce_cod_fees', $fees );
	}


	/**
	 * Add extra charge to cart totals
	 *
	 * @param object $cart Cart object.
	 * @return mixed
	 */
	public function jp4wc_calculate_order_totals( $cart ) {
		if ( is_admin() || 0 === $cart->get_cart_contents_count() ) {
			return;
		}
		if ( defined( 'WOOCOMMERCE_STOREAPI' ) && WOOCOMMERCE_STOREAPI ) {
			// Parse php://input to get the payment method.
			$raw_input = file_get_contents( 'php://input' );
			$body_json = json_decode( $raw_input, true );

			// If a payment method is sent with a block-based checkout, it goes in "payment_method" key.
			if ( isset( $body_json['payment_method'] ) ) {
				$current_gateway = $body_json['payment_method'];
			} else {
				return;
			}
		} else {
			// For traditional checkout (shortcode), retrieve from session.
			$current_gateway = WC()->session->get( 'chosen_payment_method' );
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$subtotal           = $cart->cart_contents_total;

		if ( ! empty( $available_gateways ) ) {
			// Get the current gateway.
			if ( isset( $current_gateway ) && isset( $available_gateways[ $current_gateway ] ) ) {
				$current_gateway = $available_gateways[ $current_gateway ];
			} elseif ( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) ) {
				$current_gateway = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ];
			} else {
				$current_gateway = current( $available_gateways );
			}
		}

		$cod_setting = get_option( 'woocommerce_cod_settings' );
		if ( isset( $cod_setting['extra_charge_max_cart_value'] ) ) {
			$extra_charge_max_cart_value = $cod_setting['extra_charge_max_cart_value'];
		} else {
			$extra_charge_max_cart_value = '';
		}

		$extra_charge_amount = 0;
		$extra_charge_name   = '';
		// Add charges to cart totals.
		if ( ! empty( $current_gateway ) && ( empty( $extra_charge_max_cart_value ) || $extra_charge_max_cart_value >= $subtotal ) && 'cod' === $current_gateway->id ) {

			if ( isset( $cod_setting['extra_charge_name'] ) ) {
				$extra_charge_name = $cod_setting['extra_charge_name'];
			}
			if ( isset( $cod_setting['extra_charge_amount'] ) ) {
				$extra_charge_amount = $cod_setting['extra_charge_amount'];
			}
			if ( isset( $cod_setting['extra_charge_calc_taxes'] ) ) {
				$calc_taxes = $cod_setting['extra_charge_calc_taxes'];
			}

			$taxable = false;
			$taxes   = 0;
			if ( isset( $calc_taxes ) && 'no-tax' !== $calc_taxes ) {
				$taxable   = true;
				$tax       = new WC_Tax();
				$base_rate = $tax->get_base_tax_rates();
				$taxrates  = array_shift( $base_rate );
				$taxrate   = floatval( $taxrates['rate'] ) / 100;
				if ( 'tax-incl' === $calc_taxes ) {
					$taxes                = $extra_charge_amount - ( $extra_charge_amount / ( 1 + $taxrate ) );
					$extra_charge_amount -= $taxes;
				} else {
					$taxes = $extra_charge_amount * $taxrate;
				}
			}

			$extra_charge_amount = apply_filters( 'jp4wc_' . $current_gateway->id . '_amount', $extra_charge_amount, $subtotal, $current_gateway );
			$do_apply            = 0 !== $extra_charge_amount;
			$do_apply            = apply_filters( 'jp4wc_apply', $do_apply, $extra_charge_amount, $subtotal, $current_gateway, $cart );
			$do_apply            = apply_filters( 'jp4wc_apply_for_' . $current_gateway->id, $do_apply, $extra_charge_amount, $subtotal, $current_gateway );

			if ( $do_apply ) {

				$already_exists = false;
				$fees           = $cart->get_fees();
				$fees_count     = count( $fees );
				for ( $i = 0; $i < $fees_count; $i++ ) {
					if ( 'payment-method-fee' === $fees[ $i ]->id ) {
						$already_exists = true;
						$fee_id         = $i;
					}
				}

				if ( ! $already_exists ) {
					$cart->add_fee( $extra_charge_name, $extra_charge_amount, $taxable );
				} else {
					$fees[ $fee_id ]->amount = $extra_charge_amount;
				}
			}
		}
	}
}
