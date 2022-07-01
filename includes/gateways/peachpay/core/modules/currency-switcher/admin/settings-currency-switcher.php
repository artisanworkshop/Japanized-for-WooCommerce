<?php
/**
 * File to hold all settings related to Peachpay Currency Switcher
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . '/core/modules/currency-switcher/util/peachpay-currency-arrays.php';

/**
 * New settings for our built in peachpay currency switcher allows admins to view and set settings for our currency switcher itself.
 */
function peachpay_settings_currency_switch() {
	add_settings_section(
		'peachpay_section_currency_switch',
		__( 'Currency switcher', 'woocommerce-for-japan' ),
		'peachpay_feedback_cb',
		'peachpay',
		'peachpay_section_currency',
	);

	add_settings_field(
		'peachpay_field_enabled_currency_switch',
		__( 'Enabled', 'woocommerce-for-japan' ),
		'peachpay_enabled_currency_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

	add_settings_field(
		'peachpay_field_currency_update_time',
		__( 'Frequency of auto-update', 'peachpay' ),
		'peachpay_update_frequency_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

	add_settings_field(
		'peachpay_field_currencies',
		__( 'Currencies', 'woocommerce-for-japan' ),
		'peachpay_currencies_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

}

/**
 * If currency switch is enabled or not.
 */
function peachpay_enabled_currency_cb() {
	?>
	<input type="checkbox"
	name="peachpay_currency_options[enabled]"
	id= "enable_peachpay_currency_switch"
	value = 1
	<?php checked( 1, peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' ), true ); ?>
	>
	<label for="enable_peachpay_currency_switch">
		<?php esc_html_e( 'Enable the currency switcher', 'woocommerce-for-japan' ); ?>
	</label>
	<?php
}

/**
 * Callback for selecting currencies and conversion rates for peachpay
 */
function peachpay_currencies_cb() {
	$base_currency             = get_option( 'woocommerce_currency' );
	$peachpay_currency_options = get_option( 'peachpay_currency_options' );
	if ( $peachpay_currency_options ) {
		$num_currencies    = $peachpay_currency_options['num_currencies'];
		$active_currencies = $peachpay_currency_options ['selected_currencies'];
	} else {
		$num_currencies    = 0;
		$active_currencies = array();
	}

	$active_payment_methods = peachpay_get_active_payment_methods();

	$round_values = array(
		'up',
		'down',
		'nearest',
		'none',
	);

	?>
	<div id='pp-currency-table-div'>
	<table id = "pp-active-currencies">
	<tr class = table-header-footer >
		<td> </td>
		<th class= "pp-tooltip"><?php esc_html_e( ' Currency', 'woocommerce-for-japan' ); ?>
			<span>
				<img  class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
			</span>
			<span class ="pp-tooltipHidden"> <?php esc_html_e( 'Currency converted to from base', 'woocommerce-for-japan' ); ?> </span>
		</th>

		<th class="pp-tooltip"><?php esc_html_e( ' Auto update ', 'woocommerce-for-japan' ); ?>
			<span>
				<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
			</span>
			<span class ="pp-tooltipHidden"> <?php esc_html_e( 'Update the rate automatically?', 'woocommerce-for-japan' ); ?></span>
		</th>

		<th class="pp-tooltip"><?php esc_html_e( ' Conversion rate ', 'woocommerce-for-japan' ); ?>
			<span>
				<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
			</span>
			<span class ="pp-tooltipHidden"> <?php esc_html_e( 'Rate at which the currency will be exchanged', 'woocommerce-for-japan' ); ?></span>
		</th>

		<th class="pp-tooltip"><?php esc_html_e( ' Decimals ', 'woocommerce-for-japan' ); ?>
		<span>
			<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
		</span>
		<span class ="pp-tooltipHidden"><?php esc_html_e( 'Number of decimals the currency will support', 'woocommerce-for-japan' ); ?></span></th>

		<th class="pp-tooltip"><?php esc_html_e( 'Rounding', 'woocommerce-for-japan' ); ?>
		<span>
			<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
		</span>
		<span class ="pp-tooltipHidden"><?php esc_html_e( 'How to round when the there are more decimals than the currency supports', 'woocommerce-for-japan' ); ?> </span></th>

		<th class="pp-tooltip"> <?php esc_html_e( 'Used in these countries', 'woocommerce-for-japan' ); ?>
		<span>
			<img class='pp-tooltip-qm' src=<?php echo esc_url( peachpay_url( '/core/modules/currency-switcher/admin/assets/Property_1help_isFilledTrue.svg' ) ); ?> >
		</span>
		<span class ="pp-tooltipHidden"><?php esc_html_e( 'If WooCommerce geolocation is on, what countries the currency will be restricted to', 'woocommerce-for-japan' ); ?></span></th>
	</tr>

	<tr class="pp-base-currency">
		<td> <?php esc_html_e( 'Base currency', 'woocommerce-for-japan' ); ?></td>
		<td class=pp-tooltip>

		<?php
		$not_supported = array_diff( $active_payment_methods, PEACHPAY_CURRENCIES_METHOD_ARRAY[ $base_currency ] );
		if ( ! empty( $not_supported ) ) {
			echo esc_html( '<span class="pp-tooltipHidden">' );
			esc_html_e( 'Not supported by the following providers ' );
			echo esc_html( implode( ' ', $not_supported ) );
			echo esc_html( '</span>' );
		}
		echo esc_html( $base_currency );
		if ( ! empty( $not_supported ) ) {
			esc_html( '<span>' );
			esc_html( '<img width=10px height=10px src =' );
			echo esc_url( peachpay_url( 'core/modules/currency-switcher/admin/assets/warning-sign.svg' ) );
			esc_html( '> </span>' );
		}
		?>

		</td>
		<td> N/A </td>
		<td>1</td>
		<td><?php echo esc_html( get_option( 'woocommerce_price_num_decimals' ) ); ?></td>
		<td>N/A</td>
		<td>
		<select class="chosen-select" data-placeholder="Allowed in all non-restrcited countries" multiple id="pp_countries_base">
			<?php
			foreach ( ISO_TO_COUNTRY as $iso => $country ) {
				?>
					<option value =
					<?php
					echo esc_html( $iso );
					?>
					<?php
					$selected = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', null );
					$selected = explode( ',', $selected['base']['countries'], 100000 );
					echo ( in_array( $iso, $selected, true ) ? esc_html( 'selected' ) : '' );
					?>
					>
					<?php echo esc_html( $country ); ?>
					</option>
					<?php
			}
			?>
			<script>
				window.addEventListener('load', () => {
					document.querySelector('#submit').addEventListener('click', (event) => {
					let select = document.querySelector('#pp_countries_base');
					let hiddenInput = document.querySelector( '#hiddenCountriesBase');
					let chosen = select.selectedOptions;
					for(let i = 0; i < chosen.length; i++) {
						if(!hiddenInput.value.includes(chosen[i].value)) {
							hiddenInput.value += ',' + chosen[i].value;
						}
					}
				});
			});
			</script>
		</td>
	</tr>
	<?php
	$i = 0;
	foreach ( $active_currencies as $key => $currency ) {
		if ( 'base' === $key ) {
			continue;
		}
		?>
		<tr id = <?php echo esc_html( 'removerow' . $i ); ?> class="currencyRow" >
		<td style="vertical-align:5px;">
			<input type = "button" value="Remove" class = "pp-removeButton">
		</td>

		<td class="pp-currency-name">
			<select
			id="peachpay_new_currency_code"
			name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][name]"
			value =
			<?php
				echo array_key_exists( 'name', $currency ) ? esc_html( $currency['name'] ) : esc_html( $base_currency );
			?>
			class = 'currencyName'
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
			<div class="pp-method-warning pp-tooltip <?php echo empty( $not_supported ) ? esc_html( 'hide' ) : ''; ?>">
			<img style='vertical-align:-2px' width=15px height=15px src =
			<?php
			echo esc_url( peachpay_url( 'core/modules/currency-switcher/admin/assets/warning-sign.svg' ) );
			?>
			>
			<span class="pp-tooltipHidden pp-currency-warning-table"> 
			<?php
			esc_html_e( 'Not supported by the following providers: ' );
			echo esc_html( implode( ' ', $not_supported ) );
			?>
			</div> 
		</span>
		</td>

		<td>
		<input
			id = "peachpay_new_currency_auto_update"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][auto_update]"
			value ="1"
			<?php
			if ( array_key_exists( 'auto_update', $currency ) ) {
				echo( checked( '1', $currency['auto_update'] ) );
			}
			?>
			type="checkbox"
			class="currencyAutoUpdate">
		</td>

		<td>
			<input
			id = "peachpay_new_currency_rate"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][rate]"
			value =
			<?php
			if ( array_key_exists( 'rate', $currency ) ) {
				echo( esc_html( $currency['rate'] ) );
			} else {
				echo( 1 );
			}
			?>
			type="text"
			class="currencyRate"
			<?php
			if ( array_key_exists( 'auto_update', $currency ) ) {
				echo( 'readonly' );
			}
			?>
			>
		</input>
		</td>

		<td>
			<input
			id = "peachpay_new_currency_decimals"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][decimals]"
			value =
			<?php
			if ( array_key_exists( $i, $active_currencies ) ) {
				echo esc_html( $currency['decimals'] );
			} else {
				echo 2;
			}
			?>
			type="number"
			min=0
			max=2
			class="currencyDecimals">
		</td>

		<td>
			<select
			id = "peachpay_convert_rounding"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][round]"
			class = "currencyRound"
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

		<td>

		<select class="chosen-select currencyCountries" data-placeholder="Allowed everywhere" multiple id="pp_countries<?php echo esc_html( $i ); ?>">
			<?php
			foreach ( ISO_TO_COUNTRY as $iso => $country ) {
				?>
				<option value =
				<?php
				echo esc_html( $iso );
				?>
				<?php
				$selected = explode( ',', $currency['countries'], 100000 );
				echo ( in_array( $iso, $selected, true ) ? esc_html( 'selected' ) : '' );
				?>
				>
				<?php echo esc_html( $country ); ?>
				</option>
				<?php
			}

			?>
		</select>


		<input type='text' name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][countries]" id="hiddenCountries<?php echo esc_html( $i ); ?>" hidden>
		<script>
			window.addEventListener('load', () => {
				document.querySelector('#submit').addEventListener('click', (event) => {
				let select = document.querySelector('#pp_countries<?php echo esc_html( $i ); ?>');
				let hiddenInput = document.querySelector( '#hiddenCountries<?php echo esc_html( $i ); ?>');
				let chosen = select.selectedOptions;
				for(let i = 0; i < chosen.length; i++) {
					if(!hiddenInput.value.includes(chosen[i].value)) {
						hiddenInput.value += ',' + chosen[i].value;
					}
				}
			});
			});
		</script>
		</td>
	</tr>
	<tr>
		<?php
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
			name = "peachpay_currency_options[num_currencies]"
			id = "hiddenCurrencyNumber";
			value = <?php echo esc_html( $num_currencies ); ?>
		>
		</input>
	</td>

