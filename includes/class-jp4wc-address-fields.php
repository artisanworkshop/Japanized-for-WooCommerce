<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.2.21
 * @category    Address Setting for Japan
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AddressField4jp{

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        //WPML check
        if( defined('ICL_LANGUAGE_CODE') and ICL_LANGUAGE_CODE != 'ja' ) return;
		// Default address fields.
		add_filter( 'woocommerce_default_address_fields', array( $this, 'address_fields'));
        // Add yomigana fields
        add_filter( 'woocommerce_default_address_fields', array( $this, 'add_yomigana_fields'));
		// MyPage Edit And Checkout fields.
		add_filter( 'woocommerce_billing_fields', array( $this, 'billing_address_fields'));
		add_filter( 'woocommerce_shipping_fields', array( $this, 'shipping_address_fields'), 20 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'address_replacements'),20,2);
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_formats'),20);
		// My Account Display for address
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'formatted_address'),20,3);//template/myaccount/my-address.php
		// Check Out Display for address
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'jp4wc_billing_address'),10,2);
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'jp4wc_shipping_address'),20,2);
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'jp4wc_shipping_address_add_phone'),20,3);
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_order_data_after_shipping_address'), 10 );
		// include get_order function
		add_filter( 'woocommerce_get_order_address', array( $this, 'jp4wc_get_order_address'),20,3);//includes/abstract/abstract-wc-order.php
		// FrontEnd CSS file
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style'), 99 );
		// Admin Edit Address
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'admin_billing_address_fields'));
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'admin_shipping_address_fields'));
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'admin_customer_meta_fields'));
        // Automatic address registration by postal code
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'auto_zip2address_billing'), 10 );
        add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'auto_zip2address_shipping'), 10 );
        add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'auto_zip2address_billing'), 10 );
        add_action( 'woocommerce_after_edit_address_form_shipping', array( $this, 'auto_zip2address_shipping'), 10 );

        // Remove checkout fields for PayPal cart checkout
        add_filter( 'woocommerce_default_address_fields' , array( $this, 'remove_checkout_fields_for_paypal') );
    }

    /**
     * Address correspondence in Japan
     *
     * @since  1.2
     * @version 2.2.7
     * @param  array $fields
     * @return array
     */
	public function address_fields( $fields ) {

        $fields['last_name']['class'] = array( 'form-row-first' );
        $fields['last_name']['priority'] = 10;
        $fields['first_name']['class'] = array( 'form-row-last' );
        $fields['first_name']['priority'] = 20;
        $fields['postcode']['class'] = array( 'form-row-first' );
        $fields['state']['class'] = array( 'form-row-last' );

		return $fields;
	}

    /**
     * Yomigana Setting
     *
     * @since 2.2.7
     * @param  array $fields
     * @return array
     */
    public function add_yomigana_fields( $fields ){
        if(get_option( 'wc4jp-yomigana')){
            $fields['yomigana_last_name'] = array(
                'label'    => __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ),
                'required' => false,
                'class'    => array( 'form-row-first' ),
                'priority'     => 25,
            );
            $fields['yomigana_first_name'] = array(
                'label'    => __( 'First Name ( Yomigana )', 'woocommerce-for-japan' ),
                'required' => false,
                'class'    => array( 'form-row-last' ),
                'clear'    => true,
                'priority'     => 28,
            );
        }
        if(get_option( 'wc4jp-yomigana-required')){
            $fields['yomigana_last_name']['required'] = true;
            $fields['yomigana_first_name']['required'] = true;
        }
        return $fields;
    }
	/**
     * Japan corresponding set of billing address information
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function billing_address_fields( $fields ) {
		$address_fields = $fields;
		if(!get_option( 'wc4jp-company-name'))unset($address_fields['billing_company']);
		return $address_fields;
	}

    /**
     * Japan corresponding set of shipping address information
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function shipping_address_fields( $fields ) {
		$address_fields = $fields;

		$address_fields['shipping_phone'] = array(
			'label' 		=> __( 'Shipping Phone', 'woocommerce-for-japan' ),
			'required' 		=> true,
			'class' 		=> array( 'form-row-wide' ),
			'clear'			=> true,
			'validate'		=> array( 'phone' ),
			'priority' => 100,
		);
		if(!get_option( 'wc4jp-company-name'))unset($address_fields['shipping_company']);
		return $address_fields;
	}

    /**
     * Substitute address parts into the string for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @param  array $args
     * @return array
     */
	public function address_replacements( $fields, $args ) {
		$fields['{name}'] = $args['last_name'] . ' ' . $args['first_name'];
		$fields['{name_upper}'] = strtoupper( $args['last_name'] . ' ' . $args['first_name'] );
		if(get_option( 'wc4jp-yomigana')){
			if(isset($args['yomigana_last_name']))$fields['{yomigana_last_name}'] = $args['yomigana_last_name'];
			if(isset($args['yomigana_first_name']))$fields['{yomigana_first_name}'] = $args['yomigana_first_name'];
		}
		if(version_compare( WC_VERSION, '3.1', '<=' )){
			$fields['{phone}'] = $args['phone'];
		}
		if(is_order_received_page()){
			$fields['{phone}'] = $args['phone'];
		}

		return $fields;
	}

    /**
     * Setting address formats for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function address_formats( $fields ) {
		//honorific suffix
		$honorific_suffix = '';
		if(get_option('wc4jp-honorific-suffix'))$honorific_suffix = '様';

        //PayPal Payment compatible
		if( isset($_GET['woo-paypal-return']) && $_GET['woo-paypal-return'] == true && isset($_GET['token'])){
            $set_yomigana = "";
        }else{
            $set_yomigana = "\n{yomigana_last_name} {yomigana_first_name}";
        }
		if(version_compare( WC_VERSION, '3.1', '>=' )){
			if(get_option( 'wc4jp-company-name') and get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}".$set_yomigana."\n{last_name} {first_name}".$honorific_suffix."\n {country}";
			}
            if(get_option( 'wc4jp-company-name') and !get_option( 'wc4jp-yomigana')){
                $fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}".$honorific_suffix."\n {country}";
            }
			if(!get_option( 'wc4jp-company-name') and get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}".$set_yomigana."\n{last_name} {first_name}".$honorific_suffix."\n {country}";
			}
			if(!get_option( 'wc4jp-company-name') and !get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{last_name} {first_name}".$honorific_suffix."\n {country}";
			}
		}else{
			if(get_option( 'wc4jp-company-name') and get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}".$set_yomigana."\n{last_name} {first_name}".$honorific_suffix."\n {phone}\n {country}";
			}
            if(get_option( 'wc4jp-company-name') and !get_option( 'wc4jp-yomigana')){
                $fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}".$honorific_suffix."\n {phone}\n {country}";
            }
			if(!get_option( 'wc4jp-company-name') and get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}".$set_yomigana."\n{last_name} {first_name}".$honorific_suffix."\n {phone}\n {country}";
			}
			if(!get_option( 'wc4jp-company-name') and !get_option( 'wc4jp-yomigana')){
				$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{last_name} {first_name}".$honorific_suffix."\n {phone}\n {country}";
			}			
		}
		if(is_cart()){
			$fields['JP'] = "〒{postcode}{state}{city}{address_1}{address_2} {country}";
		}
		if(is_order_received_page()){
			$fields['JP'] = $fields['JP']."\n {phone}";
		}
		return $fields;
	}

    /**
     * Setting account formatted address for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @param  string $customer_id
     * @param  string $name
     * @return array
     */
	public function formatted_address( $fields, $customer_id, $name) {
	    $fields['yomigana_first_name']  = get_user_meta( $customer_id, $name . '_yomigana_first_name', true );
	    $fields['yomigana_last_name']  = get_user_meta( $customer_id, $name . '_yomigana_last_name', true );
	    $fields['phone']  = get_user_meta( $customer_id, $name . '_phone', true );

		return $fields;
	}

    /**
     * Setting account formatted address for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @param  object $args
     * @return array
     */
	public function jp4wc_billing_address( $fields, $args) {
	    $order_id = $args->get_id();
	    $fields['yomigana_first_name'] = get_post_meta( $order_id, '_billing_yomigana_first_name', true );
	    $fields['yomigana_last_name'] = get_post_meta( $order_id, '_billing_yomigana_last_name', true );
	    $fields['phone'] = get_post_meta( $order_id, '_billing_phone', true );

		if($fields['country'] == '')$fields['country'] = 'JP';

		return $fields;
	}

    /**
     * Setting a formatted shipping address for the order, in Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @param  object $args
     * @return array
     */
	public function jp4wc_shipping_address( $fields, $args) {
        if(isset($fields['first_name'])) {
            $order_id = $args->get_id();
            $fields['yomigana_first_name'] = get_post_meta($order_id, '_shipping_yomigana_first_name', true);
            $fields['yomigana_last_name'] = get_post_meta($order_id, '_shipping_yomigana_last_name', true);
            $fields['phone'] = get_post_meta($order_id, '_shipping_phone', true);
            if ($fields['country'] == '') $fields['country'] = 'JP';
        }

		return $fields;
	}

    /**
     * Set shipping phone display for e-mail and complete page
     *
     * @since 2.2.13
     * @param string address
     * @param $raw_address
     * @param $object
     * @return string
     */
	public function jp4wc_shipping_address_add_phone( $address, $raw_address, $object ){
	    if( is_order_received_page() || is_admin() || version_compare( WC_VERSION, '5.6', '>=' )){
            return $address;
        }else{
            $field_value = get_post_meta( $object->get_id(), '_shipping_phone', true );
            $field_value = wc_make_phone_clickable( $field_value );
            $phone_display = '<br />'. wp_kses_post( $field_value ) . '<br />';
            return $address.$phone_display;
        }
    }
    /**
     * Display phone number of shipping address in admin screen
     *
     * @since  1.2
     * @version 2.0.0
     * @param  object $order
     */
	public function admin_order_data_after_shipping_address( $order ){
		$order_id = $order->get_id();
		$field['label'] = __( 'Shipping Phone', 'woocommerce-for-japan' );
		$field_value = get_post_meta( $order_id, '_shipping_phone', true );
		$field_value = wc_make_phone_clickable( $field_value );
		echo '<div style="display:block;clear:both;"><p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p></div>';
	}

    /**
     * Setting address for the order, in Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $address
     * @param  string $type 'billing' or 'shipping'
     * @param  object $args
     * @return array The stored address after filter.
     */
	public function jp4wc_get_order_address( $address, $type, $args ){
		$order_id = $args->get_id();
		if ( 'billing' === $type ) {
			$address['yomigana_first_name'] = $args->get_meta( '_billing_yomigana_first_name', true );
			$address['yomigana_last_name'] = $args->get_meta( '_billing_yomigana_last_name', true );
		}else{
			$address['yomigana_first_name'] = $args->get_meta( '_shipping_yomigana_first_name', true );
			$address['yomigana_last_name'] = $args->get_meta( $order_id, '_shipping_yomigana_last_name', true );
			$address['phone'] = $args->get_meta( $order_id, '_shipping_phone', true );
		}
		return $address;
	}

	//FrontEnd CSS file function
	public function frontend_enqueue_style() {
		if(is_order_received_page()){
			wp_register_style( 'custom_order_received_jp4wc', JP4WC_URL_PATH . 'assets/css/order-received-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'custom_order_received_jp4wc' );
		}
		if(is_account_page()){
			wp_register_style( 'edit_account_jp4wc', JP4WC_URL_PATH . 'assets/css/edit-account-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'edit_account_jp4wc' );
		}
	}

    /**
     * Setting edit item in the billing address of the admin screen for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function admin_billing_address_fields( $fields ) {
		foreach($fields as $key => $value){
			$new_fields[$key] = $value;
		}
		$fields = array(
			'last_name' => $new_fields['last_name'],
			'first_name' => $new_fields['first_name'],
            'yomigana_last_name' => array(
                'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
                'show'	=> false
            ),
            'yomigana_first_name' => array(
                'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
                'show'	=> false
            ),
			'country' => $new_fields['country'],
			'postcode' => $new_fields['postcode'],
			'state' => $new_fields['state'],
			'city' => $new_fields['city'],
			'company' => $new_fields['company'],
			'address_1' => $new_fields['address_1'],
			'address_2' => $new_fields['address_2'],
			'email' => $new_fields['email'],
			'phone' => $new_fields['phone'],
		);
		if(!get_option( 'wc4jp-company-name'))unset($fields['company']);
		if(!get_option( 'wc4jp-yomigana')){
			unset($fields['yomigana_last_name']);
			unset($fields['yomigana_first_name']);
		};

		return $fields;
	}

    /**
     * Setting edit item in the shipping address of the admin screen for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function admin_shipping_address_fields( $fields ) {
		foreach($fields as $key => $value){
			$new_fields[$key] = $value;
		}
		$fields = array(
			'last_name' => $new_fields['last_name'],
			'first_name' => $new_fields['first_name'],
            'yomigana_last_name' => array(
                'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
                'show'	=> false
            ),
            'yomigana_first_name' => array(
                'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
                'show'	=> false
            ),
			'country' => $new_fields['country'],
			'postcode' => $new_fields['postcode'],
			'state' => $new_fields['state'],
			'city' => $new_fields['city'],
			'company' => $new_fields['company'],
			'address_1' => $new_fields['address_1'],
			'address_2' => $new_fields['address_2'],
			'phone' => array(
				'label' => __( 'Phone', 'woocommerce-for-japan' ),
				'show'	=> false
			)
		);

		if(!get_option( 'wc4jp-company-name'))unset($fields['company']);
		if(!get_option( 'wc4jp-yomigana'))unset($fields['yomigana_last_name'],$fields['yomigana_first_name']);

		return $fields;
	}
    /**
     * Setting Address Fields for the edit user pages for Japanese.
     *
     * @since  1.2
     * @version 2.0.0
     * @param  array $fields
     * @return array
     */
	public function admin_customer_meta_fields( $fields ){
		$customer_meta_fields = $fields;
		//Billing fields
		$billing_fields = $fields['billing']['fields'];
		$customer_meta_fields['billing']['fields'] = array(
			'billing_last_name' => $billing_fields['billing_last_name'],
			'billing_first_name' => $billing_fields['billing_first_name'],
			'billing_yomigana_last_name' => array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'billing_yomigana_first_name' => array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'billing_company'  => $billing_fields['billing_company'],
			'billing_country'  => $billing_fields['billing_country'],
			'billing_postcode' => $billing_fields['billing_postcode'],
			'billing_state'  => $billing_fields['billing_state'],
			'billing_city'  => $billing_fields['billing_city'],
			'billing_address_1'  => $billing_fields['billing_address_1'],
			'billing_address_2'  => $billing_fields['billing_address_2'],
			'billing_phone'  => $billing_fields['billing_phone'],
			'billing_email'  => $billing_fields['billing_email'],
		);
		//Shipping fields
		$shipping_fields = $fields['shipping']['fields'];
		$customer_meta_fields['shipping']['fields'] = array(
			'shipping_last_name' => $shipping_fields['shipping_last_name'],
			'shipping_first_name' => $shipping_fields['shipping_first_name'],
			'shipping_yomigana_last_name' => array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'shipping_yomigana_first_name' => array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'shipping_company'  => $shipping_fields['shipping_company'],
			'shipping_country'  => $shipping_fields['shipping_country'],
			'shipping_postcode' => $shipping_fields['shipping_postcode'],
			'shipping_state'  => $shipping_fields['shipping_state'],
			'shipping_city'  => $shipping_fields['shipping_city'],
			'shipping_address_1'  => $shipping_fields['shipping_address_1'],
			'shipping_address_2'  => $shipping_fields['shipping_address_2'],
			'shipping_phone'  => array(
				'label' => __( 'Phone', 'woocommerce-for-japan' ),
				'description' => '',
			),
		);
		if(!get_option( 'wc4jp-company-name'))unset($customer_meta_fields['billing']['fields']['billing_company'], $customer_meta_fields['shipping']['fields']['shipping_company']);
		if(!get_option( 'wc4jp-yomigana'))unset($customer_meta_fields['billing']['fields']['billing_yomigana_last_name'], $customer_meta_fields['billing']['fields']['billing_yomigana_first_name'], $customer_meta_fields['shipping']['fields']['shipping_yomigana_last_name'], $customer_meta_fields['shipping']['fields']['shipping_yomigana_first_name']);
		return $customer_meta_fields;
	}

	// Automatic input from postal code to Address for billing
	public function auto_zip2address_billing(){
		$this->auto_zip2address( 'billing' );
	}

	// Automatic input from postal code to Address for shipping
	public function auto_zip2address_shipping(){
		$this->auto_zip2address( 'shipping' );
	}

    /**
     * Display JavaScript code for automatic registration of address by zip code.
     *
     * @param string $method 'billing' or 'shipping'
     */
    function auto_zip2address($method){
	    if(version_compare( WC_VERSION, '3.6', '>=' )){
            $jp4wc_countries = new WC_Countries;
            $states = $jp4wc_countries->get_states();
        }else{
            global $states;
        }
		if(get_option( 'wc4jp-yahoo-app-id' )){
			$yahoo_app_id = get_option( 'wc4jp-yahoo-app-id' );
		}else{
			$yahoo_app_id = 'dj0zaiZpPWZ3VWp4elJ2MXRYUSZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-';
		}
		$state_id = 'select2-'.$method.'_state-container';
		if(get_option( 'wc4jp-zip2address' )){
			wp_enqueue_script( 'yahoo-app','https://map.yahooapis.jp/js/V1/jsapi?appid='.$yahoo_app_id,array('jquery'),JP4WC_VERSION);
			?>
<script type="text/javascript">
// Search Japanese Postal number
jQuery(function($) {
    // Method to automatically insert hyphen in postal code
    function insertStr(input){
        return input.slice(0, 3) + '-' + input.slice(3,input.length);
    }

    $(document).ready(function(){
        $("#<?php echo $method;?>_postcode").keyup(function(e){
            let zip = $("#<?php echo $method;?>_postcode").val(),
                zipCount = zip.length;

            // Control the delete key so that the hyphen addition process does not work (8 is Backspace, 46 is Delete)
            let key = e.keyCode || e.charCode;
            if( key === 8 || key === 46 ){
                return false;
            }

            if(zipCount === 3){
                $("#<?php echo $method;?>_postcode").val(insertStr(zip));
            }else if( zipCount > 7) {
                const url = "https://map.yahooapis.jp/search/zip/V1/zipCodeSearch";
                let param = {
                    appid: "<?php echo $yahoo_app_id;?>",
                    output: "json",
                    query: $("#<?php echo $method;?>_postcode").val()
                };
                $.ajax({
                    url: url,
                    data: param,
                    dataType: "jsonp",
                    success: function(result) {
                        var ydf = new Y.YDF(result);
                        // Display Address from Zip
                        dispZipToAddress<?php echo $method;?>(ydf);
                    },
                    error: function() {
                        // Error handling
                    }
                });
            }
        });
    });
});
// Display Address from Zipcode
function dispZipToAddress<?php echo $method;?>(ydf) {
	var address = ydf.features[0].property.Address;
	var state = address.substr( 0, 3 );
	var states = new Array();

	<?php foreach((array)$states['JP'] as $key => $value){
		$key = substr($key, 2);
		if($key == '14' || $key == "30" || $key == "46"){
			echo 'states['.$key.'] = "'.mb_substr($value, 0, 3).'";';
		}else{
			echo 'states['.$key.'] = "'.$value.'";';
		}
	}
	?>

	var state_id = jQuery.inArray(state, states);
	jQuery("#<?php echo $method;?>_state").val(state_id);
	var text_num = 3;
	if(state_id == "14" || state_id == "30" || state_id == "46"){
		text_num = 4;
	}
	var city = address.substr( text_num );
	jQuery("#<?php echo $method;?>_city").val(city);
	states[14] = "<?php echo $states['JP']['JP14'];?>";
	states[30] = "<?php echo $states['JP']['JP30'];?>";
	states[46] = "<?php echo $states['JP']['JP46'];?>";
	if(state_id > 9){
	document.getElementById("<?php echo $method;?>_state").value = "JP" + state_id;
	}else{
	document.getElementById("<?php echo $method;?>_state").value = "JP0" + state_id;
	}
	document.getElementById("<?php echo $state_id;?>").innerHTML = states[state_id];
}
</script>
		<?php
		}
	}
    /**
     * Address correspondence in Japan
     *
     * @since  2.2.7
     * @param  array $fields
     * @return array $fields
     */
	public function remove_checkout_fields_for_paypal($fields){
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $enabled_gateways = array();
        foreach ($gateways as $key => $value){
            if($value->enabled == 'yes'){
                $enabled_gateways[] = $key;
            }
        }
        $paypal_flag = in_array('ppec_paypal', $enabled_gateways);
        if(get_option( 'wc4jp-yomigana' ) && $paypal_flag == 1){
            $fields['yomigana_last_name']['required'] = false;
            $fields['yomigana_first_name']['required'] = false;
        }
        return $fields;
    }
}
// Address Fields Class load
if(!get_option('wc4jp-no-ja')) new AddressField4jp();
