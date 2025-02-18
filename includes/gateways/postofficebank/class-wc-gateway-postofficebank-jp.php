<?php
/**
 * Class WC_Gateway_PostOfficeBank_JP file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Post Office Bank Payment Gateway in Japanese
 *
 * Provides a Post Office Bank Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 		WC_Gateway_PostOfficeBank_JP
 * @extends		WC_Payment_Gateway
 * @version		2.6.15
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_PostOfficeBank_JP extends WC_Payment_Gateway {

	/**
     * Settings parameter
     *
     * @var mixed
     */
	public $account_details;

	/**
	 * Settings parameter
	 *
	 * @var mixed
	 */
	public $settings;

	/**
	 * Bank symbol
	 *
	 * @var mixed
	 */
	public $bank_symbol;

	/**
	 * Account number
	 *
	 * @var mixed
	 */
	public $account_number;

	/**
	 * Account name
	 *
	 * @var mixed
	 */
	public $account_name;

	/**
	 * Gateway instructions that will be added to the thank you page and emails.
	 *
	 * @var string
	 */
	public $instructions;

	/**
	 * Display location of the transfer account information on the e-mail.
	 *
	 * @var int
	 */
	public $display_location;

	/**
	 * Display order of instructions and transfer account.
	 *
	 * @var bool
	 */
	public $display_order;

	/**
	 * Constructor for the gateway.
	 */
    public function __construct() {
		$this->id                 = 'postofficebank';
	    $this->icon               = apply_filters( 'woocommerce_postofficebank_icon', JP4WC_URL_PATH . '/assets/images/jp4wc-jppost-bank.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Postal transfer', 'woocommerce-for-japan' );
		$this->method_description = __( 'Allows payments by Postal transfer in Japan.', 'woocommerce-for-japan' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );

		// Post Office BANK Japan account fields shown on the thanks page and in emails
		$this->account_details = get_option( 'woocommerce_postofficebankjp_accounts',
			array(
				array(
					'bank_symbol'    => $this->get_option( 'bank_symbol' ),
					'account_number' => $this->get_option( 'account_number' ),
					'account_name'   => $this->get_option( 'account_name' ),
				)
			)
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails
		if( $this->display_location ){
			add_action( 'woocommerce_email_order_details', array( $this, 'email_instructions' ), $this->display_location, 3 );
		}else{
			add_action( 'woocommerce_email_order_details', array( $this, 'email_instructions' ), 9, 3 );
		}
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
	     public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Postal transfer', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Postal transfer', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Make your payment directly into our Post Office Bank account.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'instructions'    => array(
				'title'       => __( 'Instructions', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-for-japan' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'display_location' => array(
				'title'       => __( 'Transfer account display Location', 'woocommerce-for-japan' ),
				'type'        => 'number',
				'description' => __( 'The location of the transfer account information on the e-mail.', 'woocommerce-for-japan' ) . ' ' .
					__( 'Unless customized by an extension plugin, 9 will be before the order and 15 will be after the order.', 'woocommerce-for-japan' ),
				'default'     => 9,
				'desc_tip'    => true,
			),
			'display_order'   => array(
				'title'       => __( 'Display order of instructions and transfer account', 'woocommerce-for-japan' ),
				'type'        => 'checkbox',
				'description' => __( 'Check this box if you want to reverse the order in which instructions and transfer accounts are displayed.', 'woocommerce-for-japan' ),
				'default'     => 'no',
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
            <th scope="row" class="titledesc"><?php _e( 'Post Office Account Details', 'woocommerce-for-japan' ); ?>:</th>
            <td class="forminp" id="bankjp_accounts">
			    <table class="widefat wc_input_table sortable" cellspacing="0">
		    		<thead>
		    			<tr>
		    				<th class="sort">&nbsp;</th>
			            	<th><?php _e( 'Bank Symbol', 'woocommerce-for-japan' ); ?></th>
			            	<th><?php _e( 'Account Number', 'woocommerce-for-japan' ); ?></th>
		    				<th><?php _e( 'Account Name', 'woocommerce-for-japan' ); ?></th>
		    			</tr>
		    		</thead>
		    		<tfoot>
		    			<tr>
		    				<th colspan="4"><a href="#" class="add button"><?php _e( '+ Add Account', 'woocommerce-for-japan' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Remove selected account(s)', 'woocommerce-for-japan' ); ?></a></th>
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
		                			<td><input type="text" value="' . esc_attr( $account['bank_symbol'] ) . '" name="postofficebankjp_bank_symbol[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['account_number'] ) . '" name="postofficebankjp_account_number[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $account['account_name'] ) . '" name="postofficebankjp_account_name[' . $i . ']" /></td>
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
		                			<td><input type="text" name="postofficebankjp_bank_symbol[' + size + ']" /></td>\
		                			<td><input type="text" name="postofficebankjp_account_number[' + size + ']" /></td>\
		                			<td><input type="text" name="postofficebankjp_account_name[' + size + ']" /></td>\
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

    	if ( isset( $_POST['postofficebankjp_account_name'] ) ) {

			$account_names   = wc_clean( wp_unslash( $_POST['postofficebankjp_account_name'] ) );
			$account_numbers = wc_clean( wp_unslash( $_POST['postofficebankjp_account_number'] ) );
			$bank_symbol     = wc_clean( wp_unslash( $_POST['postofficebankjp_bank_symbol'] ) );

			foreach ( $account_names as $i => $name ) {
				if ( ! isset( $account_names[ $i ] ) ) {
					continue;
				}

	    		$accounts[] = array(
					'bank_symbol'    => $bank_symbol[ $i ],
					'account_number' => $account_numbers[ $i ],
	    			'account_name'   => $account_names[ $i ],
	    		);
	    	}
    	}
    	update_option( 'woocommerce_postofficebankjp_accounts', $accounts );
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page( $order_id ) {
		$instructions = '';
		if ( $this->instructions ) {
        	$instructions = wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) );
        }
        $bank_detail = $this->html_bank_details( $order_id );
		if( $this->display_order == 'yes' ){
			echo $bank_detail;
			echo $instructions;
		}else{
			echo $instructions;
			echo $bank_detail;
		}
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order order
     * @param bool Sent to admin
     * @param bool Plain text
     * @return void
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	    $payment_method = $order->get_payment_method();
		$order_status = $order->get_status();
    	if ( ! $sent_to_admin && $this->id == $payment_method && ('on-hold' === $order_status || 'pending' === $order_status ) ) {
			if ( $this->instructions ) {
				$instructions = wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$order_id = $order->get_id();
			if( $plain_text ){
				$bank_detail = $this->text_bank_details( $order_id );
			}else{
				$bank_detail = $this->html_bank_details( $order_id );
			}
			if( $this->display_order == 'yes' ){
				echo $bank_detail;
				echo $instructions;
			}else{
				echo $instructions;
				echo $bank_detail;
			}
		}
    }

    /**
     * Get bank details and place into a list format
     */
    private function html_bank_details( $order_id = '' ) {
    	if ( empty( $this->account_details ) ) {
    		return;
    	}

    	$postofficebankjp_accounts = apply_filters( 'woocommerce_postofficebankjp_accounts', $this->account_details );

    	if ( ! empty( $postofficebankjp_accounts ) ) {
			$html = '<h2>' . __( 'Post Office Bank Account Details', 'woocommerce-for-japan' ) . '</h2>' . PHP_EOL;
			$html .= '<ul class="order_details postofficebankjp_details">' . PHP_EOL;
	    	foreach ( $postofficebankjp_accounts as $postofficebankjp_account ) {

	    		$postofficebankjp_account = (object) $postofficebankjp_account;

	    		// BANK account fields shown on the thanks page and in emails
				$account_field = apply_filters( 'woocommerce_postofficebankjp_account_fields', array(
					'account_number'=> array(
						'name_label' => __( 'Account Name', 'woocommerce-for-japan' ),
						'name' => $postofficebankjp_account->account_name,
						'number_label' => __( 'Account Number', 'woocommerce-for-japan' ),
						'bank_symbol' => $postofficebankjp_account->bank_symbol,
						'value' => $postofficebankjp_account->account_number
					)
				), $order_id );

				$html .= '<li class="account_number">'.wptexturize($account_field['account_number']['name_label']).': <strong>' . wptexturize($account_field['account_number']['name']) . '</strong>' . PHP_EOL;
			    $html .= esc_attr( $account_field['account_number']['number_label'] ) . ': <strong>' . wptexturize($account_field['account_number']['bank_symbol']).'-'.wptexturize( $account_field['account_number']['value'] ) . '</strong></li>' . PHP_EOL;
	 		}
			$html .= '</ul>';
			return apply_filters( 'jp4wc_html_po_bank_details', $html, $postofficebankjp_accounts, $order_id );
		}
    }

    /**
	 * Get bank details and place into a list format for emails.
	 
	 */
	private function text_bank_details( $order_id = '' ) {
    	if ( empty( $this->account_details ) ) {
    		return;
    	}

    	$postofficebankjp_accounts = apply_filters( 'woocommerce_postofficebankjp_accounts', $this->account_details );

    	if ( ! empty( $postofficebankjp_accounts ) ) {
			$text = __( 'Post Office Bank Account Details', 'woocommerce-for-japan' ) . "\n" . PHP_EOL;
	    	foreach ( $postofficebankjp_accounts as $postofficebankjp_account ) {

	    		$postofficebankjp_account = (object) $postofficebankjp_account;

	    		// BANK account fields shown on the thanks page and in emails
				$account_field = apply_filters( 'woocommerce_postofficebankjp_account_fields', array(
					'account_number'=> array(
						'name_label' => __( 'Account Name', 'woocommerce-for-japan' ),
						'name' => $postofficebankjp_account->account_name,
						'number_label' => __( 'Account Number', 'woocommerce-for-japan' ),
						'bank_symbol' => $postofficebankjp_account->bank_symbol,
						'value' => $postofficebankjp_account->account_number
					)
				), $order_id );

				$text .= wptexturize($account_field['account_number']['name_label']).': ' . wptexturize($account_field['account_number']['name']) . "\n" . PHP_EOL;
			    $text .= esc_attr( $account_field['account_number']['number_label'] ) . ': ' . wptexturize($account_field['account_number']['bank_symbol']).'-'.wptexturize( $account_field['account_number']['value'] ) . "\n" . PHP_EOL;
	 		}
			$text .= "\n" . PHP_EOL;
			return apply_filters( 'jp4wc_text_po_bank_details', $text, $postofficebankjp_accounts, $order_id );
		}
    }

	/**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status( 'on-hold', __( 'Awaiting Post Office BANK payment', 'woocommerce-for-japan' ) );

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
function add_wc4jp_commerce_postofficebank_gateway( $methods ) {
	$methods[] = 'WC_Gateway_PostOfficeBANK_JP';
	return $methods;
}

if(get_option('wc4jp-postofficebank')) add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_commerce_postofficebank_gateway' );
