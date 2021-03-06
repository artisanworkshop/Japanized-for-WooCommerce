<?php
/**
 * Support for the Elementor Plugin
 * Plugin: https://elementor.com/
 *
 * @package PeachPay
 */

namespace Elementor;

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Loads Peachpay Elementor support.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function peachpay_load_elementor_widget( $widgets_manager ) {
	try {
		$widgets_manager->register( new \Elementor\PeachPay_Elementor_Widget() );
		// phpcs:ignore
	} catch ( \Exception $exception ) {
		// Prevent a fatal error if Elementor class could not be loaded for whatever reason.
	}
}
add_action( 'elementor/widgets/register', '\Elementor\peachpay_load_elementor_widget' );

/**
 * Elementor widget that inserts the PeachPay button onto any page.
 */
class PeachPay_Elementor_Widget extends Widget_Base {
	//phpcs:ignore
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		wp_register_style(
			'enqueue_peachpay_css',
			peachpay_url( 'public/css/peachpay.css' ),
			array(),
			peachpay_file_version( 'public/css/peachpay.css' ),
		);
		wp_register_script(
			'enqueue_peachpay_js',
			peachpay_url( 'public/js/compatibility/peachpay-elementor.js' ),
			array( 'elementor-frontend' ),
			peachpay_file_version( 'public/js/compatibility/peachpay-elementor.js' ),
			true
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
		return __( 'PeachPay', 'woocommerce-for-japan' );
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
				'placeholder' => '',
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
			'fade',
			array(
				'label'        => __( 'Turn on button fade on hover', 'woocommerce-for-japan' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'yes',
				'label_off'    => 'no',
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'alignment',
			array(
				'label'   => __( 'Alignment', 'woocommerce-for-japan' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'left'   => __( 'Left', 'woocommerce-for-japan' ),
					'right'  => __( 'Right', 'woocommerce-for-japan' ),
					'full'   => __( 'Full', 'woocommerce-for-japan' ),
					'center' => __( 'Center', 'woocommerce-for-japan' ),
				),
				'default' => $options['product_button_alignment'],
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

	/**
	 * Gets the base URL of the peachpay api Server.
	 *
	 * @param string $merchant_hostname Hostname.
	 */
	private function baseURL( $merchant_hostname ) {
		$test_mode = peachpay_is_test_mode();
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

	/**
	 * Render PeachPay widget output on the frontend.
	 */
	protected function render() {
		$settings     = $this->get_settings_for_display();
		$width        = 'width:' . $settings['width'] . 'px;';
		$color        = '--pp-button-background-color:' . $settings['color'] . ';';
		$style        = $width . $color;
		$dark         = gethostname() === 'www.blazecandles.co' ? '-dark' : '';
		$hostname_url = $this->baseURL( gethostname() );
		$spinner_url  = $hostname_url . '/img/spinner' . $dark . '.svg';
		$button_text  = $settings['text'] ? $settings['text'] : 'Express Checkout'
		?>
		<div id="pp-button-container" class="pp-button-container">
			<button id="pp-button" class="pp-button elementor-pp-button" type="button" style=<?php echo esc_html( $style ); ?>>
				<img src= <?php echo esc_html( $spinner_url ); ?> id="loading-spinner" class="pp-spinner hide">
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo esc_html( $button_text ); ?> </span>
				</div>
			</button>
		</div>
		<div id = "pp-data" data-text= <?php echo esc_html( $button_text ); ?> data-color= <?php echo esc_html( $settings['color'] ); ?> ></div>
		<?php
	}
}

