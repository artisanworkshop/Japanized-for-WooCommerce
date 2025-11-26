/**
 * Payment Settings Component
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	Button,
	SelectControl,
} from '@wordpress/components';

const PaymentSettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	const handleSave = () => {
		saveSettings( settings );
	};

	return (
		<div className="jp4wc-payment-settings">
			<PanelBody
				title={ __( 'Payment Method', 'woocommerce-for-japan' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'BANK PAYMENT IN JAPAN',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Enable bank payment method',
							'woocommerce-for-japan'
						) }
						checked={ settings?.bankjp === '1' }
						onChange={ ( value ) =>
							updateSetting( 'bankjp', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Postal transfer',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Enable postal transfer payment method',
							'woocommerce-for-japan'
						) }
						checked={ settings?.postofficebank === '1' }
						onChange={ ( value ) =>
							updateSetting( 'postofficebank', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Pay at store', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable pay at store payment method',
							'woocommerce-for-japan'
						) }
						checked={ settings?.atstore === '1' }
						onChange={ ( value ) =>
							updateSetting( 'atstore', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'COD for Subscriptions',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Enable COD for subscription orders',
							'woocommerce-for-japan'
						) }
						checked={ settings?.cod2 === '1' }
						onChange={ ( value ) =>
							updateSetting( 'cod2', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'PayPal', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable PayPal payment method',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'jp4wc-paypal' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'jp4wc-paypal', value ? '1' : '' )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __(
					'Extra charge for COD method',
					'woocommerce-for-japan'
				) }
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __( 'Fee name', 'woocommerce-for-japan' ) }
						help={ __(
							'Name of the COD fee',
							'woocommerce-for-japan'
						) }
						value={ settings?.extra_charge_name || '' }
						onChange={ ( value ) =>
							updateSetting( 'extra_charge_name', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __(
							'Extra charge amount',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Amount to charge for COD',
							'woocommerce-for-japan'
						) }
						type="number"
						value={ settings?.extra_charge_amount || '0' }
						onChange={ ( value ) =>
							updateSetting( 'extra_charge_amount', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __(
							'Maximum cart value to which adding fee',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Maximum cart value for COD fee application',
							'woocommerce-for-japan'
						) }
						type="number"
						value={ settings?.extra_charge_max_cart_value || '0' }
						onChange={ ( value ) =>
							updateSetting(
								'extra_charge_max_cart_value',
								value
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __(
							'Includes taxes',
							'woocommerce-for-japan'
						) }
						value={ settings?.extra_charge_calc_taxes || 'no' }
						options={ [
							{
								label: __( 'No', 'woocommerce-for-japan' ),
								value: 'no',
							},
							{
								label: __( 'Yes', 'woocommerce-for-japan' ),
								value: 'yes',
							},
						] }
						onChange={ ( value ) =>
							updateSetting( 'extra_charge_calc_taxes', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __( 'Tax Class', 'woocommerce-for-japan' ) }
						value={ settings?.extra_charge_tax_class || '' }
						options={ [
							{
								label: __(
									'Standard',
									'woocommerce-for-japan'
								),
								value: '',
							},
							{
								label: __(
									'Reduced rate',
									'woocommerce-for-japan'
								),
								value: 'reduced-rate',
							},
							{
								label: __(
									'Zero rate',
									'woocommerce-for-japan'
								),
								value: 'zero-rate',
							},
						] }
						onChange={ ( value ) =>
							updateSetting( 'extra_charge_tax_class', value )
						}
					/>
				</PanelRow>
			</PanelBody>

			<div className="jp4wc-settings-footer">
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving...', 'woocommerce-for-japan' )
						: __( 'Save Settings', 'woocommerce-for-japan' ) }
				</Button>
			</div>
		</div>
	);
};

export default PaymentSettings;
