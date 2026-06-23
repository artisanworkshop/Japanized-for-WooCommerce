<?php
/**
 * Cash on Delivery Gateway for Subscriptions
 *
 * This file provides a Cash on Delivery payment gateway that supports WooCommerce Subscriptions.
 *
 * @package WooCommerce-For-Japan
 * @version 2.7.11
 * @category Payment Gateways
 * @author ArtsanWorkshop
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cash on Delivery Gateway for Subscriptions.
 *
 * Provides a Cash on Delivery Payment Gateway for Subscriptions.
 *
 * @class       WC_Gateway_COD2
 * @extends     WC_Payment_Gateway
 * @version     2.7.11
 * @package     WooCommerce/Classes/Payment
 * @author      ArtsanWorkshop
 */
class WC_Gateway_COD2 extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'cod2';

	/**
	 * Instructions to be displayed on the thank you page and in emails.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $instructions;

	/**
	 * Array of shipping method IDs this COD gateway is allowed for
	 *
	 * @var array
	 */
	public $enable_for_methods;

	/**
	 * Enable COD for virtual products option
	 *
	 * @var string
	 */
	public $enable_for_virtual;


	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'woocommerce_cod2_icon', JP4WC_URL_PATH . '/assets/images/jp4wc-cash-on-delivery.png' );
		$this->method_title       = __( 'Cash on Delivery for Subscriptions', 'woocommerce-for-japan' );
		$this->method_description = __( 'Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce-for-japan' );
		$this->has_fields         = false;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array(
			'products',
		);

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_cod2_fee_details' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                     => array(
				'title'       => __( 'Enable COD', 'woocommerce-for-japan' ),
				'label'       => __( 'Enable Cash on Delivery', 'woocommerce-for-japan' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'                       => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Cash on Delivery', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description'                 => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-for-japan' ),
				'default'     => __( 'Pay with cash upon delivery.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'instructions'                => array(
				'title'       => __( 'Instructions', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce-for-japan' ),
				'default'     => __( 'Pay with cash upon delivery.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'enable_for_methods'          => array(
				'title'             => __( 'Enable for shipping methods', 'woocommerce-for-japan' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-for-japan' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'woocommerce-for-japan' ),
				),
			),
			'enable_for_virtual'          => array(
				'title'   => __( 'Accept for virtual orders', 'woocommerce-for-japan' ),
				'label'   => __( 'Accept COD if the order is virtual', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			'extra_cod2_title'            => array(
				'title' => __( 'Extra charge for COD2 method', 'woocommerce-for-japan' ),
				'type'  => 'title',
			),
			'extra_charge_name'           => array(
				'title'   => __( 'Fee name', 'woocommerce-for-japan' ),
				'type'    => 'text',
				'default' => __( 'COD Payment method fee', 'woocommerce-for-japan' ),
			),
			'extra_charge_amount'         => array(
				'title' => __( 'Extra charge amount', 'woocommerce-for-japan' ),
				'type'  => 'number',
				'css'   => 'width:120px;',
			),
			'extra_charge_max_cart_value' => array(
				'title'       => __( 'Maximum cart value to which adding fee', 'woocommerce-for-japan' ),
				'type'        => 'number',
				'css'         => 'width:120px;',
				'description' => __( "If you don't need this setting, please leave it empty or set to 0.", 'woocommerce-for-japan' ),
			),
			'extra_charge_calc_taxes'     => array(
				'title'   => __( 'Includes taxes', 'woocommerce-for-japan' ),
				'type'    => 'select',
				'options' => array(
					'no-tax'   => __( 'Do not calculate taxes', 'woocommerce-for-japan' ),
					'tax-incl' => __( 'The fee is taxes included', 'woocommerce-for-japan' ),
					'tax-excl' => __( 'The fee is taxes excluded', 'woocommerce-for-japan' ),
				),
			),
			'extra_charge_terms_of_use'   => array(
				'type' => 'cod2_fee_details',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_virtual       = true;
		$shipping_methods = array();

		// Get shipping methods from the cart or order.
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order            = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
			$shipping_methods = $order ? $order->get_shipping_methods() : array();
			$is_virtual       = ! count( $shipping_methods );
		} elseif ( WC()->cart && WC()->cart->needs_shipping() ) {
			$shipping_methods = WC()->cart->get_shipping_methods();
			$is_virtual       = false;
		}

		// If COD is not enabled for virtual orders and the order does not need shipping, return false.
		if ( ! $this->enable_for_virtual && $is_virtual ) {
			return false;
		}

		// Return early if:
		// - There are no shipping methods resrictions in place.
		// - The order is virtual so needs no shipping.
		// - Shipping methods are not set yet.
		if ( empty( $this->enable_for_methods ) || $is_virtual || ! $shipping_methods ) {
			return parent::is_available();
		}

		// Get the selected shipping method ids. This works on both WC_Shipping_Rate and WC_Order_Item_Shipping class instances.
		$canonical_rate_ids = array_unique(
			array_values(
				array_map(
					function ( $shipping_method ) {
						return $shipping_method && is_callable( array( $shipping_method, 'get_method_id' ) ) && is_callable( array( $shipping_method, 'get_instance_id' ) ) ? $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id() : null;
					},
					$shipping_methods
				)
			)
		);

		if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || self::ID !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		if ( Constants::is_true( 'REST_REQUEST' ) ) {
			global $wp;
			if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		$zones      = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce core string, intentionally using woocommerce domain

			foreach ( $zones as $zone ) {
				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {
					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce core string

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce core string

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return array
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark as processing or on-hold (payment won't be taken until delivery).
		$order->update_status( 'processing', __( 'Payment to be made upon delivery.', 'woocommerce-for-japan' ) );

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param object $order WC_Order Order object.
	 * @param bool   $sent_to_admin Whether the email is being sent to the admin.
	 * @param bool   $plain_text Whether the email is plain text.
	 * @return void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
		}
	}

	/**
	 * Get COD2 fee settings from gateway options.
	 *
	 * @return array
	 */
	public static function get_cod2_fee_settings() {
		$settings = get_option( 'woocommerce_cod2_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Generate PRO tiered fee table HTML for COD2 admin settings.
	 *
	 * @return string
	 */
	public function generate_cod2_fee_details_html() {
		$tax_class = get_option( 'jp4wc_tax_class_for_cod2' );
		$tax_class = empty( $tax_class ) ? 'standard' : $tax_class;
		$cod2_fees = get_option(
			'woocommerce_cod2_fees',
			array(
				array(
					'cod_fee' => '',
					'cod_max' => '',
				),
			)
		);

		ob_start();
		?>
		<tr valign="top" id="cod2_tax_class_setting">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Tax Class:', 'woocommerce-for-japan' ); ?></th>
			<td class="forminp" id="cod2_tax_class_setting">
			<select name="jp4wc_tax_class_for_cod2">
			<?php foreach ( jp4wc_get_fee_tax_classes() as $tax_class_id => $tax_class_name ) : ?>
					<option value="<?php echo esc_attr( $tax_class_id ); ?>" <?php selected( $tax_class, $tax_class_id ); ?>><?php echo esc_html( $tax_class_name ); ?></option>
			<?php endforeach; ?>
			</select>
			</td>
		</tr>
		<tr valign="top" id="cod2_fee_details">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Charge amount of details:', 'woocommerce-for-japan' ); ?></th>
			<td class="forminp" id="cod2_fee_accounts">
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
						foreach ( $cod2_fees as $cod2_fee ) {
							++$i;
							echo '<tr class="account">
									<td class="sort"></td>
									<td><input type="text" value="' . esc_attr( wp_unslash( $cod2_fee['cod_fee'] ) ) . '" name="cod2_fee[' . esc_attr( $i ) . ']" /></td>
									<td><input type="text" value="' . esc_attr( wp_unslash( $cod2_fee['cod_max'] ) ) . '" name="cod2_max[' . esc_attr( $i ) . ']" /></td>
								</tr>';
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
						jQuery('#cod2_fee_accounts').on( 'click', 'a.add', function(){
							var size = jQuery('#cod2_fee_accounts').find('tbody .account').length;
							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="cod2_fee[' + size + ']" /></td>\
									<td><input type="text" name="cod2_max[' + size + ']" /></td>\
								</tr>').appendTo('#cod2_fee_accounts table tbody');
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
	 * Save PRO tiered fee details for COD2.
	 */
	public function save_cod2_fee_details() {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! isset( $_POST['cod2_fee'] ) || ! isset( $_POST['cod2_max'] ) || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
			return;
		}

		$fees      = array();
		$cod2_fees = wc_clean( array_map( 'sanitize_text_field', wp_unslash( $_POST['cod2_fee'] ) ) );
		$cod2_maxs = wc_clean( array_map( 'sanitize_text_field', wp_unslash( $_POST['cod2_max'] ) ) );

		foreach ( $cod2_fees as $i => $fee ) {
			if ( ! isset( $cod2_maxs[ $i ] ) ) {
				continue;
			}
			$fees[] = array(
				'cod_fee' => $fee,
				'cod_max' => $cod2_maxs[ $i ],
			);
		}

		if ( isset( $_POST['jp4wc_tax_class_for_cod2'] ) ) {
			update_option( 'jp4wc_tax_class_for_cod2', sanitize_text_field( wp_unslash( $_POST['jp4wc_tax_class_for_cod2'] ) ) );
		}

		update_option( 'woocommerce_cod2_fees', $fees );
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Payment methods.
	 * @return array
	 */
	public static function add_gateway( $methods ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
			$subscription_support_enabled = true;
		}
		if ( isset( $subscription_support_enabled ) ) {
			$methods[] = 'WC_Addons_Gateway_COD2';
		} else {
			$methods[] = 'WC_Gateway_COD2';
		}
		return $methods;
	}
}

// Add the gateway to WooCommerce.
if ( get_option( 'wc4jp-cod2' ) ) {
	add_filter( 'woocommerce_payment_gateways', array( 'WC_Gateway_COD2', 'add_gateway' ) );
}
