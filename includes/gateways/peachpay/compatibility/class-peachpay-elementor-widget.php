<?php
/**
 * Support for the Elementor Plugin
 * Plugin: https://elementor.com/
 *
 * @package PeachPay
 */

namespace Elementor;

use ElementorPro\Modules\QueryControl\Module;

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Elementor widget that inserts the PeachPay button onto any page.
 */
class PeachPay_Elementor_Widget extends Widget_Base {
	//phpcs:ignore
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		wp_enqueue_style(
			'pp-button-css',
			peachpay_url( 'public/dist/express-checkout-button.bundle.css' ),
			array(),
			peachpay_file_version( 'public/dist/express-checkout-button.bundle.css' )
		);
	}
//phpcs:ignore
	public function get_style_depends() {
		return array( 'enqueue_peachpay_css' );
	}
//phpcs:ignore
	public function get_script_depends() {
		return array( 'enqueue_peachpay_js' );
	}
//phpcs:ignore
	public function get_name() {
		return 'peachpay';
	}
//phpcs:ignore
	public function get_title() {
		return __( 'PeachPay', 'peachpay-for-woocommerce' );
	}
//phpcs:ignore
	public function get_icon() {
		return 'eicon-cart-solid';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the PeachPay widget belongs to.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'woocommerce-elements' );
	}

	/**
	 * Register PeachPay widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 */
	protected function register_controls() {
		$button_options   = get_option( 'peachpay_express_checkout_button' );
		$branding_options = get_option( 'peachpay_express_checkout_branding' );

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'peachpay-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Button Text', 'peachpay-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'Express Checkout',
			)
		);

		$this->add_control(
			'color',
			array(
				'label'   => __( 'Button Color', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $branding_options['button_color'] ?? '#21105d',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'   => __( 'Text Color', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $branding_options['button_text_color'] ?? '#FFFFFF',
			)
		);

		$this->add_control(
			'corners',
			array(
				'label'   => __( 'Rounded Corners', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 50,
				'default' => $button_options['button_border_radius'] ?? 5,
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'lock'          => esc_html__( 'Lock', 'peachpay-for-woocommerce' ),
					'baseball'      => esc_html__( 'Baseball', 'peachpay-for-woocommerce' ),
					'arrow'         => esc_html__( 'Arrow', 'peachpay-for-woocommerce' ),
					'mountain'      => esc_html__( 'Mountain', 'peachpay-for-woocommerce' ),
					'bag'           => esc_html__( 'Bag', 'peachpay-for-woocommerce' ),
					'shopping_cart' => esc_html__( 'Shopping Cart', 'peachpay-for-woocommerce' ),
					'none'          => esc_html__( 'None', 'peachpay-for-woocommerce' ),
				),
				'default' => $button_options['button_icon'] ?? 'none',
			)
		);

		$this->add_control(
			'fade',
			array(
				'label'     => __( 'Fade effect on hover', 'peachpay-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'yes',
				'label_off' => 'no',
				'default'   => 'no',
			)
		);

		$this->add_control(
			'display_cards',
			array(
				'label'     => __( 'Display payment method icons below', 'peachpay-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'yes',
				'label_off' => 'no',
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'default_fonts',
			array(
				'label'     => __( 'Font style match the theme font', 'peachpay-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'yes',
				'label_off' => 'no',
				'default'   => 'no',
			)
		);

		$this->add_control(
			'alignment',
			array(
				'label'   => __( 'Alignment', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'left'   => __( 'Left', 'peachpay-for-woocommerce' ),
					'right'  => __( 'Right', 'peachpay-for-woocommerce' ),
					'full'   => __( 'Full', 'peachpay-for-woocommerce' ),
					'center' => __( 'Center', 'peachpay-for-woocommerce' ),
				),
				'default' => $button_options['product_button_alignment'] ?? 'left',
			)
		);

		$this->add_control(
			'width',
			array(
				'label'   => __( 'Width', 'peachpay-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 400,
				'step'    => 5,
				'default' => $button_options['button_width_product_page'] ?? 220,
			)
		);

		$this->add_control(
			'is_elementor_product_page',
			array(
				'label'       => __( 'Is Single Product Template?', 'peachpay-for-woocommerce' ),
				'description' => __( 'The product will be added to the cart before the checkout window opens. If this option is enabled outside a Single Product Template, the button may not work correctly. The Single Product Template must have an Add To Cart widget.', 'peachpay-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'label_on'    => 'yes',
				'label_off'   => 'no',
				'default'     => 'no',
			)
		);

		$this->add_control(
			'shortcode_enable',
			array(
				'label'       => __( 'Enable Specific Product', 'peachpay-for-woocommerce' ),
				'description' => __( "Choose a product that will be added to the shopper's cart before the checkout window opens.", 'peachpay-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'label_on'    => 'yes',
				'label_off'   => 'no',
				'default'     => 'no',
				'condition'   => array(
					'is_elementor_product_page' => array( '', 'no' ),
				),
			)
		);

		if ( class_exists( 'ElementorPro\Modules\QueryControl\Module' ) ) {
			$this->add_control(
				'shortcode_product_id',
				array(
					'label'        => __( 'Specific Product', 'peachpay-for-woocommerce' ),
					'type'         => Module::QUERY_CONTROL_ID,
					'options'      => array(),
					'label_block'  => true,
					'autocomplete' => array(
						'object' => Module::QUERY_OBJECT_POST,
						'query'  => array(
							'post_type' => array( 'product' ),
						),
					),
					'condition'    => array(
						'shortcode_enable'          => 'yes',
						'is_elementor_product_page' => array( '', 'no' ),
					),
				)
			);
		} else {
			$this->add_control(
				'shortcode_product_id',
				array(
					'label'       => __( 'Specific Product', 'peachpay-for-woocommerce' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'placeholder' => 'Product id',
					'condition'   => array(
						'shortcode_enable'          => 'yes',
						'is_elementor_product_page' => array( '', 'no' ),
					),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Gets the icon content based on the selected icon.
	 *
	 * @param string $icon Selected icon.
	 */
	private function get_icon_content( $icon ) {
		switch ( $icon ) {
			case 'lock':
				return '<g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)" stroke="none">
				<path d="M5610 11999 c-1096 -88 -1924 -626 -2375 -1544 -80 -162 -155 -347
				-155 -380 0 -8 -4 -15 -8 -15 -4 0 -8 -8 -9 -17 -1 -19 -6 -38 -16 -63 -17
				-47 -78 -287 -91 -360 -3 -19 -8 -46 -11 -59 -2 -14 -7 -47 -10 -74 -3 -28 -8
				-53 -10 -56 -8 -13 -24 -189 -28 -313 -1 -27 -5 -48 -10 -48 -4 0 -3 -9 3 -20
				8 -14 8 -20 0 -20 -7 0 -8 -6 -1 -19 6 -10 8 -21 5 -24 -3 -3 -5 -392 -4 -865
				l1 -860 -26 -20 c-14 -11 -69 -30 -123 -43 -159 -36 -266 -74 -364 -128 -109
				-60 -149 -91 -240 -183 -206 -208 -334 -560 -353 -968 -5 -99 -4 -3022 1
				-3125 8 -175 28 -319 46 -343 7 -8 7 -12 -1 -12 -7 0 -9 -5 -4 -13 4 -6 8 -16
				9 -22 1 -5 11 -34 23 -62 11 -29 17 -53 13 -53 -4 0 -2 -4 3 -8 10 -7 37 -61
				37 -72 0 -3 6 -12 14 -21 8 -9 12 -18 9 -21 -3 -3 6 -17 20 -32 13 -14 25 -29
				25 -31 0 -14 84 -117 122 -150 24 -21 34 -32 23 -24 -18 13 -19 13 -6 -3 7
				-10 17 -18 22 -18 5 0 17 -8 27 -17 39 -37 67 -57 121 -86 175 -94 267 -121
				521 -153 111 -13 461 -15 3121 -14 3106 0 3123 0 3314 39 221 45 397 134 531
				270 161 162 238 342 269 626 12 107 15 407 15 1696 0 862 -2 1573 -4 1580 -3
				8 -7 63 -10 122 -4 60 -8 112 -10 116 -3 4 -7 27 -10 51 -17 143 -76 342 -137
				463 -30 57 -107 176 -138 211 -62 69 -159 160 -174 164 -13 3 -37 30 -38 44 0
				7 -4 4 -8 -7 -8 -19 -8 -19 -15 3 -5 14 -13 21 -21 18 -8 -3 -19 2 -25 10 -7
				8 -19 14 -26 14 -8 0 -14 5 -14 10 0 6 -7 10 -15 10 -8 0 -15 5 -15 10 0 6 -9
				10 -20 10 -11 0 -20 5 -20 11 0 5 -4 7 -10 4 -5 -3 -10 -1 -10 5 0 6 -4 8 -10
				5 -5 -3 -10 -2 -10 4 0 5 -8 8 -17 6 -10 -1 -30 4 -45 11 -15 8 -31 13 -35 13
				-4 -1 -16 1 -25 4 -46 18 -54 20 -69 23 -9 2 -27 8 -40 13 -13 5 -27 8 -30 7
				-4 0 -17 8 -30 19 -14 13 -20 14 -15 5 4 -8 -4 -2 -18 13 l-26 28 0 847 c-1
				466 -3 883 -5 927 -7 117 -14 206 -19 240 -3 24 -26 169 -40 255 -21 125 -30
				170 -36 170 -4 0 -6 10 -5 23 1 12 -1 27 -5 32 -5 6 -11 24 -13 40 -9 46 -14
				68 -25 96 -6 15 -8 29 -6 32 3 3 -2 22 -10 42 -9 21 -16 47 -16 59 0 16 -2 18
				-9 7 -7 -10 -11 -1 -16 32 -4 26 -12 47 -17 47 -6 0 -8 9 -5 20 3 11 1 20 -4
				20 -5 0 -9 7 -9 15 0 8 -4 15 -10 15 -5 0 -6 7 -3 17 5 11 3 14 -6 8 -10 -6
				-11 -2 -6 15 5 17 4 21 -5 15 -8 -5 -11 -4 -6 3 7 11 -28 93 -96 227 -22 44
				-55 107 -71 140 -17 33 -34 62 -38 65 -3 3 -16 25 -29 49 -12 24 -26 49 -31
				55 -30 36 -84 113 -105 149 -13 23 -24 38 -24 34 0 -4 -6 -2 -14 5 -8 7 -12
				16 -9 20 2 4 -7 19 -21 33 -15 14 -26 31 -26 37 0 6 -3 8 -7 5 -3 -4 -13 2
				-20 13 -8 11 -19 18 -24 15 -5 -4 -9 2 -9 12 0 11 -20 39 -45 63 -24 24 -42
				47 -39 52 3 4 -4 8 -15 8 -11 0 -22 9 -26 20 -3 11 -13 20 -21 20 -8 0 -14 7
				-14 15 0 8 -7 15 -16 15 -8 0 -12 5 -9 10 3 6 -1 10 -9 10 -9 0 -16 5 -16 11
				0 5 -3 8 -7 6 -5 -3 -14 1 -21 9 -7 8 -9 14 -5 14 4 1 -4 8 -19 16 -16 8 -28
				19 -28 24 0 6 -7 10 -15 10 -8 0 -15 5 -15 10 0 6 -5 10 -10 10 -6 0 -21 11
				-34 25 -13 14 -26 25 -30 25 -7 0 -67 41 -76 51 -3 3 -19 13 -37 22 -17 9 -37
				22 -45 29 -18 17 -59 42 -143 85 -38 19 -73 40 -76 45 -3 5 -9 9 -12 9 -4 0
				-26 8 -49 18 -374 158 -665 229 -1072 261 -150 12 -504 11 -656 -1z m475
				-1214 c103 -10 140 -15 240 -31 90 -15 283 -67 314 -85 17 -9 74 -31 79 -30
				10 4 273 -148 292 -170 3 -3 23 -18 44 -35 22 -16 49 -38 60 -49 12 -11 35
				-31 51 -43 17 -13 23 -21 15 -17 -8 4 -1 -6 16 -22 27 -24 98 -107 157 -184
				10 -13 27 -40 38 -59 11 -19 28 -48 39 -65 19 -30 73 -126 75 -135 1 -3 10
				-23 20 -45 10 -22 18 -43 17 -47 -1 -5 2 -8 6 -8 5 0 9 -6 10 -12 2 -29 29
				-88 37 -81 4 5 5 3 1 -4 -4 -7 7 -50 23 -97 16 -46 27 -87 24 -90 -3 -3 0 -12
				7 -21 7 -9 10 -18 7 -22 -4 -3 -2 -12 4 -20 9 -10 8 -13 -2 -13 -9 -1 -8 -5 5
				-15 11 -8 15 -15 8 -15 -8 0 -9 -7 -1 -27 6 -16 12 -37 14 -48 8 -49 27 -203
				29 -230 0 -16 5 -33 11 -37 7 -5 6 -8 0 -8 -6 0 -11 -9 -11 -20 0 -11 4 -20 9
				-20 5 0 4 -6 -2 -14 -9 -11 -9 -15 1 -20 10 -5 10 -7 1 -12 -19 -8 -16 -24 5
				-24 10 0 13 -3 6 -8 -16 -10 -18 -222 -3 -222 8 0 9 -4 0 -13 -8 -10 -12 -215
				-13 -707 l-3 -694 -34 -20 c-34 -20 -56 -20 -1717 -24 -1216 -3 -1683 -1
				-1688 7 -4 6 -22 11 -40 11 -69 0 -65 -59 -66 905 -1 479 -1 872 0 875 1 3 3
				32 5 65 4 65 10 117 26 210 6 33 12 71 13 85 2 14 6 32 10 41 4 9 8 27 10 41
				1 13 5 28 8 33 3 6 9 28 13 50 8 42 47 159 71 212 8 17 14 34 14 39 0 33 188
				356 247 424 36 42 197 200 239 235 110 93 398 240 507 259 7 1 28 7 47 14 58
				19 273 52 382 58 57 4 104 7 105 8 4 3 156 -3 218 -9z"/>
				</g>';
			case 'baseball':
				return '<path d="M368.5 363.9l28.8-13.9c11.1 22.9 26 43.2 44.1 60.9 34-42.5 54.5-96.3 54.5-154.9 0-58.5-20.4-112.2-54.2-154.6-17.8 17.3-32.6 37.1-43.6 59.5l-28.7-14.1c12.8-26 30-49 50.8-69C375.6 34.7 315 8 248 8 181.1 8 120.5 34.6 75.9 77.7c20.7 19.9 37.9 42.9 50.7 68.8l-28.7 14.1c-11-22.3-25.7-42.1-43.5-59.4C20.4 143.7 0 197.4 0 256c0 58.6 20.4 112.3 54.4 154.7 18.2-17.7 33.2-38 44.3-61l28.8 13.9c-12.9 26.7-30.3 50.3-51.5 70.7 44.5 43.1 105.1 69.7 172 69.7 66.8 0 127.3-26.5 171.9-69.5-21.1-20.4-38.5-43.9-51.4-70.6zm-228.3-32l-30.5-9.8c14.9-46.4 12.7-93.8-.6-134l30.4-10c15 45.6 18 99.9.7 153.8zm216.3-153.4l30.4 10c-13.2 40.1-15.5 87.5-.6 134l-30.5 9.8c-17.3-54-14.3-108.3.7-153.8z"></path>';
			case 'arrow':
				return '<path d="M256 8c137 0 248 111 248 248S393 504 256 504 8 393 8 256 119 8 256 8zm113.9 231L234.4 103.5c-9.4-9.4-24.6-9.4-33.9 0l-17 17c-9.4 9.4-9.4 24.6 0 33.9L285.1 256 183.5 357.6c-9.4 9.4-9.4 24.6 0 33.9l17 17c9.4 9.4 24.6 9.4 33.9 0L369.9 273c9.4-9.4 9.4-24.6 0-34z"></path>';
			case 'mountain':
				return '<path d="M12 3.19995C11.52 3.19995 11.36 3.51995 11.04 3.83995L1.76001 19.04C1.60001 19.2 1.60001 19.52 1.60001 19.68C1.60001 20.48 2.24001 20.8 2.72001 20.8H21.28C21.92 20.8 22.4 20.48 22.4 19.68C22.4 19.36 22.4 19.36 22.24 19.04L13.12 3.83995C12.8 3.51995 12.48 3.19995 12 3.19995ZM12 5.59995L17.28 14.4H16L13.6 12L12 14.4L10.4 12L8.00001 14.4H6.56001L12 5.59995Z" />';
			case 'bag':
				return '<path d="M320 336c0 8.84-7.16 16-16 16h-96c-8.84 0-16-7.16-16-16v-48H0v144c0 25.6 22.4 48 48 48h416c25.6 0 48-22.4 48-48V288H320v48zm144-208h-80V80c0-25.6-22.4-48-48-48H176c-25.6 0-48 22.4-48 48v48H48c-25.6 0-48 22.4-48 48v80h512v-80c0-25.6-22.4-48-48-48zm-144 0H192V96h128v32z"></path>';
			case 'shopping_cart':
				return '<path d="M528.12 301.319l47.273-208C578.806 78.301 567.391 64 551.99 64H159.208l-9.166-44.81C147.758 8.021 137.93 0 126.529 0H24C10.745 0 0 10.745 0 24v16c0 13.255 10.745 24 24 24h69.883l70.248 343.435C147.325 417.1 136 435.222 136 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-15.674-6.447-29.835-16.824-40h209.647C430.447 426.165 424 440.326 424 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-22.172-12.888-41.332-31.579-50.405l5.517-24.276c3.413-15.018-8.002-29.319-23.403-29.319H218.117l-6.545-32h293.145c11.206 0 20.92-7.754 23.403-18.681z"></path>';
			case 'none':
			default:
				return '';
		}
	}

	/**
	 * Gets the icon viewbox based on the selected icon.
	 *
	 * @param string $icon Selected icon.
	 */
	private function get_icon_viewbox( $icon ) {
		switch ( $icon ) {
			case 'lock':
				return '0 0 1190 1280';
			case 'baseball':
				return '0 0 496 512';
			case 'arrow':
				return '0 0 512 512';
			case 'mountain':
				return '0 0 24 24';
			case 'bag':
				return '0 0 512 512';
			case 'shopping_cart':
				return '0 0 576 512';
			case 'none':
			default:
				return '0 0 0 0';
		}
	}

	/**
	 * Render PeachPay widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( 'full' === $settings['alignment'] ) {
			$width = 'width: 100%;';
		} else {
			$width = 'width:' . $settings['width'] . 'px;';
		}
		$color        = '--pp-button-background-color:' . $settings['color'] . ';';
		$style        = $width . $color;
		$button_text  = $settings['text'] ? $settings['text'] : 'Express Checkout';
		$icon_content = $this->get_icon_content( $settings['icon'] );
		$icon_viewbox = $this->get_icon_viewbox( $settings['icon'] );
		$alignment    = 'text-align:' . $settings['alignment'] . ' !important; justify-content: ' . $settings['alignment'] . ' !important;';
		$allowed_tags = array(
			'svg'   => array(
				'class'           => true,
				'aria-hidden'     => true,
				'aria-labelledby' => true,
				'role'            => true,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
			),
			'g'     => array(
				'fill'      => true,
				'transform' => true,
				'stroke'    => true,
			),
			'title' => array( 'title' => true ),
			'path'  => array(
				'd'    => true,
				'fill' => true,
			),
		);

		if ( 'yes' === $settings['shortcode_enable'] && strlen( $settings['shortcode_product_id'] ) > 0 ) {
			$specific_product_id = $settings['shortcode_product_id'];
		} else {
			$specific_product_id = null;
		}

		if ( 'yes' === $settings['is_elementor_product_page'] ) {
			$is_elementor_product_page = $settings['is_elementor_product_page'];
		} else {
			$is_elementor_product_page = null;
		}

		?>
		<div class="button-container pp-button-container " style="<?php echo esc_html( $settings['alignment'] ) !== 'full' ? esc_html( $alignment ) : ''; ?>">
			<button
				<?php
				if ( isset( $specific_product_id ) ) {
					echo 'data-product-id="' . esc_html( $specific_product_id ) . '"';
				}
				if ( isset( $is_elementor_product_page ) ) {
					echo 'data-is-elementor-product-page';
				}
				?>
				class="pp-button pp-product-page pp-button-shortcode <?php echo esc_html( $settings['default_fonts'] ) === 'yes' ? '' : 'pp-button-default-font'; ?> <?php echo esc_html( $settings['fade'] ) === 'yes' ? 'pp-effect-fade' : ''; ?> elementor-pp-button"
				type="button"
				style="display: block; <?php echo esc_html( $style ); ?> ?>; --pp-button-text-color:<?php echo esc_html( $settings['text_color'] ); ?>; border-radius: <?php echo esc_html( $settings['corners'] ); ?>px; font-size: 16px;"
			>
			<svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" viewBox="0 0 128 128" xml:space="preserve" class="pp-spinner hide"><g><circle cx="16" cy="64" r="16" fill-opacity="1"/><circle cx="16" cy="64" r="16" fill-opacity="0.67" transform="rotate(45,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.42" transform="rotate(90,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.2" transform="rotate(135,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(180,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(225,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(270,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(315,64,64)"/><animateTransform attributeName="transform" type="rotate" values="0 64 64;315 64 64;270 64 64;225 64 64;180 64 64;135 64 64;90 64 64;45 64 64" calcMode="discrete" dur="800ms" repeatCount="indefinite"></animateTransform></g></svg>
				<div class="pp-button-content">
					<span><?php echo esc_html( $button_text ); ?></span>
					<svg viewBox="<?php echo esc_html( $icon_viewbox ); ?>" class="pp-btn-symbol <?php echo esc_html( $settings['icon'] ) === 'none' ? 'hide' : ''; ?>"><?php echo wp_kses( $icon_content, $allowed_tags ); ?></svg>
				</div>
			</button>
			<div id="payment-methods-container" class="cc-company-logos <?php echo esc_html( $settings['display_cards'] ) === 'yes' ? '' : 'hide'; ?>">
				<?php echo \PeachPay_Payment::available_gateway_icons(); //PHPCS:ignore  ?>
			</div>
		</div>
		<?php
	}
}

