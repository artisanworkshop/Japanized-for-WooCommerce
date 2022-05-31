<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.3.3
 * @package 	Admin Screen
 * @author 		ArtisanWorkshop
 */
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Admin_Screen {
	/**
	 * Error messages.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Update messages.
	 *
	 * @var array
	 */
	public $messages = array();

    /**
     * Japanized for WooCommerce Framework.
     *
     * @var object
     */
	public $jp4wc_plugin;
	public $prefix;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 80 );
		add_action( 'admin_menu', array( $this, 'jp4wc_admin_menu' ) ,60 );
		add_action( 'admin_init', array( $this, 'jp4wc_setting_init') );
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'jp4wc_dashboard_widget' ), 1 );
		$this->jp4wc_plugin = new Framework\JP4WC_Plugin();
		$this->prefix =  'wc4jp-';
	}

	/**
	 * Admin Menu
	 */
	public function jp4wc_admin_menu() {
		add_submenu_page( 'woocommerce', __( 'For Japanese', 'woocommerce-for-japan' ), __( 'For Japanese', 'woocommerce-for-japan' ), 'manage_woocommerce', 'wc4jp-options', array( $this, 'jp4wc_output_display' ) );
	}

	/**
	 * Admin Screen output
	 */
	public function jp4wc_output_display() {
        include( 'views/html-admin-screen.php' );
	}

	/**
	 * Admin page for Setting
	 */
	public function admin_setting_page() {
		include( 'views/html-admin-setting-screen.php' );
	}

	/**
	 * Admin page for infomation
	 */
	public function admin_info_page() {
		include( 'views/html-admin-info-screen.php' );
	}
	
	function jp4wc_setting_init(){

		register_setting(
			'jp4wc_options',
			'jp4wc_options_name',
			array( $this, 'validate_options' )
		);

		// Address Display Setting
		add_settings_section(
			'jp4wc_general', __( 'Address Display Setting', 'woocommerce-for-japan' ),
			'',
			'jp4wc_setting'
		);
		add_settings_field(
			'jp4wc_options_yomigana',
			__( 'Name Yomigana', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_yomigana' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
        add_settings_field(
            'jp4wc_options_yomigana_required',
            __( 'Required Name Yomigana', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_options_yomigana_required' ),
            'jp4wc_setting',
            'jp4wc_general'
        );
		add_settings_field(
			'jp4wc_options_honorific_suffix',
			__( 'Honorific suffix(Sama)', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_honorific_suffix' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_company_name',
			__( 'Company Name', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_company_name' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_zip2address',
			__( 'Automatic zip code entry', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_zip2address' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_yahoo_app_id',
			__( 'Yahoo APP ID', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_yahoo_app_id' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_no_ja',
			__( 'No Japanese Address', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_no_ja' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_free_shipping',
			__( 'Free Shipping Display', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_free_shipping' ),
			'jp4wc_setting',
			'jp4wc_general'
		);
		add_settings_field(
			'jp4wc_options_custom_email_customer_name',
			__( 'Custamize Email template at customer name', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_custom_email_customer_name' ),
			'jp4wc_setting',
			'jp4wc_general'
		);

		// Delivery date designation
		add_settings_section(
			'jp4wc_delivery_date',
			__( 'Delivery date designation', 'woocommerce-for-japan' ),
			'',
			'jp4wc_shipment'
        );
		add_settings_field(
			'jp4wc_delivery_date_designation',
			__( 'Preferred delivery date', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_delivery_date_designation' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
        add_settings_field(
            'jp4wc_delivery_date_required',
            __( 'Preferred delivery date required', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_delivery_date_required' ),
            'jp4wc_shipment',
            'jp4wc_delivery_date'
        );
		add_settings_field(
			'jp4wc_start_date',
			__( 'Start Date', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_start_date' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
		add_settings_field(
			'jp4wc_reception_period',
			__( 'Delivery Term', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_reception_period' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
		add_settings_field(
			'jp4wc_unspecified_date',
			__( 'Unspecified date description', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_unspecified_date' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
		add_settings_field(
			'jp4wc_date_format',
			__( 'Date format', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_date_format' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
        add_settings_field(
            'jp4wc_day_of_week',
            __( 'Display day of week', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_day_of_week' ),
            'jp4wc_shipment',
            'jp4wc_delivery_date'
        );
		add_settings_field(
			'jp4wc_delivery_deadline',
			__( 'Delivery deadline', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_delivery_deadline' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
		add_settings_field(
			'jp4wc_no_delivery_weekday',
			__( 'No delivery weekday', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_no_delivery_weekday' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);
		add_settings_field(
			'jp4wc_holiday_term',
			__( 'Holiday term', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_holiday_term' ),
			'jp4wc_shipment',
			'jp4wc_delivery_date'
		);

		// Delivery time designation
		add_settings_section(
			'jp4wc_delivery_time',
			__( 'Delivery Time designation', 'woocommerce-for-japan' ),
			'',
			'jp4wc_shipment'
		);
		add_settings_field(
			'jp4wc_delivery_time_zone',
			__( 'Delivery time zone', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_delivery_time_zone' ),
			'jp4wc_shipment',
			'jp4wc_delivery_time'
		);
        add_settings_field(
            'jp4wc_delivery_time_zone_required',
            __( 'Delivery time zone required', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_delivery_time_zone_required' ),
            'jp4wc_shipment',
            'jp4wc_delivery_time'
        );
		add_settings_field(
			'jp4wc_unspecified_time',
			__( 'Unspecified Time description', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_unspecified_time' ),
			'jp4wc_shipment',
			'jp4wc_delivery_time'
		);
		add_settings_field(
			'jp4wc_delivery_time_zone_mgn',
			__( 'Delivery time zone management', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_delivery_time_zone_mgn' ),
			'jp4wc_shipment',
			'jp4wc_delivery_time'
		);

		// Payment Method
		add_settings_section(
			'jp4wc_payments',
			__( 'Payment Method', 'woocommerce-for-japan' ),
			'',
			'jp4wc_payment'
		);
		add_settings_field(
			'jp4wc_options_bankjp',
			__( 'BANK PAYMENT IN JAPAN', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_bankjp' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);
		add_settings_field(
			'jp4wc_options_postofficebank',
			__( 'Postal transfer', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_postofficebank' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);
		add_settings_field(
			'jp4wc_options_atstore',
			__( 'Pay at store', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_atstore' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);
		add_settings_field(
			'jp4wc_options_cod2',
			__( 'COD for Subscriptions', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_cod2' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);
		add_settings_field(
			'jp4wc_options_peachpay',
			__( 'PeachPay', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_peachpay' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);
		add_settings_field(
			'jp4wc_options_paypal',
			__( 'PayPal', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_options_paypal' ),
			'jp4wc_payment',
			'jp4wc_payments'
		);

        // Display of Specified Commercial Transaction Law
        add_settings_section(
            'jp4wc_laws',
            __( 'Specified Commercial Transaction Law', 'woocommerce-for-japan' ),
            '',
            'jp4wc_law'
        );
        add_settings_field(
            'jp4wc_law_shop_name',
            __( 'Shop Name', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_shop_name' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_company_name',
            __( 'Sales company name (company name)', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_company_name' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_owner_name',
            __( 'Owner Name', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_owner_name' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_location',
            __( 'Location', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_location' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_contact',
            __( 'Contact', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_contact' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
		add_settings_field(
			'jp4wc_law_tel',
			__( 'Telephone', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_law_tel' ),
			'jp4wc_law',
			'jp4wc_laws'
		);
        add_settings_field(
            'jp4wc_law_price',
            __( 'Selling price', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_price' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_payment',
            __( 'Payment method', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_payment' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_purchase',
            __( 'Product purchase method', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_purchase' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_delivery',
            __( 'Product delivery time', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_delivery' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_cost',
            __( 'Costs other than product charges', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_cost' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_return',
            __( 'Returns / Cancellations', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_return' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_special',
            __( 'Special conditions', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_special' ),
            'jp4wc_law',
            'jp4wc_laws'
        );
        add_settings_field(
            'jp4wc_law_explanation',
            __( 'Setting method', 'woocommerce-for-japan' ),
            array( $this, 'jp4wc_law_explanation' ),
            'jp4wc_law',
            'jp4wc_laws'
        );

		// affiliates setting
		add_settings_section(
			'jp4wc_affiliates',
			__( 'Affiliate Setting', 'woocommerce-for-japan' ),
			'',
			'jp4wc_affiliate'
		);

		add_settings_field(
			'jp4wc_affiliates_a8',
			__( 'A8.net Setting', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_affiliates_a8' ),
			'jp4wc_affiliate',
			'jp4wc_affiliates'
		);

		add_settings_field(
			'jp4wc_affiliates_felmat',
			__( 'felmat Setting', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_affiliates_felmat' ),
			'jp4wc_affiliate',
			'jp4wc_affiliates'
		);

		register_setting(
			'jp4wc_informations',
			'jp4wc_informations_name'
		);

		// Plugins Informations
		add_settings_section(
			'jp4wc_plugins',
			__( 'Plugins Information', 'woocommerce-for-japan' ),
			'',
			'jp4wc_informations'
		);
		add_settings_field(
			'jp4wc_informations_plugins',
			__( 'Featured Plugins', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_informations_plugins' ),
			'jp4wc_informations',
			'jp4wc_plugins'
		);

		// Professional services Informations
		add_settings_section(
			'jp4wc_services',
			__( 'Professional services Information', 'woocommerce-for-japan' ),
			'',
			'jp4wc_informations'
		);
		add_settings_field(
			'jp4wc_informations_services',
			__( 'Featured Services', 'woocommerce-for-japan' ),
			array( $this, 'jp4wc_informations_services' ),
			'jp4wc_informations',
			'jp4wc_services'
		);

		if( isset( $_POST['_wpnonce']) and isset($_GET['page']) and $_GET['page'] == 'wc4jp-options' ){
			if((isset($_GET['tab']) and $_GET['tab'] == 'setting') or !isset($_GET['tab'])){
				//Save general setting
				$add_methods = array(
					'yomigana',
                    'yomigana-required',
					'honorific-suffix',
					'company-name',
					'zip2address',
					'yahoo-app-id',
					'no-ja',
					'free-shipping',
					'custom-email-customer-name'
				);
				$this->jp4wc_save_methods( $add_methods );
				self::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
			}
			if(isset($_GET['tab']) and $_GET['tab'] == 'payment'){
				//Save payment setting
				$payment_methods = array(
				    'bankjp',
					'postofficebank',
					'atstore',
					'cod2',
					'peachpay',
					'jp4wc-paypal',
				);
				foreach($payment_methods as $payment_method){
					$woocommerce_settings = get_option('woocommerce_'.$payment_method.'_settings');
					if(isset($_POST[$payment_method]) && $_POST[$payment_method]){
						update_option( $this->prefix.$payment_method, $_POST[$payment_method]);
						if(isset($woocommerce_settings)){
							$woocommerce_settings['enabled'] = 'yes';
							update_option( 'woocommerce_'.$payment_method.'_settings', $woocommerce_settings);
						}
					}else{
						update_option( $this->prefix.$payment_method, '');
						if(isset($woocommerce_settings)){
							$woocommerce_settings['enabled'] = 'no';
							update_option( 'woocommerce_'.$payment_method.'_settings', $woocommerce_settings);
						}
					}
				}
				self::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
			}
			if(isset($_GET['tab']) and $_GET['tab'] == 'shipment'){
				//Save shipment setting
				$add_methods = array(
					'delivery-date',
                    'delivery-date-required',
					'start-date',
					'reception-period',
					'unspecified-date',
					'delivery-deadline',
					'no-mon',
					'no-tue',
					'no-wed',
					'no-thu',
					'no-fri',
					'no-sat',
					'no-sun',
					'holiday-start-date',
					'holiday-end-date',
					'delivery-time-zone',
                    'delivery-time-zone-required',
					'unspecified-time',
					'date-format',
                    'day-of-week'
				);
				$this->jp4wc_save_methods( $add_methods );
				//Save timezones setting
				$time_zones = array();
				if ( isset( $_POST['start_time'] ) ) {

					$start_times   = array_map( 'wc_clean', $_POST['start_time'] );
					$end_times = array_map( 'wc_clean', $_POST['end_time'] );

					foreach ( $start_times as $i => $start_time ) {
						if ( ! isset( $start_times[ $i ] ) ) {
							continue;
						}
						$time_zones[] = array(
							'start_time'      => $start_times[ $i ],
							'end_time'      => $end_times[ $i ],
						);
					}
				}
				update_option( 'wc4jp_time_zone_details', $time_zones);
                self::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
			}
            if((isset($_GET['tab']) and $_GET['tab'] == 'law')){
                //Save general setting
                $add_methods = array(
                    'law-shop-name',
                    'law-company-name',
                    'law-owner-name',
                    'law-location',
                    'law-contact',
                    'law-price',
                    'law-payment',
                    'law-purchase',
                    'law-delivery',
                    'law-cost',
                    'law-return',
                    'law-special',
	                'law-tel',
                );
                $this->jp4wc_save_methods( $add_methods );
                self::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
            }
			if((isset($_GET['tab']) and $_GET['tab'] == 'affiliate')){
				//Save affiliate setting
				$add_methods = array(
					'affiliate-a8',
					'affiliate-a8-test',
					'affiliate-a8-pid',
					'affiliate-felmat',
					'affiliate-felmat-pid',
				);
				$this->jp4wc_save_methods( $add_methods );
				self::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Add a message.
	 * @param string $text
	 */
	public function add_message( $text ) {
		$this->messages[] = $text;
	}

	/**
	 * Add an error.
	 * @param string $text
	 */
	public function add_error( $text ) {
		$this->errors[] = $text;
	}

	/**
	 * Output messages + errors.
	 */
	public function show_messages() {
		if ( sizeof( $this->errors ) > 0 ) {
			foreach ( $this->errors as $error ) {
				echo '<div id="message" class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}
		} elseif ( sizeof( $this->messages ) > 0 ) {
			foreach ( $this->messages as $message ) {
				echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * save option.
	 *
     * @param array add　methods array
	 */
	public function jp4wc_save_methods( $add_methods ) {
		foreach($add_methods as $add_method){
			if(isset($_POST[$add_method]) && $_POST[$add_method]){
				update_option( $this->prefix.$add_method, $_POST[$add_method]);
			}else{
				update_option( $this->prefix.$add_method, '');
			}
		}
	}

	/**
	 * Yomigana option.
	 */
	public function jp4wc_options_yomigana() {
		$title = __( 'Name Yomigana', 'woocommerce-for-japan' );
		$description = $this->jp4wc_description_address_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('yomigana', $description, $this->prefix);
	}

    /**
     * Yomigana option.
     */
    public function jp4wc_options_yomigana_required() {
        $description = __( 'Check if you want to require yomigana.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_checkbox('yomigana-required', $description, $this->prefix);
    }

	/**
	 * Honorific Suffix option.
	 */
	public function jp4wc_options_honorific_suffix() {
		$title = __( 'Honorific Suffix(Sama)', 'woocommerce-for-japan' );
		$description = $this->jp4wc_description_address_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('honorific-suffix', $description, $this->prefix);
	}

	/**
	 * Company Name option.
	 */
	public function jp4wc_options_company_name() {
		$title = __( 'Company Name', 'woocommerce-for-japan' );
		$description = $this->jp4wc_description_address_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('company-name', $description, $this->prefix);
	}

	/**
	 * Free Shipping Display option.
	 */
	public function jp4wc_options_free_shipping() {
		$title = __( 'Free Shipping Display', 'woocommerce-for-japan' );
		$description = $this->jp4wc_description_address_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('free-shipping', $description, $this->prefix);
	}

	/**
	 * Email customize Customer name option.
	 */
	public function jp4wc_options_custom_email_customer_name() {
		$description = __( 'Please check it if you want to change customer name display.( from first name to full name )', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_checkbox('custom-email-customer-name', $description, $this->prefix);
	}

	/**
	 * Free Shipping Display option.
	 */
	public function jp4wc_options_zip2address() {
		$title = __( 'Automatic zip code entry', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('zip2address', $description, $this->prefix);
	}

	/**
	 * Setting Yahoo App ID option.
	 */
	public function jp4wc_options_yahoo_app_id() {
		$title = __( 'Yahoo! APP ID', 'woocommerce-for-japan' );
		$description = sprintf(__( 'If you use it a bit for testing, you do not need to enter it here. But if you want to use Automatic zip code entry, you must get and input %s here. Please get it from <a href="https://e.developer.yahoo.co.jp/dashboard/" target="_blank">here</a>.', 'woocommerce-for-japan' ), $title);
		$this->jp4wc_plugin->jp4wc_input_text('yahoo-app-id', $description, 60, '', $this->prefix);
	}

	/**
	 * Eliminate the order of addresses option.
	 */
	public function jp4wc_options_no_ja() {
		$description = __( 'Eliminate the order of addresses for Japan.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_checkbox('no-ja', $description, $this->prefix);
	}

	/**
	 * Delivery date designation enable.
	 */
	public function jp4wc_delivery_date_designation(){
		$title = __( 'Delivery date designation', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('delivery-date', $description, $this->prefix);
	}

    /**
     * Delivery date designation required.
     */
    public function jp4wc_delivery_date_required(){
        $description = __( 'Check here if you want to specify the delivery date as a required.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_checkbox('delivery-date-required', $description, $this->prefix);
    }

	/**
	 * Start date for delivery date.
	 */
	public function jp4wc_start_date(){
		$description = __( 'Please enter the number of days until the first day you can receive the delivery date / time. If you enter 0 you can specify delivery from today.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_number('start-date', $description, 2, $this->prefix);
	}

	/**
	 * Term for delivery date.
	 */
	public function jp4wc_reception_period(){
		$description = __( 'Please enter the number of days for which you can accept the delivery reservation. Please enter 1 or more.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_number('reception-period', $description, 7, $this->prefix);
	}

	/**
	 * Unspecified for delivery date.
	 */
	public function jp4wc_unspecified_date(){
		$description = __( 'Please enter the sentence when you do not need to specify the delivery date.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_text('unspecified-date', $description, 60, __( 'No specified date', 'woocommerce-for-japan' ), $this->prefix);
	}

	/**
	 * Unspecified for delivery date.
	 */
	public function jp4wc_date_format(){
		$description = __( 'Please enter the format when you want to save the data by format. Use PHP format type.', 'woocommerce-for-japan' );
		$description .= '<a href="http://php.net/manual/ja/function.date.php" target="_blank">'.' '.__('Check from here.', 'woocommerce-for-japan' ).'</a>';
		$this->jp4wc_plugin->jp4wc_input_text('date-format', $description, 10, 'Y/m/d', $this->prefix);
	}

    /**
     * Delivery date designation enable.
     */
    public function jp4wc_day_of_week(){
        $title = __( 'Display day of week', 'woocommerce-for-japan' );
        $description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
        $this->jp4wc_plugin->jp4wc_input_checkbox('day-of-week', $description, $this->prefix);
    }

	/**
	 * Delivery deadline setting.
	 */
	public function jp4wc_delivery_deadline(){
		$description = __( 'Please enter the time delivery deadline of your store.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_time('delivery-deadline', $description, '15:00', $this->prefix);
	}

	/**
	 * Delivery No delivery weekday setting.
	 */
	public function jp4wc_no_delivery_weekday(){
		$weekdays = array(
			array(
				'label' => __( 'Sunday', 'woocommerce-for-japan' ),
				'name'=> 'no-sun',
				'bold'=> true,
			),
			array(
				'label' => __( 'Moday', 'woocommerce-for-japan' ),
				'name'=> 'no-mon',
			),
			array(
				'label' => __( 'Tuesday', 'woocommerce-for-japan' ),
				'name'=> 'no-tue',
			),
			array(
				'label' => __( 'Wednesday', 'woocommerce-for-japan' ),
				'name'=> 'no-wed',
			),
			array(
				'label' => __( 'Thursday', 'woocommerce-for-japan' ),
				'name'=> 'no-thu',
			),
			array(
				'label' => __( 'Friday', 'woocommerce-for-japan' ),
				'name'=> 'no-fri',
			),
			array(
				'label' => __( 'Saturday', 'woocommerce-for-japan' ),
				'name'=> 'no-sat',
				'bold'=> true,
			),
		);
		foreach($weekdays as $weekday){
			if(isset($weekday['bold']))echo '<strong>';
			echo $weekday['label'].$this->jp4wc_plugin->jp4wc_input_checkbox($weekday['name'], null, $this->prefix).' ';
			if(isset($weekday['bold']))echo '</strong>';
		}
		echo '<br />'.__( 'Please check the days of the week that you do not ship. In case of continuous, we will correspond up to three days.', 'woocommerce-for-japan' );
	}

	/**
	 * Delivery holiday term setting.
	 */
	public function jp4wc_holiday_term(){
		$start_date = array(
			'label' => __( 'Holiday Start Date', 'woocommerce-for-japan' ),
			'id' => 'holiday_start_date',
			'name'=> 'holiday-start-date',
			'title'=> 'Start date:YYYY-MM-DD (Blank:Not specified)',
		);
		$end_date = array(
			'label' => __( 'Holiday End Date', 'woocommerce-for-japan' ),
			'id' => 'holiday_end_date',
			'name'=> 'holiday-end-date',
			'title'=> 'End date:YYYY-MM-DD (Blank:Not specified)',
		);
		$description = __( 'Please enter the date term long holiday of your store.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_date_term($start_date, $end_date, $description, $this->prefix);
	}

	/**
	 * Delivery time zone enable.
	 */
	public function jp4wc_delivery_time_zone(){
		$title = __( 'Delivery time zone', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('delivery-time-zone', $description, $this->prefix);
	}

    /**
     * Delivery time zone required.
     */
    public function jp4wc_delivery_time_zone_required(){
        $description = __( 'Check here if you want to specify the delivery time zone as a required item.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_checkbox('delivery-time-zone-required', $description, $this->prefix);
    }

	/**
	 * Unspecified for delivery time zone.
	 */
	public function jp4wc_unspecified_time(){
		$description = __( 'Please enter the sentence when you do not need to specify the delivery time.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_text('unspecified-time', $description, 60, __( 'No designated time zone', 'woocommerce-for-japan' ), $this->prefix);
	}

	/**
	 * Delivery time zone Management.
	 */
	public function jp4wc_delivery_time_zone_mgn(){
		$time_zone_details = array(
			array ( 'start_time' => '08:00', 'end_time' => '12:00' ), 
			array ( 'start_time' => '12:00', 'end_time' => '14:00' ),
			array ( 'start_time' => '14:00', 'end_time' => '16:00' ),
			array ( 'start_time' => '16:00', 'end_time' => '18:00' ),
			array ( 'start_time' => '18:00', 'end_time' => '20:00' ),
			array ( 'start_time' => '19:00', 'end_time' => '21:00' ),
			array ( 'start_time' => '20:00', 'end_time' => '21:00' ),
		);
		$this->jp4wc_input_time_zone_html($time_zone_details );
	}

	/**
	 * BANK PAYMENT IN JAPAN option.
	 */
	public function jp4wc_options_bankjp() {
		$title = __( 'BANK PAYMENT IN JAPAN', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('bankjp', $description, $this->prefix);
	}
	/**
	 * Postal transfer option.
	 */
	public function jp4wc_options_postofficebank() {
		$title = __( 'Postal transfer', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('postofficebank', $description, $this->prefix);
	}
	/**
	 * Pay at store option.
	 */
	public function jp4wc_options_atstore() {
		$title = __( 'Pay at store', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('atstore', $description, $this->prefix);
	}

	/**
	 * COD option.
	 */
	public function jp4wc_options_cod2() {
		$title = __( 'COD for Subscriptions', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('cod2', $description, $this->prefix);
	}

	/**
	 * PeachPay option.
	 */
	public function jp4wc_options_peachpay() {
		$title = __( 'PeachPay', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('peachpay', $description, $this->prefix);
	}

	/**
	 * PayPal option.
	 */
	public function jp4wc_options_paypal() {
		$title = __( 'PayPal', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_payment_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('jp4wc-paypal', $description, $this->prefix);
	}

	/**
     * Shop Name option.
     */
    public function jp4wc_law_shop_name() {
        $description = __( 'Please enter the shop name.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_text('law-shop-name', $description, 60, get_bloginfo('name'), $this->prefix);
    }

    /**
     * Company Name option.
     */
    public function jp4wc_law_company_name() {
        $description = __( 'Please enter the company name.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_text('law-company-name', $description, 60, '', $this->prefix);
    }

    /**
     * Owner Name option.
     */
    public function jp4wc_law_owner_name() {
        $description = __( 'Please enter the owner name.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_text('law-owner-name', $description, 60, '', $this->prefix);
    }

    /**
     * Owner Name option.
     */
    public function jp4wc_law_location() {
        $description = __( 'Please enter the location.', 'woocommerce-for-japan' );
        if(version_compare( WC_VERSION, '3.6', '>=' )){
            $wc4jp_countries = new WC_Countries;
            $states = $wc4jp_countries->get_states();
        }else{
            global $states;
        }
        $country = get_option('woocommerce_default_country');
        $country_code = substr($country, 0, 2);
        $state_code = substr($country, 3);
        $location = $this->get_option('woocommerce_store_postcode').' '.$states[$country_code][$state_code].get_option('woocommerce_store_city').get_option('woocommerce_store_address').get_option('woocommerce_store_address_2').$country_code;
        $this->jp4wc_plugin->jp4wc_input_text('law-location', $description, 60, $location, $this->prefix);
    }

    /**
     * Contact rules explanation.
     */
    public function jp4wc_law_contact() {
        $description = __( 'Please enter the contact rule.', 'woocommerce-for-japan' );
        $default_value = __( 'Please contact us from the inquiry form.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-contact', $description, $default_value, $this->prefix);
    }

	/**
	 * Telephone explanation.
	 */
	public function jp4wc_law_tel() {
		$description = __( 'Please enter the telephone number.', 'woocommerce-for-japan' );
		$default_value = '';
		$this->jp4wc_plugin->jp4wc_input_textarea('law-tel', $description, $default_value, $this->prefix);
	}

	/**
     * Selling price rules explanation.
     */
    public function jp4wc_law_price() {
        $description = __( 'Please enter the Selling price rules.', 'woocommerce-for-japan' );
        $default_value = __( 'The product price is displayed on each product screen.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-price', $description, $default_value, $this->prefix);
    }

    /**
     * Payment methods rules explanation.
     */
    public function jp4wc_law_payment() {
        $description = __( 'Please enter the Payment methods rules.', 'woocommerce-for-japan' );
        $default_value = __( 'Payment methods are limited to various credit cards.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-payment', $description, $default_value, $this->prefix);
    }

    /**
     * Purchase rules explanation.
     */
    public function jp4wc_law_purchase() {
        $description = __( 'Please enter the purchase rules.', 'woocommerce-for-japan' );
        $default_value = __( 'Put the product you want to purchase in the cart, proceed to purchase, pay with credit card and purchase.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-purchase', $description, $default_value, $this->prefix);
    }

    /**
     * Product delivery time rules explanation.
     */
    public function jp4wc_law_delivery() {
        $description = __( 'Please enter the product delivery time rules.', 'woocommerce-for-japan' );
        $default_value = __( 'The shipping date of the product is listed on each product page.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-delivery', $description, $default_value, $this->prefix);
    }

    /**
     * Cost rules explanation.
     */
    public function jp4wc_law_cost() {
        $description = __( 'Please enter the cost rules.', 'woocommerce-for-japan' );
        $default_value = __( 'A separate shipping fee will be added to the purchase price.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-cost', $description, $default_value, $this->prefix);
    }

    /**
     * Return and cancellation rules explanation.
     */
    public function jp4wc_law_return() {
        $description = __( 'Please enter the return and cancellation rules.', 'woocommerce-for-japan' );
        $default_value = __( 'We will not accept returns or exchanges other than mistakes at the time of delivery on our company side.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-return', $description, $default_value, $this->prefix);
    }

    /**
     * Selling price rules explanation.
     */
    public function jp4wc_law_special() {
        $description = __( 'Please enter the Special rules.', 'woocommerce-for-japan' );
        $this->jp4wc_plugin->jp4wc_input_textarea('law-special', $description,'', $this->prefix);
    }

    /**
     * How to use short code explanation.
     */
    public function jp4wc_law_explanation() {
        echo __( 'It will be displayed if you put [jp4wc_law] shortcode on a page or post.', 'woocommerce-for-japan' );
    }

	/**
	 * A8.net tracking settings option.
	 */
	public function jp4wc_affiliates_a8() {
		$title = __( 'A8.net tracking settings', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('affiliate-a8', $description, $this->prefix);
		echo '<br /><br />';
		$description2 = __( 'Please enter your A8.net Advertising ID.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_text('affiliate-a8-pid', $description2, 30, '', $this->prefix);
        echo '<br /><br />';
		$title3 = __( 'A8.net test mode', 'woocommerce-for-japan' );
		$description3 = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title3 );
		$this->jp4wc_plugin->jp4wc_input_checkbox('affiliate-a8-test', $description3, $this->prefix);
	}

	/**
	 * felmat tracking settings option.
	 */
	public function jp4wc_affiliates_felmat() {
		$title = __( 'felmat tracking settings', 'woocommerce-for-japan' );
		$description = $this->jp4wc_plugin->jp4wc_description_check_pattern( $title );
		$this->jp4wc_plugin->jp4wc_input_checkbox('affiliate-felmat', $description, $this->prefix);
		echo '<br /><br />';
		$description2 = __( 'Please enter your felmat Advertising ID.', 'woocommerce-for-japan' );
		$this->jp4wc_plugin->jp4wc_input_text('affiliate-felmat-pid', $description2, 30, '', $this->prefix);
		echo '<br />';
	}

	/**
	 * create input time zone form.
	 *
     * @param array Default array
	 */
	 public function jp4wc_input_time_zone_html( $default_array ){
		if(get_option( 'wc4jp_time_zone_details')){			
	    	$time_zone_details = get_option( 'wc4jp_time_zone_details',
				array(
					array(
						'start_time'      => $this->get_option( 'start_time' ),
						'end_time'   => $this->get_option( 'end_time' ),
					)
				)
			);
		}else{
			$time_zone_details = $default_array;
		}
?>
	 			    <table class="widefat wc_input_table sortable" id="delivery_time_zone" cellspacing="0">
		    		<thead>
		    			<tr>
		    				<th class="sort" style="width: 17px;">&nbsp;</th>
			            	<th><?php _e( 'Delivery time zone start time', 'woocommerce-for-japan' ); ?></th>
			            	<th></th>
			            	<th><?php _e( 'Delivery time zone end time', 'woocommerce-for-japan' ); ?></th>
		    			</tr>
		    		</thead>
		    		<tfoot>
		    			<tr>
		    				<th colspan="7"><a href="#" class="add button"><?php _e( '+ Add Time Zone', 'woocommerce-for-japan' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Remove selected Time Zone', 'woocommerce-for-japan' ); ?></a></th>
		    			</tr>
		    		</tfoot>
		    		<tbody class="time_zone">
		            	<?php
		            	$i = -1;
		            	if ( $time_zone_details ) {
		            		foreach ( $time_zone_details as $time_zone ) {
		                		$i++;

		                		echo '<tr class="time_zone">
		                			<td class="sort"></td>
		                			<td><input type="time" value="' . esc_attr( $time_zone['start_time'] ) . '" name="start_time[' . $i . ']" /></td>
		                			<td>~</td>
		                			<td><input type="time" value="' . esc_attr( $time_zone['end_time'] ) . '" name="end_time[' . $i . ']" /></td>
			                    </tr>';
		            		}
		            	}
		            	?>
		        	</tbody>
		        </table>
		       	<script type="text/javascript">
					jQuery(function() {
						jQuery('#delivery_time_zone').on( 'click', 'a.add', function(){

							var size = jQuery('#delivery_time_zone').find('tbody .time_zone').length;

							jQuery('<tr class="time_zone">\
		                			<td class="sort"></td>\
		                			<td><input type="time" name="start_time[' + size + ']" /></td>\
		                			<td>~</td>\
		                			<td><input type="time" name="end_time[' + size + ']" /></td>\
			                    </tr>').appendTo('table#delivery_time_zone tbody');

							return false;
						});
					});
				</script>
       <?php
	 }

	/**
	 * create description for address pattern.
	 *
     * @param string Title
	 */
	 public function jp4wc_description_address_pattern( $title ){
			$description = sprintf(__( 'Please check it if you want to use input field for %s', 'woocommerce-for-japan' ), $title);
			return $description;
	 }
	/**
	 * Validate options.
	 * 
	 * @param array $input
	 * @return array
	 */
	public function validate_options( $input ) {
		if ( isset( $_POST['save_wc4jp_options'] ) ) {
			add_settings_error( 'wc4jp_settings_errors', 'wc4jp_settings_saved', __( 'Settings saved.', 'woocommerce-for-japan' ), 'updated' );
		}
		return $input;
	}

	/**
	 * Plugins information display.
	 */
	public function jp4wc_informations_plugins() {
		echo sprintf(__('<a href="%s" target="_blank" title="Paygent Payment">Paygent Payment</a> :  You can handle Credit Card payment and Convini payment, etc<br >', 'woocommerce-for-japan'),'https://wc.artws.info/shop/wordpress-official/paygent-for-woocommerce/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=plugins-information');
		echo sprintf(__('<a href="%s" target="_blank" title="WooCommerce Subscriptions">WooCommerce Subscriptions</a> : You can handle Subscriptions.<br >', 'woocommerce-for-japan'),'https://wc.artws.info/shop/woothemes-official/woocommerce-subscriptions/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=plugins-information');
	}

	/**
	 * Services information display.
	 */
	public function jp4wc_informations_services() {
		echo sprintf(__('<a href="%s" target="_blank" title="Payment Setting Support">Payment Setting Support</a> :  We support Payment Plugins Setting.<br >', 'woocommerce-for-japan'),'https://wc.artws.info/shop/setting-support/payment-support/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=services-information');
		echo sprintf(__('<a href="%s" target="_blank" title="Maintenance Support">Maintenance Support</a> : We support your WordPress and WooCommmerce site, update or somethings.<br >', 'woocommerce-for-japan'),'https://wc.artws.info/shop/maintenance-support/woocommerce-professional-support-subscription/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=services-information');
	}

	/**
	 * Enqueue admin scripts and styles.
	 * 
	 * @param $page
	 */
	public function admin_enqueue_scripts( $page ) {
		wp_register_style( 'custom_jp4wc_admin_css', JP4WC_URL_PATH . 'includes/admin/views/css/admin-jp4wc.css', false, JP4WC_VERSION );
		wp_enqueue_style( 'custom_jp4wc_admin_css' );
		if ( $page === 'woocommerce_page_wc4jp-options' ) {
			wp_enqueue_script( 'jp4wc-admin-script', JP4WC_URL_PATH. 'includes/admin/views/js/admin-settings.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-slider', 'jquery-ui-button' ), JP4WC_VERSION );
			wp_enqueue_script( 'postbox' );
            wp_enqueue_script('jp4wc-tooltip', plugins_url( '/', WC_PLUGIN_FILE ) . 'assets/js/jquery-tiptip/jquery.tipTip.min.js', array('jquery'));
		}
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param mixed $option_name
     * @param string $default
	 * @return string
	 */
	public function get_option( $option_name, $default = '' ) {
		// Array value
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}

		// Single value
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}

    public static function is_dashboard_active() {
        $flag = false;
        if ( 'ja' == get_locale() ) {
            $flag = true;
        }
        return apply_filters( 'jp4wc-admin-is-dashboard-active', $flag );
    }

    /**
     * admin _ Dashboard Widget
     */
    public static function jp4wc_dashboard_widget() {
        if ( self::is_dashboard_active() ) {
            wp_add_dashboard_widget(
                'jp4wc_dashboard_widget',
                __( 'Japanized for WooCommerce Information', 'woocommerce-for-japan' ),
                array( __CLASS__, 'jp4wc_dashboard_widget_body' )
            );
        }
    }

    /**
     * Dashboard Widget body
     */
    public static function jp4wc_dashboard_widget_body() {
        if ( 'ja' == get_locale() ) {
            echo self::jp4wc_get_news_body();
        }
        echo self::jp4wc_get_admin_banner();
    }

    /**
     * Dashboard Widget News body
     */
    public static function jp4wc_get_news_body() {
        if ( 'ja' == get_locale() ) {
            return self::get_news_from_rest_api();
        }
        return '<p>Sorry this information is Japanese only.</p>';
    }

    /**
     * Dashboard Widget admin banner
     */
    public static function jp4wc_get_admin_banner() {
        $banner_html = '<div class="jp4wc-admin-banner">';
        $banner_html .= '<a href="//wc.artws.info" class="jp4wc_logo" target="_blank" class="admin_banner">';
        $banner_html .= '<img src="' . JP4WC_URL_PATH . '/assets/images/woo-logo.png" alt="職人工房" /></a>';
        $banner_html .= '</div>';

        return apply_filters( 'jp4wc_admin_banner_html', $banner_html );
    }

    /**
     * Dashboard html from rest api
     */
    public static function get_news_from_rest_api() {

        $html = '<h4 class="jp4wc-metabox-sub-title">';
        $html .= 'WooCommerce Meetups in Japan';
        $html .= '<a href="https://wc4jp-pro.work/category/wc-meetups/?rel=jp4wc_admin" target="_blank" class="jp4wc-metabox-more-link">記事一覧<span aria-hidden="true" class="dashicons dashicons-external"></span></a>';
        $html .= '</h4>';
        $html .= '<ul id="jp4wc-wc-meetups" class="jp4wc-metabox-post-list"></ul>';

        $html .= '<h4 class="jp4wc-metabox-sub-title">';
        $html .= 'News';
        $html .= '<a href="https://wc4jp-pro.work/category/news/?rel=jp4wc_admin" target="_blank" class="jp4wc-metabox-more-link">記事一覧<span aria-hidden="true" class="dashicons dashicons-external"></span></a>';
        $html .= '</h4>';
        $html .= '<ul id="jp4wc-wc-news" class="jp4wc-metabox-post-list"></ul>';

        $html = apply_filters( 'jp4wc_admin_news_html', $html );

        add_action( 'admin_footer', array( __CLASS__, 'load_rest_api_js' ) );

        return $html;
        ?>
        <?php
    }

    /**
     * Get notifications with WP REST API
     */
    public static function load_rest_api_js() {
        ?>
        <script>
            /*-------------------------------------------*/
            /* Get notifications with WP REST API
            /*-------------------------------------------*/
            ;(function($){
                jQuery(document).ready(function($){

                    // WooCommerce Meetup in Japan
                    $.getJSON( "https://wc4jp-pro.work/wp-json/wp/v2/posts/?categories=47&per_page=3",
                        function(results) {
                            // Loop the contents of the retrieved JSON
                            $.each(results, function(i, item) {
                                // Get date data
                                var date = new Date(item.date_gmt);
                                var formate_date = date.toLocaleDateString();
                                // Output the JSON content element before </ ul>
                                $("ul#jp4wc-wc-meetups").append('<li><span class="date">'+ formate_date +'</span><a href="' + item.link + '?rel=vkadmin" target="_blank">' + item.title.rendered + '</a></li>');
                            });
                        });

                    // News
                    $.getJSON( "https://wc4jp-pro.work/wp-json/wp/v2/posts/?categories=1&per_page=3",
                        function(results) {
                            // Loop the contents of the retrieved JSON
                            $.each(results, function(i, item) {
                                // Get date data
                                var date = new Date(item.date_gmt);
                                var formate_date = date.toLocaleDateString();
                                // Output the JSON content element before </ ul>
                                $("ul#jp4wc-wc-news").append('<li><span class="date">'+ formate_date +'</span><a href="' + item.link + '?rel=vkadmin" target="_blank">' + item.title.rendered + '</a></li>');
                            });
                        });
                });
            })(jQuery);
        </script>
        <?php
    }

}

new JP4WC_Admin_Screen();