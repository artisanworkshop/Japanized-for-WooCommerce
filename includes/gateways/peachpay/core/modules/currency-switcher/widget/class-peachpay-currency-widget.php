<?php
/**
 * This class file is so we can offer a widget with PeachPay currency switcher.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay currency widget.
 */
class Peachpay_Currency_Widget extends WP_Widget {

	/**
	 * Create the widget.
	 */
	public function __construct() {
		parent::__construct(
			'peachpay_currency_widget',
			__( 'PeachPay Currency Switcher', 'peachpay-for-woocommerce' ),
			array( 'description' => 'Allow customers to switch currencies outside the PeachPay checkout modal' )
		);

		PeachPay::enqueue_script( 'peachpay_currency_widget', 'public/dist/currency-switcher-widget.bundle.js', array(), true );
	}

	/**
	 * Create the front end.
	 *
	 * @param array $args the arguments passed to the widget.
	 * @param array $instance the instance of the widget we are working with.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo esc_html( $title ) . '<br>';

		$currencies = peachpay_currencies_by_iso( peachpay_get_client_country() );

		$active_currency = ( $_COOKIE && isset( $_COOKIE['pp_active_currency'] ) ) ? sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) ) : peachpay_best_currency( peachpay_get_client_country() );

		if ( $instance['read_only'] ) {
			$string = PEACHPAY_SUPPORTED_CURRENCIES[ $active_currency ];
			?>
			<p class="description">
			<?php
			echo esc_html( $string );
			?>
			</p>
			<?php
			return;
		}

		echo '<select id=pp-currency-widget>';

		foreach ( $currencies as $currency ) {
			echo '<option value="' . esc_html( $currency );
			echo $active_currency === $currency ? '" selected>' : '">';
			echo esc_html( PEACHPAY_SUPPORTED_CURRENCIES[ $currency ] );
			echo '</option>';
		}

		echo '</select>';
	}

	/**
	 * Let user set the currency switcher input title
	 *
	 * @param array $instance what is in the instance.
	 */
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'New title', 'peachpay-for-woocommerce' );
		}
		if ( isset( $instance['read_only'] ) ) {
			$read_only = $instance['read_only'];
		} else {
			$read_only = '0';
		}
		// Widget admin form.
		?>
		<p>
		<label for="<?php esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'peachpay-for-woocommerce' ); ?></label>
		<input class="widefat" id="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<label for="read_only"> <?php esc_html_e( 'Read only:', 'peachpay-for-woocommerce' ); ?></label>
		<input class="widefat" id="<?php echo esc_html( $this->get_field_id( 'read_only' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'read_only' ) ); ?>" type="checkbox" value="1" <?php echo '1' === $read_only ? esc_html( 'checked' ) : ''; ?> />
		</p>
		<?php
	}

	/**
	 * Update the old widget instance to the new Widget instance.
	 *
	 * @param array $new_instance the new instance being passed along to the frontend.
	 * @param array $old_instance the previous instance of the widget being passed along.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = array();
		$instance['title']     = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['read_only'] = ( ! empty( $new_instance['read_only'] ) ) ? wp_strip_all_tags( $new_instance['read_only'] ) : '';
		return $instance;
	}

}

/**
 * Register the widget for addition
 */
function add_pp_currency_widget() {
	register_widget( 'peachpay_currency_widget' );
}