<tr>
<td>
	<input type="button" value ="Add new currency" id = "updateCurrency">
	<script>


	</script>
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
	echo ( ' </tr> </table>' );
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
	<?php
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_currency_admin_dropdown_scripts' );

/**
 * Enque scripts so our dropdown displays
 *
 * @param string $hook the top level page.
 */
function peachpay_enqueue_currency_admin_dropdown_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'pp_dropdown_code', 'https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.jquery.min.js', array(), '1.8.7', false );
	wp_enqueue_style( 'pp_dropdown_style', 'https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.min.css', array(), '1.8.7', 'all' );
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_currency_admin_scripts' );
/**
 * Enque our script that allows currency addition and removal
 *
 * @param string $hook the top level page.
 */
function peachpay_enqueue_currency_admin_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'pp_currency',
		peachpay_url( 'core/modules/currency-switcher/admin/js/remove-row.js' ),
		array(),
		peachpay_file_version( 'core/modules/currency-switcher/admin/js/remove-row.js' ),
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
}

/**
 * Get active payment methods.
 */
function peachpay_get_active_payment_methods() {
	$active   = array();
	$stripe   = peachpay_get_settings_option( 'peachpay_payment_options', 'enable_stripe' );
	$klarna   = peachpay_get_settings_option( 'peachpay_payment_options', 'klarna_payments' );
	$afterpay = peachpay_get_settings_option( 'peachpay_payment_options', 'afterpay_clearpay_payments' );
	$paypal   = peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' );

	$stripe ? array_push( $active, 'Stripe' ) : '';
	$klarna ? array_push( $active, 'Klarna' ) : '';
	$afterpay ? array_push( $active, 'Afterpay/Clearpay' ) : '';
	$paypal ? array_push( $active, 'PayPal' ) : '';

	return $active;
}
