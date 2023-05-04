<?php
/**
 * File to hold all settings related to Peachpay Currency Switcher
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/util/peachpay-currency-arrays.php';

/**
 * New settings for our built in peachpay currency switcher allows admins to view and set settings for our currency switcher itself.
 */
function peachpay_settings_currency_switch() {
	add_settings_section(
		'peachpay_section_currency',
		'',
		'__return_true',
		'peachpay'
	);

	add_settings_field(
		'peachpay_currency_switch_general',
		peachpay_build_section_header( __( 'General', 'peachpay-for-woocommerce' ), 'https://youtu.be/QgZ4impd6nA' ),
		'peachpay_currency_switch_section',
		'peachpay',
		'peachpay_section_currency',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_currency_switch_auto_update',
		peachpay_build_section_header( __( 'Auto update', 'peachpay-for-woocommerce' ) ),
		'peachpay_currency_switch_auto_update_cb',
		'peachpay',
		'peachpay_section_currency',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_currency_switch_widget',
		peachpay_build_section_header( __( 'Switcher widget', 'peachpay-for-woocommerce' ) ),
		'peachpay_currency_switch_widget_cb',
		'peachpay',
		'peachpay_section_currency',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_currency_table',
		peachpay_build_section_header( __( 'Currencies', 'peachpay-for-woocommerce' ) ),
		'peachpay_currency_table_cb',
		'peachpay',
		'peachpay_section_currency',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the currency switcher settings field.
 */
function peachpay_currency_switch_section() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<?php
			// Toggle for currency switcher feature.
			peachpay_admin_input(
				'enable_peachpay_currency_switch',
				'peachpay_currency_options',
				'enabled',
				1,
				__( 'Enable currency switcher', 'peachpay-for-woocommerce' ),
				__( 'When enabled, the PeachPay currency switcher will provide a currency selection on the payment page of the checkout window. The default currency will be set according to the table below, but shoppers can also select a currency when checking out. This currency switcher affects all prices on your store, not just the ones shown in the checkout window.', 'peachpay-for-woocommerce' ),
				array( 'input_type' => 'checkbox' )
			);
			?>
		</div>
		<div>
			<h4><?php esc_html_e( 'Currency based on', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_how_currency_defaults_cb(); ?>
		</div>
		<div>
			<?php
			peachpay_admin_input(
				'enable_peachpay_currency_switch',
				'peachpay_currency_options',
				'add_conversion_fees',
				1,
				__( 'Add currency conversion fees', 'peachpay-for-woocommerce' ),
				__( "Any time a customer pays with a currency different from the store's base currency, Stripe* and PayPal** apply a conversion fee. With this option enabled, you can pass that cost off to the customer.", 'peachpay-for-woocommerce' ),
				array( 'input_type' => 'checkbox' )
			);
			?>
		</div>
		<div>
			<p id='extended-description' style='font-size:11px;'>
				<?php echo esc_html__( '*  For Stripe currency conversions, a 1% fee is assessed.', 'peachpay-for-woocommerce' ); ?>
				<br>
				<?php echo esc_html__( '** For PayPal currency conversions to USD or CAD, a 4% fee is assessed. For other PayPal currency conversions, a 4.5% fee is assessed.', 'peachpay-for-woocommerce' ); ?>
			</p>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the currency switcher auto update settings.
 */
function peachpay_currency_switch_auto_update_cb() {
	?>
	<div class="peachpay-setting-section">
		<div class="currency_update_frequency">
			<h4><?php esc_html_e( 'Frequency of auto-update', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_update_frequency_cb(); ?>
		</div>
		<div class="custom_frequencies">
			<?php
			peachpay_admin_input(
				'enable_peachpay_currency_custom_intervals',
				'peachpay_currency_options',
				'custom_intervals',
				1,
				__( 'Custom intervals', 'peachpay-for-woocommerce' ),
				__( 'Enable custom intervals to be selected for currencies.', 'peachpay-for-woocommerce' ),
				array( 'input_type' => 'checkbox' )
			);
			?>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the currency switcher widget settings.
 */
function peachpay_currency_switch_widget_cb() {
	?>
	<div class="peachpay-settings-section">
		<div>
			PeachPay automatically adds a currency switcher in the Express Checkout window. You can also place a currency switcher widget elsewhere on the store.
		</div>
		<div class="currency-widget-section">
			<a class="button-secondary pp-button-secondary" href="<?php echo esc_html( admin_url( '/widgets.php' ) ); ?>">Place currency switcher</a>
			<a href="https://help.peachpay.app/en/articles/6314847-currency-switcher">View documentation</a>
		</div>
	</div>
	<?php
}

/**
 * Renders the currency table.
 */
function peachpay_currency_table_cb() {
	?>
	<div>
		<div class="currency_table_section">
			<div class="pp-flex-row pp-jc-sb">
				<input class="wp-core-ui button pp-button-secondary" type="button" value="Add new currency" id="pp-updateCurrency">
				<div class="pp-flex-row pp-gap-4 pp-ai-center">
					<input class="wp-core-ui button pp-button-secondary" type="button" value="Remove all currencies" id="pp-currency-reset">
					<?php peachpay_every_currency_cb(); ?>
				</div>
			</div>
			<?php peachpay_currencies_cb(); ?>
			<div class="pp-save-button-section">
				<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * If a merchant wants to support every currency we support let them :)
 */
function peachpay_every_currency_cb() {
	?>
	<div class="pp-popup-mousemove-trigger">
		<input class="wp-core-ui button pp-button-secondary" type="button"
		name="peachpay_currency_options[every_currency]"
		id= "pp-enable-every-currency"
		value="<?php echo esc_html_e( 'Fill table with all currencies', 'peachpay-for-woocommerce' ); ?>"
		>
		<input
		type='hidden'
		name='peachpay_currency_options[flag]'
		id='pp_currency_flag';
		disabled
		>
		<div class="pp-popup pp-popup-above pp-tooltip-popup">
			<?php esc_html_e( 'If clicked, the table below will be populated with the 135+ currencies PeachPay supports. PeachPay will select the best currency for a shopper based on their location.', 'peachpay-for-woocommerce' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Callback for selecting currencies and conversion rates for peachpay
 */
function peachpay_currencies_cb() {
	$base_currency          = get_option( 'woocommerce_currency' );
	$active_currencies      = peachpay_get_active_currencies();
	$active_payment_methods = peachpay_get_active_payment_methods();

	?>
	<div id='pp-currency-table-div' class='pp-load'>
		<table id="pp-active-currencies" >
			<tr class=table-header-footer >
				<th></th>
				<th><?php esc_html_e( ' Currency', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class="pp-popup pp-popup-above pp-tooltip-popup"> <?php esc_html_e( 'When you add a new currency, it will appear below the base currency listed on the first line', 'peachpay-for-woocommerce' ); ?> </span>
					</span>
				</th>

				<th><?php esc_html_e( ' Auto update ', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"> <?php esc_html_e( 'When checked, the rate will update automatically on the schedule set in “Frequency of auto-update” in the settings above', 'peachpay-for-woocommerce' ); ?></span>
					</span>
				</th>

				<th><?php esc_html_e( ' Conversion rate ', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"> <?php esc_html_e( 'This is the rate at which the currency will be converted', 'peachpay-for-woocommerce' ); ?></span>
					</span>
				</th>

				<th><?php esc_html_e( ' Decimals ', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"><?php esc_html_e( 'For currencies with decimals, you can change the number of decimals', 'peachpay-for-woocommerce' ); ?></span>
					</span>
				</th>
				<th><?php esc_html_e( 'Rounding', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"><?php esc_html_e( 'This determines how to round when there are more decimals than the currency supports', 'peachpay-for-woocommerce' ); ?> </span>
					</span>
				</th>

				<th class="custom_interval_selector <?php echo peachpay_get_settings_option( 'peachpay_currency_options', 'custom_intervals' ) ? '' : esc_html( 'hide' ); ?>"><?php esc_html_e( 'Custom interval', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"><?php esc_html_e( 'Set this to "No custom interval" to follow the default interval schedule', 'peachpay-for-woocommerce' ); ?> </span>
					</span>
				</th>

				<th style="border-top-right-radius: 5px; border-bottom-right-radius: 5px;"> <?php esc_html_e( 'Countries restricted to', 'peachpay-for-woocommerce' ); ?>
					<span class="pp-popup-mousemove-trigger">
						<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
						<span class ="pp-popup pp-popup-above pp-tooltip-popup"><?php esc_html_e( 'If a currency has countries associated with it, the currency will only be available in those countries', 'peachpay-for-woocommerce' ); ?></span>
					</span>
				</th>
			</tr>
			<tr class="pp-base-currency">
				<td></td>
				<td>
					<div>
						<?php echo esc_html( isset( PEACHPAY_SUPPORTED_CURRENCIES[ $base_currency ] ) ? PEACHPAY_SUPPORTED_CURRENCIES[ $base_currency ] : __( 'Unsupported Currency', 'peachpay-for-woocommerce' ) ); ?>
						<br>
						<?php echo esc_html( __( ' (Base currency)', 'peachpay-for-woocommerce' ) ); ?>
					</div>
					<?php
					$currency_methods = isset( PEACHPAY_CURRENCIES_METHOD_ARRAY[ $base_currency ] ) ? PEACHPAY_CURRENCIES_METHOD_ARRAY[ $base_currency ] : array();
					$not_supported    = array_diff( $active_payment_methods, $currency_methods );
					?>
					<div class="pp-method-warning pp-popup-mousemove-trigger <?php echo empty( $not_supported ) ? esc_html( 'hide' ) : ''; ?>">
						<img style='vertical-align:-2px' width='15px' height='15px' src=<?php echo esc_url( peachpay_url( 'core/modules/currency-switcher/admin/assets/warning-sign.svg' ) ); ?>>
						<span class="pp-popup pp-popup-above pp-tooltip-popup pp-currency-warning-table">
							<?php
							esc_html_e( 'Not supported by the following providers: ', 'peachpay-for-woocommerce' );
							echo esc_html( implode( ' ', $not_supported ) );
							?>
						</span>
					</div>

				</td>
				<td> N/A </td>
				<td>1</td>
				<td><?php echo esc_html( get_option( 'woocommerce_price_num_decimals' ) ); ?></td>
				<td>N/A</td>
				<td class="custom_interval_selector <?php echo peachpay_get_settings_option( 'peachpay_currency_options', 'custom_intervals' ) ? '' : esc_html( 'hide' ); ?>"> N/A</td>
				<td>
				<select class="chosen-select" data-placeholder="<?php echo esc_html_e( 'Allowed in all non-restricted countries', 'peachpay-for-woocommerce' ); ?>" multiple id="pp_countries_base">
					<?php
					foreach ( ISO_TO_COUNTRY as $iso => $country ) {
						?>
							<option value =
							<?php
							echo esc_html( $iso );
							?>
							<?php
							$selected = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );

							if ( ! empty( $selected ) ) {
								$selected = explode( ',', $selected['base']['countries'], 100000 );
							}
							echo ( in_array( $iso, $selected, true ) ? esc_html( 'selected' ) : '' );
							?>
							>
							<?php echo esc_html( $country ); ?>
							</option>
							<?php
					}
					?>
				</td>
			</tr>
			<?php
			$i = 0;
			foreach ( $active_currencies as $key => $currency ) {
				if ( 'base' === $key ) {
					continue;
				}
				peachpay_currency_table_row( $i, $currency );
				$i++;
			}
			?>
			<tr>
				<td>
					<input
					type = "hidden"
					name = "peachpay_currency_options[selected_currencies][base][name]"
					value = <?php echo esc_html( $base_currency ); ?>
					>
					</input>
				</td>
				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[selected_currencies][base][rate]"
						value =1
					>
					</input>
				</td>
				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[selected_currencies][base][auto_update]"
						value = "1"
					>
					</input>
				</td>
				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[selected_currencies][base][round]"
						value = "disabled"
					>
					</input>
				</td>

				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[selected_currencies][base][decimals]"
						value = <?php echo esc_html( get_option( 'woocommerce_price_num_decimals' ) ); ?>
					>
					</input>
				</td>

				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[selected_currencies][base][countries]"
						id ="hiddenCountriesBase"
						value =""
					>
					</input>
				</td>

				<td>
					<input
						type = "hidden"
						name = "peachpay_currency_options[new_flag]"
						id = "hiddenAddFlag";
						value =1
						disabled
					>
					</input>
				</td>
			</tr>
		</table>
	</div>

	<script>
			jQuery(".chosen-select").chosen({
				no_results_text: "No matching country found"
			})

	</script>
	<?php
}

/**
 * Callback for rendering currency auto update time.
 */
function peachpay_update_frequency_cb() {
	$types = array(
		'15minute' => 'Update every 15 minutes',
		'30minute' => 'Update every 30 minutes',
		'hourly'   => 'Update every hour',
		'6hour'    => 'Update every 6 hours',
		'12hour'   => 'Update every 12 hours',
		'daily'    => 'Update every day',
		'2day'     => 'Update every 2 days',
		'weekly'   => 'Update once a week',
		'biweekly' => 'Update every 2 weeks',
		'monthly'  => 'Update every month',
	);
	?>
	<select
	id = "peachpay_convert_type"
	name = "peachpay_currency_options[update_frequency]"
	class="currencyType">
	<?php
	foreach ( $types as $type => $type_value ) {
		?>
		<option
			value="<?php echo esc_attr( $type ); ?>"
			<?php
				echo ( peachpay_get_settings_option( 'peachpay_currency_options', 'update_frequency' ) === $type ? 'selected' : ' ' );
			?>
			>
			<?php echo esc_html( $type_value ); ?>
		</option>
	<?php } ?>
	</select>
	<p for='peachpay_convert_type' class="description">
	<?php
	esc_html_e( 'Update the currency exchange rate based on the frequency you set.', 'peachpay-for-woocommerce' );
	?>
	</p>
	<?php
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_currency_admin_dropdown_scripts', 1 );

/**
 * Enque scripts so our dropdown displays
 *
 * @param string $hook the top level page.
 */
function peachpay_enqueue_currency_admin_dropdown_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'pp_dropdown_custom_code',
		plugin_dir_url( __FILE__ ) . './js/dropdown.js',
		array(),
		true,
		false
	);
	wp_enqueue_style( 'pp_dropdown_style', 'https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.min.css', array(), '1.8.7', 'all' );
	wp_enqueue_script( 'pp_dropdown_code', 'https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.jquery.min.js', array(), '1.8.7', false );
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_currency_admin_scripts' );
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_currency_switcher_style' );

/**
 * Enqueues admin.css
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_currency_switcher_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'pp_currency',
		plugin_dir_url( __FILE__ ) . 'assets/currency-switcher.css',
		array(),
		true
	);
}

/**
 * Enque our script that allows currency addition and removal
 *
 * @param string $hook the top level page.
 */
function peachpay_enqueue_currency_admin_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook || ( isset( $_GET['tab'] ) && 'currency' !== $_GET['tab'] ) ) {
		return;
	}
	wp_enqueue_script(
		'pp_currency',
		peachpay_url( 'core/modules/currency-switcher/admin/js/remove-row.js' ),
		array(),
		peachpay_file_version( 'core/modules/currency-switcher/admin/js/remove-row.js' ),
		true
	);
	wp_enqueue_script(
		'pp_currency_fee_editor',
		peachpay_url( 'core/modules/currency-switcher/admin/js/fee-editor.js' ),
		array(),
		peachpay_file_version( 'core/modules/currency-switcher/admin/js/fee-editor.js' ),
		true
	);

	wp_localize_script(
		'pp_currency',
		'pp_currency_data',
		array(
			'method_supports'  => PEACHPAY_CURRENCIES_METHOD_ARRAY,
			'active_providers' => peachpay_get_active_payment_methods(),
		)
	);

	wp_localize_script(
		'pp_currency_fee_editor',
		'pp_currency_info',
		peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() )
	);
}

/**
 * Get active payment methods.
 */
function peachpay_get_active_payment_methods() {
	// TODO followup this needs updated to work with new gateways.

	return array();
}

/**
 * Allow merchants to always have paypal in usd even if paypal doesn't support the selected currency.
 */
function peachpay_field_auto_convert_paypal() {
	$paypal_supported_currencies = array(
		'AUD',
		'CAD',
		'CZK',
		'DKK',
		'EUR',
		'HKD',
		'HUF',
		'ILS',
		'JPY',
		'MXN',
		'TWD',
		'NZD',
		'NOK',
		'PHP',
		'PLN',
		'GBP',
		'RUB',
		'SGD',
		'SEK',
		'CHF',
		'TBH',
		'USD',
	);
	$currencies                  = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );
	foreach ( $currencies as $key => $currency ) {
		if ( array_search( $currency['name'], $paypal_supported_currencies, true ) === false ) {
			unset( $currencies[ $key ] );
		}
	}
	if ( 1 > count( $currencies ) ) {
		return;
	}
	?>
	<h4>
		<?php esc_html_e( 'Default currency', 'peachpay-for-woocommerce' ); ?>
	</h4>
	<select
		id="peachpay_paypal_auto_usd"
		name="peachpay_payment_options[paypal_auto_convert]"
		value="none"
	>
		<option value='none'> None </option>
		<?php
		foreach ( $currencies as $currency ) {
			if ( in_array( $currency['name'], $paypal_supported_currencies, true ) ) {
				// update later to name of currency.
				echo '<option value="' . esc_html( $currency['name'] ) . '"' .
				selected( peachpay_get_settings_option( 'peachpay_payment_options', 'paypal_auto_convert' ), $currency['name'], true ) .
				'>' . esc_html( isset( PEACHPAY_SUPPORTED_CURRENCIES[ $currency['name'] ] ) ? PEACHPAY_SUPPORTED_CURRENCIES[ $currency['name'] ] : __( 'Unsupported Currency', 'peachpay-for-woocommerce' ) ) . '</option>';
			}
		}
		?>
	</select>
	<p class='description' for="peachpay_paypal_auto_usd">
		<?php esc_html_e( 'If a chosen currency is not supported by PayPal, switch to this currency instead of hiding PayPal. The currency switcher must be enabled for this option to work and will override country restrictions.', 'peachpay-for-woocommerce' ); ?>
	</p>
	<?php
}

/**
 * If merchnats want to enable custom intervals for currencies allow them to with this feature which will hide or show custom interval section if it's enabled.
 */
function peachpay_currency_custom_interval_cb() {
	?>
	<input type="checkbox"
	name="peachpay_currency_options[custom_intervals]"
	id="enable_peachpay_currency_custom_intervals"
	value="1"
	<?php checked( 1, peachpay_get_settings_option( 'peachpay_currency_options', 'custom_intervals' ), true ); ?>
	>
	<label for="enable_peachpay_currency_custom_intervals">
	<?php esc_html_e( 'Enable custom intervals to be selected for currencies.', 'peachpay-for-woocommerce' ); ?>
	</label>
	<?php
}

/**
 * Produces html to be inserted for a given currency inside the currency switcher table.
 *
 * @param int   $index Index of currency in table.
 * @param array $currency currency info for this row.
 */
function peachpay_currency_table_row( $index, $currency ) {
	$base_currency          = get_option( 'woocommerce_currency' );
	$active_payment_methods = peachpay_get_active_payment_methods();

	// Selector drop-down definitions.
	$types        = array(
		'none'     => __( 'No custom interval', 'peachpay-for-woocommerce' ),
		'15minute' => __( 'Update every 15 minutes', 'peachpay-for-woocommerce' ),
		'30minute' => __( 'Update every 30 minutes', 'peachpay-for-woocommerce' ),
		'hourly'   => __( 'Update every hour', 'peachpay-for-woocommerce' ),
		'6hour'    => __( 'Update every 6 hours', 'peachpay-for-woocommerce' ),
		'12hour'   => __( 'Update every 12 hours', 'peachpay-for-woocommerce' ),
		'daily'    => __( 'Update every day', 'peachpay-for-woocommerce' ),
		'2day'     => __( 'Update every 2 days', 'peachpay-for-woocommerce' ),
		'weekly'   => __( 'Update once a week', 'peachpay-for-woocommerce' ),
		'biweekly' => __( 'Update every 2 weeks', 'peachpay-for-woocommerce' ),
		'monthly'  => __( 'Update every month', 'peachpay-for-woocommerce' ),
	);
	$round_values = array(
		'up',
		'down',
		'nearest',
		'none',
	);

	?>
	<tr id = <?php echo esc_html( 'removerow' . $index ); ?> class="currencyRow" >
		<td>
			<input type = "button" value="&times;" class = "pp-removeButton">
		</td>

		<td>
			<div class="pp-currency-name pp-popup-mousemove-trigger">
			<select
			id="peachpay_new_currency_code"
			name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][name]"
			value =
			<?php
				echo array_key_exists( 'name', $currency ) ? esc_html( $currency['name'] ) : esc_html( $base_currency );
			?>
			class = 'name'
			>
			<?php foreach ( PEACHPAY_SUPPORTED_CURRENCIES as $code => $name ) { ?>
				<option
					value="<?php echo esc_attr( $code ); ?>"
					<?php
					if ( array_key_exists( 'name', $currency ) ) {
						echo ( ( $currency['name'] === $code ) ? 'selected' : ' ' );
					}
					?>
				>
					<?php echo esc_html( $name ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
		$not_supported = array_diff( $active_payment_methods, PEACHPAY_CURRENCIES_METHOD_ARRAY[ $currency['name'] ] );
		?>
			<div class="pp-method-warning pp-popup-mousemove-trigger <?php echo empty( $not_supported ) ? esc_html( 'hide' ) : ''; ?>">
			<img
			style='vertical-align:-2px'
			width='15px'
			height='15px'
			src="<?php echo esc_attr( peachpay_url( 'core/modules/currency-switcher/admin/assets/warning-sign.svg' ) ); ?>"
			>
			<span class="pp-popup pp-popup-above pp-tooltip-popup pp-currency-warning-table">
			<?php
			esc_html_e( 'Not supported by the following providers: ', 'peachpay-for-woocommerce' );
			echo esc_html( implode( ' ', $not_supported ) );
			?>
			</span>
			</div>
			</div>
		</td>

		<td>
		<input
			id = "peachpay_new_currency_auto_update"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][auto_update]"
			value ="1"
			<?php
			if ( array_key_exists( 'auto_update', $currency ) ) {
				echo( checked( '1', $currency['auto_update'] ) );
			}
			?>
			type="checkbox"
			class="auto_update">
		</td>

		<td id='pp-currency-rate-column' class='pp-popup-mousemove-trigger'>
			<span style="visibility: hidden;" class="pp-popup pp-popup-above pp-tooltip-popup"><?php esc_html_e( 'Disable auto update to change value', 'peachpay-for-woocommerce' ); ?> </span>
			<span style="visibility: hidden;" class="pp-popup pp-popup-above pp-tooltip-popup pp-rate-change-warning"><?php esc_html_e( 'Rate will not change until settings are saved', 'peachpay-for-woocommerce' ); ?> </span>
			<input
			id = "peachpay_new_currency_rate"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][rate]"
			value =
			<?php
			if ( array_key_exists( 'rate', $currency ) ) {
				echo( esc_html( $currency['rate'] ) );
			} else {
				echo( 1 );
			}
			?>
			type="text"
			class="rate"
			<?php
			if ( array_key_exists( 'auto_update', $currency ) ) {
				echo( 'readonly' );
			}
			?>
			>
		</input>
			<?php
			peachpay_currency_fee_editor( $currency, $index );
			?>
		</td>

		<td>
			<input
			id = "peachpay_new_currency_decimals"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][decimals]"
			value = <?php echo esc_html( $currency['decimals'] ); ?>
			type="number"
			min=0
			max=2
			class="decimals">
		</td>

		<td>
			<select
			id = "peachpay_convert_rounding"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][round]"
			class = "round"
			>
			<?php
			foreach ( $round_values as $round ) {
				?>
				<option
					value= <?php echo esc_html( $round ); ?>
					<?php array_key_exists( 'round', $currency ) && $currency['round'] === $round ? esc_html( 'selected' ) : ''; ?>
					>
					<?php echo esc_html( $round ); ?>
				</option>
			<?php } ?>
			</select>
		</td>

		<td class="custom_interval_selector <?php echo peachpay_get_settings_option( 'peachpay_currency_options', 'custom_intervals' ) ? '' : esc_html( 'hide' ); ?>">
			<select
			name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][custom_interval]"
			>
				<?php
				$interval = array_key_exists( 'custom_interval', $currency ) ? $currency['custom_interval'] : 'none';
				foreach ( $types as $key => $type ) {
					?>
					<option value="<?php echo esc_html( $key ); ?>" <?php echo $interval === $key ? esc_html( 'selected' ) : ''; ?> > <?php echo esc_html( $type ); ?> </option>
					<?php
				}
				?>
			</select>
		</td>
		<?php
		peachpay_restricted_countries_selector( $index, $currency['countries'], $currency['name'] );
		?>
	</tr>
	<tr>
		<?php
}

/**
 * Generates the 'country restricted to' column inputs for each currency.
 *
 * @param int    $index Index of currency row in table.
 * @param string $countries list of countries selected for currency, separated by ,.
 * @param string $name Name of currency for selector id.
 */
function peachpay_restricted_countries_selector( $index, $countries, $name ) {
	?>
	<td>
		<select class="chosen-select currencyCountries" data-placeholder="Allowed everywhere" multiple id="pp_countries<?php echo esc_html( $name ); ?>">
			<?php
			foreach ( ISO_TO_COUNTRY as $iso => $country ) {
				?>
				<option value =
				<?php
				echo esc_html( $iso );
				?>
				<?php
				$selected = explode( ',', $countries, 100000 );
				echo ( in_array( $iso, $selected, true ) ? esc_html( 'selected' ) : '' );
				?>
				>
				<?php echo esc_html( $country ); ?>
				</option>
				<?php
			}

			?>
		</select>


		<input type='text' name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $index ); ?>][countries]" id="hiddenCountries<?php echo esc_html( $name ); ?>" class='countries' hidden>
	</td>
	<?php
}

/**
 * Renders the + fees button and options for a given currency
 *
 * @param array $currency_info Currency details for the currency rows currency.
 * @param int   $index Index of currency.
 */
function peachpay_currency_fee_editor( $currency_info, $index ) {
	// Data structure example: peachpay_currency_options[selected_currencies]["name of currency"]["additional_fees"].
	// Each additional fees element will be structured as follows: {type => percent/fixed, value => number value, reason => (add later)}.
	?>
	<div class="pp-popup-click-trigger pp-add-fee-trigger">
		<button id=<?php echo esc_html( $index ); ?> class='pp-open-fee-editor-btn' type='button'>
			<?php echo esc_html_e( '+ fees', 'peachpay-for-woocommerce' ); ?>
		</button>
		<div id='[selected_currencies][<?php echo esc_html( $index ); ?>]' class='pp-fees-editor pp-popup pp-popup-above pp-darkened-border'>
			<?php
			peachpay_fee_editor_rows( $currency_info, $index );
			?>
			<button type='button' class='pp-fee-editor-btn pp-inline-flex pp-w-full pp-add-new-fee-btn'>
				<span class='pp-icon-text-font'>&#65291;</span> <span><?php esc_html_e( 'Add new', 'peachpay-for-woocommerce' ); ?></span>
			</button>
			<div class='hidden' id='pp-blank-fee-editor-form'>
				<?php
				peachpay_fee_editor_row( array(), get_woocommerce_currency_symbol( $currency_info['name'] ), $index, -1, 'name_temp' );
				?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Renders rows of fee editor based on currency data
 *
 * @param array $currency_info Input the currency data.
 * @param int   $index currency index in currency array.
 */
function peachpay_fee_editor_rows( $currency_info, $index ) {
	if ( ! isset( $currency_info ) ) {
		return;
	}

	?>
	<div class='pp-currency-fees-col pp-scrollable'>
		<p class='pp-fee-editor-info<?php echo ( isset( $currency_info['fees'] ) && count( $currency_info['fees'] ) > 0 ? ' hidden' : '' ); ?>'>
			<?php echo esc_html_e( 'Fees will be applied to the total when a customer chooses to checkout with this currency.', 'peachpay-for-woocommerce' ); ?>
		</p>
		<?php
		if ( isset( $currency_info['fees'] ) && count( $currency_info['fees'] ) > 0 ) {
			foreach ( $currency_info['fees'] as $fee_index => $fee ) {
				peachpay_fee_editor_row( $fee, get_woocommerce_currency_symbol( $currency_info['name'] ), $index, $fee_index );
			}
		}
		?>
	</div>
	<?php
}

/**
 * Renders single row of fee table.
 *
 * @param array  $fee fee data for row.
 * @param string $curr_symbol Symbol for currency to be displayed with flat rate fee selection.
 * @param int    $curr_index The index of the currency.
 * @param int    $index The index of the fee.
 * @param string $attr defines the attribute the settings option is stored under.
 */
function peachpay_fee_editor_row( $fee, $curr_symbol, $curr_index, $index, $attr = 'name' ) {
	$fee_name_str = 'peachpay_currency_options[selected_currencies][' . $curr_index . '][fees][' . $index . ']';
	$is_percent   = isset( $fee['is_percent'] ) ? true : false;
	$fee_amount   = isset( $fee['value'] ) ? $fee['value'] : '';
	$fee_reason   = isset( $fee['reason'] ) ? $fee['reason'] : '';

	?>
	<div id='pp-fee-editor-form'>
		<section class='pp-fee-editor-row'>
			<input id='pp-fee-value-input' type='number' step='Any' <?php echo esc_html( $attr ); ?>=<?php echo esc_html( $fee_name_str . '[value]' ); ?> placeholder='amount' value='<?php echo esc_html( $fee_amount ); ?>'>
			<div class='pp-fee-type-switch'>
				<input <?php echo esc_attr( $attr . '=' . $fee_name_str . '[is_percent]' ); ?> id='pp-fee-percent-toggle' class='hidden' type='checkbox' <?php echo esc_attr( $is_percent ? 'checked' : '' ); ?> >
				<input <?php echo ( $is_percent ? 'active' : '' ); ?> id='pp-fee-type-button' value='%' type='button'>
				<input <?php echo ( $is_percent ? '' : 'active' ); ?> id='pp-fee-type-button' value='<?php echo esc_attr( $curr_symbol ); ?>' type='button'>
			</div>
			<button type='button' class='pp-fee-remove-btn'>✕</button>
		</section>
		<section class='pp-fee-editor-row'>
			<input id='pp-fee-reason-input' <?php echo esc_html( $attr ); ?>='<?php echo esc_html( $fee_name_str . '[reason]' ); ?>' class='<?php echo esc_html( ( '' === $fee_reason ? 'pp-fee-reason-no-input' : '' ) ); ?>'
				value='<?php echo esc_html( $fee_reason ); ?>' placeholder='&#65291; <?php echo esc_html_e( 'Specify reason (optional)', 'peachpay-for-woocommerce' ); ?>'>
		</section>
	</div>
	<?php
}

/**
 * Callback function for currency settings section allowing merchant to change behavior for how currency defaults
 * in the peachpay checkout
 */
function peachpay_how_currency_defaults_cb() {
	$types = array(
		'geolocate'       => __( 'Customer geolocation', 'peachpay-for-woocommerce' ),
		'billing_country' => __( 'Customer billing country', 'peachpay-for-woocommerce' ),
	);

	if ( ! peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' ) ) {
		peachpay_set_settings_option( 'peachpay_currency_options', 'how_currency_defaults', 'geolocate' );
	}

	?>
	<select
	id = "peachpay_convert_type"
	name = "peachpay_currency_options[how_currency_defaults]"
	class="currencyType">
	<?php
	foreach ( $types as $type => $type_value ) {
		?>
		<option
			value="<?php echo esc_attr( $type ); ?>"
			<?php
				echo ( peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' ) === $type ? 'selected' : ' ' );
			?>
			>
			<?php echo esc_html( $type_value ); ?>
		</option>
	<?php } ?>
	</select>
	<p for="peachpay_convert_type" class="description">
		<?php
			echo esc_html( __( 'PeachPay will show the currency according to ', 'peachpay-for-woocommerce' ) . $types[ peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' ) ] )
		?>
	</p>
	<?php
}
