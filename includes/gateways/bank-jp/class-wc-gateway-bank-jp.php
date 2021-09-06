<?php
/**
 * Class WC_Gateway_BANK_JP file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bank Payment Gateway in Japanese
 *
 * Provides a Bank Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 		WC_Gateway_BANK_JP
 * @extends		WC_Payment_Gateway
 * @version		2.2.21
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_BANK_JP extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'bankjp';
		$this->icon               = apply_filters('woocommerce_bankjp_icon', JP4WC_URL_PATH . '/assets/images/jp4wc-bank-transfer.png');
		$this->has_fields         = false;
		$this->method_title       = __( 'BANK PAYMENT IN JAPAN', 'woocommerce-for-japan' );
		$this->method_description = __( 'Allows payments by bank transfer in Japan.', 'woocommerce-for-japan' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// BANK Japan account fields shown on the thanks page and in emails
		$this->account_details = get_option( 'woocommerce_bankjp_accounts',
			array(
				array(
					'bank_name'      => $this->get_option( 'bank_name' ),
					'bank_branch'    => $this->get_option( 'bank_branch' ),
					'bank_type'      => $this->get_option( 'bank_type' ),
					'account_number' => $this->get_option( 'account_number' ),
					'account_name'   => $this->get_option( 'account_name' ),
				)
			)
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
	    add_action( 'woocommerce_thankyou_bankjp', array( $this, 'thankyou_page' ) );

	    // Customer Emails
	    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
	public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bank Transfer', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Bank Transfer in Japan', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Make your payment directly into our bank account.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-for-japan' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'account_details' => array(
				'type'        => 'account_details'
			),
		);
    }

    /**
     * generate_account_details_html function.
     */
    public function generate_account_details_html() {
    	ob_start();
	    ?>
	    <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Account Details', 'woocommerce-for-japan' ); ?>:</th>
            <td class="forminp" id="bankjp_accounts">
			    <table class="widefat wc_input_table sortable" cellspacing="0">
		    		<thead>
		    			<tr>
		    				<th class="sort">&nbsp;</th>
			            	<th><?php _e( 'Bank Name', 'woocommerce-for-japan' ); ?></th>
			            	<th><?php _e( 'Bank Branch', 'woocommerce-for-japan' ); ?></th>
			            	<th><?php _e( 'Bank Type', 'woocommerce-for-japan' ); ?></th>
			            	<th><?php _e( 'Account Number', 'woocommerce-for-japan' ); ?></th>
		    				<th><?php _e( 'Account Name', 'woocommerce-for-japan' ); ?></th>
		    			</tr>
		    		</thead>
		    		<tfoot>
		    			<tr>
		    				<th colspan="7"><a href="#" class="add button"><?php _e( '+ Add Account', 'woocommerce-for-japan' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Remove selected account(s)', 'woocommerce-for-japan' ); ?></a></th>
		    			</tr>
		    		</tfoot>
		    		<tbody class="accounts">
		            	<?php
		            	$i = -1;
		            	if ( $this->account_details ) {
		            		foreach ( $this->account_details as $account ) {
		                		$i++;

		                		echo '<tr class="account">
		                			<td class="sort"></td>
		                			<td><input type="text" value="' . esc_attr( $account['bank_name'] ) . '" name="bankjp_bank_name[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['bank_branch'] ) . '" name="bankjp_bank_branch[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['bank_type'] ) . '" name="bankjp_bank_type[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['account_number'] ) . '" name="bankjp_account_number[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['account_name'] ) . '" name="bankjp_account_name[' . $i . ']" /></td>
			                    </tr>';
		            		}
		            	}
		            	?>
		        	</tbody>
		        </table>
		       	<script type="text/javascript">
					jQuery(function() {
						jQuery('#bankjp_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bankjp_accounts tbody .account').size();

							jQuery('<tr class="account">\
		                			<td class="sort"></td>\
		                			<td><input type="text" name="bankjp_bank_name[' + size + ']" /></td>\
		                			<td><input type="text" name="bankjp_bank_branch[' + size + ']" /></td>\
		                			<td><input type="text" name="bankjp_bank_type[' + size + ']" /></td>\
		                			<td><input type="text" name="bankjp_account_number[' + size + ']" /></td>\
		                			<td><input type="text" name="bankjp_account_name[' + size + ']" /></td>\
			                    </tr>').appendTo('#bankjp_accounts table tbody');

							return false;
						});
					});
				</script>
            </td>
	    </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Save account details table
     */
    public function save_account_details() {
    	$accounts = array();

    	if ( isset( $_POST['bankjp_account_name'] ) ) {

			$account_names   = wc_clean( wp_unslash( $_POST['bankjp_account_name'] ) );
			$account_numbers = wc_clean( wp_unslash( $_POST['bankjp_account_number'] ) );
			$bank_types      = wc_clean( wp_unslash( $_POST['bankjp_bank_type'] ) );
			$bank_branches   = wc_clean( wp_unslash( $_POST['bankjp_bank_branch'] ) );
			$bank_names      = wc_clean( wp_unslash( $_POST['bankjp_bank_name'] ) );

			foreach ( $account_names as $i => $name ) {
				if ( ! isset( $account_names[ $i ] ) ) {
					continue;
				}

	    		$accounts[] = array(
					'bank_name'      => $bank_names[ $i ],
					'bank_branch'    => $bank_branches[ $i ],
					'bank_type'      => $bank_types[ $i ],
					'account_number' => $account_numbers[ $i ],
					'account_name'   => $account_names[ $i ],
	    		);
	    	}
    	}

    	update_option( 'woocommerce_bankjp_accounts', $accounts );
    }

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 */
    public function thankyou_page( $order_id ) {

		if ( $this->instructions ) {
        	echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) );
        }
        $this->bank_details( $order_id );
    }

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	    $payment_method = $order->get_payment_method();
		$order_status = $order->get_status();
    	if (! $sent_to_admin && 'bankjp' === $payment_method && ('on-hold' === $order_status || 'pending' === $order_status )) {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$order_id = $order->get_id();
			$this->bank_details( $order_id );
		}
    }

	/**
	 * Get bank details and place into a list format.
	 *
	 * @param int $order_id Order ID.
	 */
    private function bank_details( $order_id = '' ) {

    	if ( empty( $this->account_details ) ) {
    		return;
    	}

    	$html = '<h2>' . __( 'Our Bank Details', 'woocommerce-for-japan' ) . '</h2>' . PHP_EOL;

    	$bankjp_accounts = apply_filters( 'woocommerce_bankjp_accounts', $this->account_details );

    	if ( ! empty( $bankjp_accounts ) ) {
			$number_label = __( 'Account Number', 'woocommerce-for-japan' );
			$name_label = __( 'Account Name', 'woocommerce-for-japan' );
	    	foreach ( $bankjp_accounts as $bankjp_account ) {
			    $html .= '<ul class="order_details bankjp_details">' . "\n";

	    		$bankjp_account = (object) $bankjp_account;

	    		// BANK account fields shown on the thanks page and in emails
				$account_fields = apply_filters( 'woocommerce_bankjp_account_fields', array(
					'account_info'=> array(
						'bank_name' => $bankjp_account->bank_name,
						'bank_branch' => $bankjp_account->bank_branch,
						'bank_type' => $bankjp_account->bank_type,
						'value' => $bankjp_account->account_number,
						'account_name' => $bankjp_account->account_name,
					)
				), $order_id );

	    		foreach ( $account_fields as $field_key => $field ) {
				    $html .= '<li class="' . esc_attr( $field_key ) . '">'."\n".'<strong>' . implode( ' - ', array_filter( array( esc_attr( $field['bank_name'] ), esc_attr( $field['bank_branch'] ),esc_attr( $field['bank_type'] ) ) ) ) . '</strong><br/>';
				    $html .= $number_label . ': <strong>' . wptexturize( $field['value'] ) . '</strong><br/>';
				    $html .= $name_label . ': <strong>' . wptexturize( $field['account_name'] ) . '</strong>'."\n".'</li>';
				}

			    $html .= '</ul>';
	    	}
	    }
        echo apply_filters( 'jp4wc_bank_details', $html, $bankjp_accounts, $order_id );
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting[pending] the payment)
		$order->update_status( 'on-hold', __( 'Awaiting BANK payment', 'woocommerce-for-japan' ) );

		// Reduce stock levels
        wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
    }
}

/**
 * Add the gateway to woocommerce
 */
function add_wc4jp_commerce_gateway( $methods ) {
	$methods[] = 'WC_Gateway_BANK_JP';
	return $methods;
}

if(get_option('wc4jp-bankjp')) add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_commerce_gateway' );

