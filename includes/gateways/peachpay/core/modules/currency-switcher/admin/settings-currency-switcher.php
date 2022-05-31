<?php
/**
 * File to hold all settings related to Peachpay Currency Switcher
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * New settings for our built in peachpay currency switcher allows admins to view and set settings for our currency switcher itself.
 */
function peachpay_settings_currency_switch() {
	add_settings_section(
		'peachpay_section_currency_switch',
		__( 'Currency switcher', 'peachpay' ),
		'peachpay_feedback_cb',
		'peachpay',
		'peachpay_section_currency',
	);

	add_settings_field(
		'peachpay_field_enabled_currency_switch',
		__( 'Enabled', 'peachpay' ),
		'peachpay_enabled_currency_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

	add_settings_field(
		'peachpay_field_num_currencies',
		__( 'Number of currencies', 'peachpay' ),
		'peachpay_num_currencies_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

	add_settings_field(
		'peachpay_field_currencies',
		__( 'Currencies', 'peachpay' ),
		'peachpay_currencies_cb',
		'peachpay',
		'peachpay_section_currency_switch',
	);

}

/**
 * If currency switch is enabled or not
 */
function peachpay_enabled_currency_cb() {
	$enabled = peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' );
	?>
	<input type="checkbox"
	name="peachpay_currency_options[enabled]"
	id= "enable_peachpay_currency_switch"
	value = 1
	<?php checked( 1, peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' ), true ); ?>
	>
	<label for="enable_peachpay_currency_switch">
		<?php esc_html_e( 'Enable the currency switcher' ); ?> 
	</label>
	<?php
}

/**
 * Callback for selecting number of currencies you wish peachpay currency switcher to support
 */
function peachpay_num_currencies_cb() {
	$option         = get_option( 'peachpay_currency_options' );
	$num_currencies = $option ? $option['num_currencies'] : 0;
	?>
	<select
	id = "peachpay_convert_type"
	name = "peachpay_currency_options[num_currencies]" 
	>
	<?php
	for ( $j = 0; $j < 10; $j++ ) {
		?>
	<option value = "<?php echo esc_html( $j ); ?> "
		<?php echo ( ( intval( $num_currencies ) === $j ) ? 'selected' : '' ); ?>  > 
		<?php echo esc_html( $j ); ?>
	</option>
	<?php } ?>
	</select>
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

	$num_set = count( $active_currencies ) - 1;

	$supported_currencies = array(
		'United Arab Emirates Dirham'         => 'AED',
		'Afghan Afghani'                      => 'AFN',
		'Albanian Lek'                        => 'ALL',
		'Armenian Dram'                       => 'AMD',
		'Netherlands Antillean Guilder'       => 'ANG',
		'Angolan Kwanza'                      => 'AOA',
		'Argentine Peso'                      => 'ARS',
		'Aruban Florin'                       => 'AWG',
		'Azerbaijani Manat'                   => 'AZN',
		'Australian dollar'                   => 'AUD',
		'Bosnia-Herzegovina Convertible Mark' => 'BAM',
		'Bajan dollar'                        => 'BBD',
		'Bangladeshi Taka'                    => 'BDT',
		'Bulgarian Lev'                       => 'BGN',
		'Burundian Franc'                     => 'BIF',
		'Bermudan Dollar'                     => 'BMD',
		'Brunei Dollar'                       => 'BND',
		'Bolivian Boliviano'                  => 'BOB',
		'Brazilian Real'                      => 'BRL',
		'Bahamian Dollar'                     => 'BSD',
		'Botswanan Pula'                      => 'BWP',
		'Belarusian Ruble'                    => 'BYN',
		'Belize Dollar'                       => 'BZD',
		'Canadian dollar'                     => 'CAD',
		'Congolese Franc'                     => 'CDF',
		'Chilean Peso'                        => 'CLP',
		'Chinese Renmenbi'                    => 'CNY',
		'Colombian Peso'                      => 'COP',
		'Costa Rican Colón'                   => 'CRC',
		'Cape Verdean Escudo'                 => 'CVE',
		'Djiboutian Franc'                    => 'DJF',
		'Danish Krone'                        => 'DKK',
		'Dominican Peso'                      => 'DOP',
		'Algerian Dinar'                      => 'DZD',
		'Czech koruna'                        => 'CZK',
		'Danish krone'                        => 'DKK',
		'Egyptian Pound'                      => 'EGP',
		'Ethiopian Birr'                      => 'ETB',
		'Euro'                                => 'EUR',
		'Fijian Dollar'                       => 'FJD',
		'Falkland Islands pound'              => 'FKP',
		'Great British Pound'                 => 'GBP',
		'Georgian Lari'                       => 'GEL',
		'Gibraltar pound'                     => 'GIP',
		'Gambian dalasi'                      => 'GMD',
		'Guinean Franc'                       => 'GNF',
		'Quetzal'                             => 'GTQ',
		'Guyanese dollar'                     => 'GYD',
		'Hong Kong dollar'                    => 'HKD',
		'Honduran Lempira'                    => 'HNL',
		'Croatian Kuna'                       => 'HRK',
		'Haitian Gourde'                      => 'HTG',
		'Hungarian forint'                    => 'HUF',
		'Indonesian rupiah'                   => 'IDR',
		'Israeli new shekel'                  => 'ILS',
		'Icelandic Króna'                     => 'ISK',
		'Jamaican Dollar'                     => 'JMD',
		'Japanese yen'                        => 'JPY',
		'Kenyan Shilling'                     => 'KES',
		'Kyrgystani Som'                      => 'KGS',
		'Cambodian riel'                      => 'KHR',
		'Comorian franc'                      => 'KMF',
		'Korean Won'                          => 'KRW',
		'Cayman Islands Dollar'               => 'KYD',
		'Kazakhstani Tenge'                   => 'KZT',
		'Laotian Kip'                         => 'LAK',
		'Lebanese pound'                      => 'LBP',
		'Sri Lankan Rupee'                    => 'LKR',
		'Liberian Dollar'                     => 'LRD',
		'Lesotho loti'                        => 'LSL',
		'Moroccan Dirham'                     => 'MAD',
		'Moldovan Leu'                        => 'MDL',
		'Malagasy Ariary'                     => 'MGA',
		'Macedonian Denar'                    => 'MKD',
		'Myanmar Kyat'                        => 'MMK',
		'Tugrik'                              => 'MNT',
		'Macanese Pataca'                     => 'MOP',
		'Mauritanian Ouguiya'                 => 'MRO',
		'Mauritian Rupee'                     => 'MUR',
		'Maldivian Rufiyaa'                   => 'MVR',
		'Malawian Kwacha'                     => 'MWK',
		'Mexican peso'                        => 'MXN',
		'Malaysian Ringgit'                   => 'MYR',
		'Mozambican metical'                  => 'MZN',
		'Namibian dollar'                     => 'NAD',
		'Nigerian Naira'                      => 'NGN',
		'Nicaraguan Córdoba'                  => 'NIO',
		'Nepalese Rupee'                      => 'NPR',
		'Panamanian Balboa'                   => 'PAB',
		'Sol'                                 => 'PEN',
		'Papua New Guinean Kina'              => 'PGK',
		'Pakistani Rupee'                     => 'PKR',
		'Poland złoty'                        => 'PLN',
		'Paraguayan Guarani'                  => 'PYG',
		'Qatari Rial'                         => 'QAR',
		'Romanian Leu'                        => 'RON',
		'Serbian Dinar'                       => 'RSD',
		'Rwandan franc'                       => 'RWF',
		'Saudi Riyal'                         => 'SAR',
		'Solomon Islands Dollar'              => 'SBD',
		'Seychellois Rupee'                   => 'SCR',
		'Sierra Leonean'                      => 'SLL',
		'Somali Shilling'                     => 'SOS',
		'Surinamese Dollar'                   => 'SRD',
		'Swazi Lilangeni'                     => 'SZL',
		'Thai Baht'                           => 'THB',
		'Tajikistani Somoni'                  => 'TJS',
		'Turkish lira'                        => 'TRY',
		'Trinidad & Tobago Dollar'            => 'TTD',
		'Tanzanian Shilling'                  => 'TZS',
		'Ukrainian hryvnia'                   => 'UAH',
		'Ugandan Shilling'                    => 'UGX',
		'Uruguayan Peso'                      => 'UYU',
		'Uzbekistani Som'                     => 'UZS',
		'Vietnamese dong'                     => 'VND',
		'Central African CFA franc'           => 'XAF',
		'East Caribbean Dollar'               => 'XCD',
		'West African CFA franc'              => 'XOF',
		'CFP Franc'                           => 'XPF',
		'Yemeni Rial'                         => 'YER',
		'South African Rand'                  => 'ZAR',
		'Zambian Kwacha'                      => 'ZMW',
		'New Taiwan dollar'                   => 'TWD',
		'New Zealand dollar'                  => 'NZD',
		'Norwegian krone'                     => 'NOK',
		'Philippine peso'                     => 'PHP',
		'Polish złoty'                        => 'PLN',
		'Russian ruble'                       => 'RUB',
		'Singapore dollar'                    => 'SGD',
		'Swedish krona'                       => 'SEK',
		'Swiss franc'                         => 'CHF',
		'United States dollar'                => 'USD',
	);

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
		'custom'   => 'Value in input box',
	);

	$round_values = array(
		'up',
		'down',
		'nearest',
		'none',
	);

	?>
	<table id = "active_currencies">
	<tr> <td> </td> <th> Currency</th> <th> Conversion rate </th>  <th> Conversion Type </th> <th> # of decimals </th> </tr>
	<tr> <td> </td> <td> Currency converted to from base</td> <td> Rate at which the currency will be exchanged </td>  <td> If auto update how often the rate will be updated </td>
	<td> Number of decimals the currency will support </td> </tr> </th>
	<tr class = table-header-footer> <td> Base currency </td> <td>
	<?php
	echo esc_html( $base_currency );
	?>
	</td> <td> 1 </td>  <td> base currency </td> <td></td></tr>
	<?php
	for ( $i = 0; $i < $num_currencies; $i++ ) {
		?>
		<td></td>
		<td>
			<select
			id="peachpay_new_currency_code"
			name="peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][name]"
			value = 
			<?php
				echo array_key_exists( $i, $active_currencies ) ? esc_html( $active_currencies[ $i ]['name'] ) : esc_html( $base_currency );
			?>
			>
			<?php foreach ( $supported_currencies as $currency => $value ) { ?>
				<option
					value="<?php echo esc_attr( $value ); ?>" 
					<?php
					if ( array_key_exists( $i, $active_currencies ) ) {
						echo ( ( $active_currencies[ $i ]['name'] === $value ) ? 'selected' : ' ' );
					}
					?>
				>
					<?php echo esc_html( $currency ); ?>
				</option>
				<?php
			}
			?>
		</select> 
		</td>

		<td> 
			<input
			id = "peachpay_new_currency_rate"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][rate]"
			value = 
			<?php
			if ( array_key_exists( $i, $active_currencies ) && $active_currencies[ $i ]['rate'] ) {
				echo( esc_html( $active_currencies[ $i ]['rate'] ) );
			} else {
				echo( 1 );
			}
			?>
			type="text" >
		</input>
		</td>	
		<td>
			<select
			id = "peachpay_convert_type"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][type]" >
			<?php
			foreach ( $types as $type => $type_value ) {
				?>
				<option
					value="<?php echo esc_attr( $type ); ?>" 
					<?php
					if ( array_key_exists( $i, $active_currencies ) ) {
						echo ( $active_currencies[ $i ]['type'] === $type ? 'selected' : ' ' );
					}
					?>
					>
					<?php echo esc_html( $type_value ); ?>
				</option>
			<?php } ?>
			</select>
		</td>

		<td> 
			<input
			id = "peachpay_new_currency_decimals"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][decimals]"
			value =
			<?php
			if ( array_key_exists( $i, $active_currencies ) ) {
				echo esc_html( $active_currencies[ $i ]['decimals'] );
			} else {
				echo 2;
			}
			?>
			type="number"
			min=0 
			max=3>
		</td>

		<td>
			<select
			id = "peachpay_convert_rounding"
			name = "peachpay_currency_options[selected_currencies][<?php echo esc_html( $i ); ?>][round]" 
			hidden>
			<?php
			foreach ( $round_values as $round ) {
				?>
				<option
					value= <?php echo 'disabled'; ?>
					>
					<?php echo esc_html( $round ); ?>
				</option>
			<?php } ?>
			</select>
		</td>

	</tr>
	<tr>
		<?php
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
		name = "peachpay_currency_options[selected_currencies][base][type]"
		value = "base"
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
	<?php
	echo ( ' </tr> </table>' );
}
