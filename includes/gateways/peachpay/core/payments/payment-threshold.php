<?php
/**
 * PeachPay Payment Threshold UI Functions.
 *
 * @deprecated
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/** Returns the currency conversion rate from $from to $to.
 *
 * @param string $from   currency of the starting value in the conversion.
 * @param string $to     target currency in the conversion.
 *
 * @deprecated
 */
function get_rate_between_two_currencies( $from, $to ) {
	$data = wp_remote_get( peachpay_api_url( peachpay_is_test_mode() ) . "api/v1/getCurrency?from={$from}&to={$to}" );

	if ( is_wp_error( $data ) || wp_remote_retrieve_response_code( $data ) === 400 ) {
		return array();
	}

	$data = json_decode( $data['body'], true );

	$rate = $data['conversion'];

	return $rate;
}

/** Gets the lower or upper bound for the transaction limit set by the payment method.
 *
 * @param string $base_currency_acr   3-letter code of the base currency.
 * @param string $payment_method      the payment method of which to return the transaction min/max.
 * @param string $min_or_max          'min' or 'max'.
 *
 * @deprecated
 */
function get_payment_method_transaction_limit( $base_currency_acr, $payment_method, $min_or_max ) {
	// The minimum and maximum values are saved in this currency
	$default_currency = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_default_currency' );
	$conversion_rate  = (float) get_rate_between_two_currencies( $default_currency, $base_currency_acr );
	$pm_val           = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_pm_' . $min_or_max );
	if ( 0 !== strcmp( 'n/a', $pm_val ) ) {
		return strval( round( ( (float) $pm_val ) * $conversion_rate, 2 ) );
	}
	return $pm_val;
}

/** Gets the lower or upper bound for the transaction limit the merchant set.
 *
 * @param string $base_currency_acr   3-letter code of the base currency.
 * @param string $payment_method      the payment method of which to return the transaction min/max.
 * @param string $min_or_max          'min' or 'max'.
 *
 * @deprecated
 */
function get_merchant_transaction_limit( $base_currency_acr, $payment_method, $min_or_max ) {
	// The minimum and maximum values are saved in this currency
	$default_currency = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_default_currency' );
	$conversion_rate  = (float) get_rate_between_two_currencies( $default_currency, $base_currency_acr );
	$merchant_val     = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_merchant_' . $min_or_max );
	if ( 0 !== strcmp( 'n/a', $merchant_val ) ) {
		return strval( round( ( (float) $merchant_val ) * $conversion_rate, 2 ) );
	}
	return $merchant_val;
}

/** Returns the transaction minimum or maximum that is currently in use —— whether that's the payment
 * method's threshold, the value that the merchant set, or nothing (n/a).
 *
 * @param string $base_currency_acr   3-letter code of the base currency.
 * @param string $payment_method      the payment method of which to return the transaction min/max.
 * @param string $min_or_max          'min' or 'max'.
 *
 * @deprecated
 */
function get_currently_used_transaction_limit( $base_currency_acr, $payment_method, $min_or_max ) {
	// The minimum and maximum values are saved in this currency
	$default_currency = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_default_currency' );
	$conversion_rate  = (float) get_rate_between_two_currencies( $default_currency, $base_currency_acr );
	$merchant_val     = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_merchant_' . $min_or_max );
	$pm_val           = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_pm_' . $min_or_max );
	$value_to_display = 0 === strcmp( 'n/a', $merchant_val ) ? $pm_val : $merchant_val;
	if ( 0 !== strcmp( 'n/a', $value_to_display ) ) {
		return strval( round( ( (float) $value_to_display ) * $conversion_rate, 2 ) );
	}
	return $value_to_display;
}

/** Renders the editing fields for each payment method's min/max transaction thresholds.
 *
 * @param string $base_currency_acr      3-letter code of the base currency.
 * @param string $base_currency_symbol   the base currency's symbol.
 * @param string $payment_method         the payment method of which to return the transaction min/max.
 *
 * @deprecated
 */
