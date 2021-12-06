<?php
/**
 * Support for the Elementor Plugin
 * Plugin: https://elementor.com/
 *
 * @package PeachPay
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads Peachpay Elementor support.
 */
function peachpay_load_elementor_widget() {
	try {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor\PeachPay_Elementor_Widget() );
	} catch ( \Exception $exception ) {
		// Prevent a fatal error if Elementor class could not be loaded for whatever reason.
	}
}
add_action( 'peachpay_init_compatibility', '\Elementor\peachpay_load_elementor_widget' );

/**
 * Elementor widget that inserts the PeachPay button onto any page.
 */
class PeachPay_Elementor_Widget extends Widget_Base {
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		wp_register_style(
			'enqueue_peachpay_css',
			plugin_dir_url( __DIR__ ) . '../css/peachpay.css'
		);
		wp_register_script(
			'enqueue_peachpay_js',
			plugin_dir_url( __DIR__ ) . '../js/compatibility/peachpay-elementor.js',
			array( 'elementor-frontend' ),
			false,
			true
		);
	}

	public function get_style_depends() {
		return array( 'enqueue_peachpay_css' );
	}

	public function get_script_depends() {
		return array( 'enqueue_peachpay_js' );
	}

	public function get_name() {
		return 'peachpay';
	}

	public function get_title() {
		return __( 'PeachPay', 'woocommerce-for-japan' );
	}

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
		$options = get_option( 'peachpay_button_options' );

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'woocommerce-for-japan' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Button Text', 'woocommerce-for-japan' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( '', 'woocommerce-for-japan' ),
			)
		);

		$this->add_control(
			'color',
			array(
				'label'   => __( 'Color', 'woocommerce-for-japan' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $options['button_color'],
			)
		);

		$this->add_control(
			'shine',
			array(
				'label'        => __( 'Turn off button shine', 'woocommerce-for-japan' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'yes',
				'label_off'    => 'no',
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'position',
			array(
				'label'   => __( 'Position', 'woocommerce-for-japan' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'left'   => __( 'Left', 'woocommerce-for-japan' ),
					'right'  => __( 'Right', 'woocommerce-for-japan' ),
					'full'   => __( 'Full', 'woocommerce-for-japan' ),
					'center' => __( 'Center', 'woocommerce-for-japan' ),
				),
				'default' => $options['product_button_position'],
			)
		);

		$this->add_control(
			'width',
			array(
				'label'   => __( 'Width', 'woocommerce-for-japan' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 400,
				'step'    => 5,
				'default' => $options['button_width_product_page'],
			)
		);

		$this->end_controls_section();
	}

	private function baseURL( $merchant_hostname ) {
		$options   = get_option( 'peachpay_general_options' );
		$test_mode = isset( $options['test_mode'] ) ? $options['test_mode'] : null;
		if ( $test_mode ) {
			switch ( $merchant_hostname ) {
				case 'localhost':
				case '127.0.0.1':
					return 'http://localhost:8080';
				case 'store.local':
				case 'woo.store.local':
					return 'https://dev-connect.peachpay.local'; // Local HTTPS.
				default:
					return 'https://dev-connect.peachpaycheckout.com';
			}
		}
		switch ( $merchant_hostname ) {
			case 'localhost':
			case '127.0.0.1':
				return 'http://localhost:8080';
			case 'woo.peachpay.app':
			case 'theme1.peachpay.app':
			case 'theme2.peachpay.app':
			case 'theme3.peachpay.app':
			case 'theme4.peachpay.app':
			case 'theme5.peachpay.app':
			case 'qa.peachpay.app':
			case 'demo.peachpay.app':
				return 'https://dev-connect.peachpaycheckout.com';
			case 'store.local':
			case 'woo.store.local':
				return 'https://connect.peachpay.local'; // Local HTTPS.
			default:
				return 'https://connect.peachpaycheckout.com';
		}
	}

	private function get_shine_html( $settings ) {
		$shine      = $settings['shine'];
		$shine_html = '';
		if ( $shine === 'no' || $shine === '' ) {
			$shine_html = "<style id = buttonShine >
				@keyframes shine {
					100% {
						left: 200%;
					}
				}
				.pp-button:after,
				.pp-button-mini:after {
					animation: shine 1.8s ease infinite;
					background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
					content: '';
					display: inherit;
					height: 200%;
					left: -200%;
					position: absolute;
					top: 0;
					transform: skewX(-20deg);
					width: 50%;
				}
				</style>";
		}
		return $shine_html;
	}

	/**
	 * Render PeachPay widget output on the frontend.
	 */
	protected function render() {
		$settings    = $this->get_settings_for_display();
		$width       = 'width:' . $settings['width'] . 'px;';
		$color       = '--button-color:' . $settings['color'] . ';';
		$style       = $width . $color;
		$dark        = gethostname() === 'www.blazecandles.co' ? '-dark' : '';
		$hostnameURL = $this->baseURL( gethostname() );
		$spinnerURL  = $hostnameURL . '/img/spinner' . $dark . '.svg';
		$buttonText  = $settings['text'] ? $settings['text'] : 'Express Checkout'
		?>
		<div id="pp-button-container" class="pp-button-container">
			<button id="pp-button" class="pp-button elementor-pp-button" type="button" style=<?php echo $style; ?>>
				<img src= <?php echo $spinnerURL; ?> id="loading-spinner" class="pp-spinner hide">
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo $buttonText; ?> </span>
				</div>
			</button>
		</div>
		<div id = "pp-data" data-text= <?php echo $buttonText; ?> data-color= <?php echo $settings['color']; ?> ></div>
		<?php
		echo $this->get_shine_html( $settings );
	}
}