function peachpay_render_min_max_editor( $base_currency_acr, $base_currency_symbol, $payment_method ) {
	// The minimum and maximum values are saved in this currency
	$default_currency = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_default_currency' );
	$conversion_rate  = get_rate_between_two_currencies( $base_currency_acr, $default_currency );

	$merchant_min                     = get_merchant_transaction_limit( $base_currency_acr, $payment_method, 'min' );
	$merchant_max                     = get_merchant_transaction_limit( $base_currency_acr, $payment_method, 'max' );
	$merchant_limits                  = array(
		'min' => $merchant_min,
		'max' => $merchant_max,
	);
	$merchant_limits_to_display       = array(
		'min' => ( 0 === strcmp( 'n/a', $merchant_min ) ) ? '' : $merchant_min,
		'max' => ( 0 === strcmp( 'n/a', $merchant_max ) ) ? '' : $merchant_max,
	);
	$payment_method_min               = get_payment_method_transaction_limit( $base_currency_acr, $payment_method, 'min' );
	$payment_method_max               = get_payment_method_transaction_limit( $base_currency_acr, $payment_method, 'max' );
	$payment_method_limits_to_display = array(
		'min' => ( 0 === strcmp( 'n/a', $payment_method_min ) ) ? 'Not set' : $payment_method_min,
		'max' => ( 0 === strcmp( 'n/a', $payment_method_max ) ) ? 'Not set' : $payment_method_max,
	);
	?>
	<div class="pp-pm-min-max-col">
		<?php foreach ( array( 'min', 'max' ) as $min_or_max ) { ?>
			<div class="pp-pm-min-max">
				<input type="text" placeholder="<?php echo esc_attr( $payment_method_limits_to_display[ $min_or_max ] ); ?>" value="<?php echo esc_attr( $merchant_limits_to_display[ $min_or_max ] ); ?>" 
														<?php
														if ( 1 !== (float) $conversion_rate ) {
															?>
					data-conversion_rate="<?php echo esc_attr( $conversion_rate ); ?>" <?php } ?>>
				<input type="text" class="pp-pm-min-max-hidden-input" id="peachpay_stripe_<?php echo esc_attr( $payment_method ); ?>_merchant_<?php echo esc_attr( $min_or_max ); ?>" name="peachpay_payment_options[<?php echo esc_attr( $payment_method ); ?>_merchant_<?php echo esc_attr( $min_or_max ); ?>]" value="<?php echo esc_attr( $merchant_limits[ $min_or_max ] ); ?>">
				<div class="pp-pm-min-max-currency">
					<?php echo esc_html( $base_currency_symbol ); ?>
					<span>(<?php echo esc_html( $base_currency_acr ); ?>)</span>
				</div>
				<label><?php echo esc_html( $min_or_max ); ?>imum</label>
			</div>
		<?php } ?>
	</div>
	<input type="text" name="peachpay_payment_options[<?php echo esc_attr( $payment_method ); ?>_default_currency]" value="<?php echo esc_attr( $default_currency ); ?>" class="pp-pm-min-max-hidden-input">
	<input type="text" name="peachpay_payment_options[<?php echo esc_attr( $payment_method ); ?>_pm_min]" value="<?php echo esc_attr( $payment_method_min ); ?>" class="pp-pm-min-max-hidden-input">
	<input type="text" name="peachpay_payment_options[<?php echo esc_attr( $payment_method ); ?>_pm_max]" value="<?php echo esc_attr( $payment_method_max ); ?>" class="pp-pm-min-max-hidden-input">
	<div id="pp-pm-min-max-error-<?php echo esc_attr( $payment_method ); ?>" class="pp-pm-min-max-error hide"></div>
	<?php
}

/** Renders the current min/max transaction limits display under 'Current values' for each payment method.
 *
 * @param string $base_currency_acr      3-letter code of the base currency.
 * @param string $base_currency_symbol   the base currency's symbol.
 * @param string $payment_method         the payment method of which to return the transaction min/max.
 *
 * @deprecated
 */
function peachpay_render_min_max_preview( $base_currency_acr, $base_currency_symbol, $payment_method ) {
	?>
	<div class="pp-pm-min-max-preview">
		<div class="pp-pm-min-preview">
			<span>Min cart total:</span>
			<?php
			$current_min = get_currently_used_transaction_limit( $base_currency_acr, $payment_method, 'min' );
			if ( 0 === strcmp( $current_min, 'n/a' ) ) {
				?>
				<span>Not set</span>
			<?php } else { ?>
				<span><?php echo esc_html( $base_currency_symbol ); ?></span>
				<span><?php echo esc_html( $current_min ); ?></span>
			<?php } ?>
		</div>
		<div class="pp-pm-max-preview">
			<span>Max cart total:</span>
			<?php
			$current_max = get_currently_used_transaction_limit( $base_currency_acr, $payment_method, 'max' );
			if ( 0 === strcmp( $current_max, 'n/a' ) ) {
				?>
				<span>Not set</span>
			<?php } else { ?>
				<span><?php echo esc_html( $base_currency_symbol ); ?></span>
				<span><?php echo esc_html( $current_max ); ?></span>
			<?php } ?>
		</div>
	</div>
	<?php
}

/** Renders the transaction threshold settings section for the given payment method.
 *
 * @param string $payment_method   the payment method of which to return the transaction min/max.
 *
 * @deprecated
 */
function peachpay_render_min_max_section( $payment_method ) {
	$base_currency_acr    = peachpay_get_base_currency();
	$base_currency_symbol = peachpay_currency_symbol();
	if ( ! in_array( $payment_method, array( 'stripe_stripe_payment_request', 'square_apple_pay_payments', 'square_google_pay_payments' ), true ) ) {
		?>
		<div class="pp-pm-min-max-editor collapsed" id="pp-pm-min-max-<?php echo esc_html( $payment_method ); ?>">
			<div class="pp-pm-min-max-label">
				Set min/max transactions
			</div>
			<?php
			peachpay_render_min_max_editor( $base_currency_acr, $base_currency_symbol, $payment_method );
			?>
			<div class="pp-pm-min-max-button-container">
				<input class="pp-pm-min-max-submit" name="submit" type="submit" data-pmkey="<?php echo esc_attr( $payment_method ); ?>">
			</div>
			<div class="pp-pm-min-max-info">
				<div class="pp-pm-min-max-arrow-container">
					<img src="<?php echo esc_url( peachpay_url( '/public/img/chevron-down-solid.svg' ) ); ?>" class="pp-pm-min-max-arrow" />
				</div>
				<div class="pp-pm-min-max-inner-label">
					Change the permitted minimum or maximum transaction amount.
				</div>
				<label>Current values</label>
				<?php
				peachpay_render_min_max_preview( $base_currency_acr, $base_currency_symbol, $payment_method );
				?>
			</div>
		</div>
		<?php
	}
}

/** Returns the transaction min/max cart total for the given payment method.
 *
 * @param string $payment_method   the payment method of which to return the transaction min/max.
 *
 * @deprecated
 */
function peachpay_get_transaction_thresholds( $payment_method ) {
	$options    = array( 'pm_min', 'pm_max', 'merchant_min', 'merchant_max', 'default_currency' );
	$thresholds = array();
	foreach ( $options as $option ) {
		$value                 = peachpay_get_settings_option( 'peachpay_payment_options', $payment_method . '_' . $option );
		$thresholds[ $option ] = $value;
	}
	return $thresholds;
}
