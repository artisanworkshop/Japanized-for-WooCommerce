/**
 * General Settings Component
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';

const GeneralSettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	const handleSave = () => {
		saveSettings( settings );
	};

	return (
		<div className="jp4wc-general-settings">
			<Notice status="info" isDismissible={ false }>
				{ __(
					'Regarding this setting, etc., we do not yet support block shopping carts or purchase procedures. Please use the shortcode to use this feature.',
					'woocommerce-for-japan'
				) }
			</Notice>

			<PanelBody
				title={ __(
					'Address Display Setting',
					'woocommerce-for-japan'
				) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Name Yomigana', 'woocommerce-for-japan' ) }
						help={ __(
							'Check it if you want to add Yomigana(furigana) input at cart, checkout and my account page.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.yomigana === '1' }
						onChange={ ( value ) =>
							updateSetting( 'yomigana', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Required Name Yomigana',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check if you want to require yomigana.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'yomigana-required' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'yomigana-required',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Honorific suffix(Sama)',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you want to add honorific suffix(Sama) at cart, checkout and my account page.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'honorific-suffix' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'honorific-suffix',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Company Name', 'woocommerce-for-japan' ) }
						help={ __(
							'Check it if you want to add Company name input at cart, checkout and my account page.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'company-name' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'company-name', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Automatic zip code entry',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you want to use automatic zip code entry.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.zip2address === '1' }
						onChange={ ( value ) =>
							updateSetting( 'zip2address', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom={true}
						label={ __( 'Yahoo APP ID', 'woocommerce-for-japan' ) }
						help={ __(
							'If you use it a bit for testing, you do not need to enter it here. But if you want to use Automatic zip code entry, you must get and input Yahoo APP ID here.',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'yahoo-app-id' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'yahoo-app-id', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'No Japanese Address',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you eliminate the order of addresses.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'no-ja' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'no-ja', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Free Shipping Display',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Check it if you want to display free shipping message.',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'free-shipping' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'free-shipping', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Custamize Email template at customer name',
							'woocommerce-for-japan'
						) }
						help={ __(
							'Please check it if you want to change customer name display.( from first name to full name )',
							'woocommerce-for-japan'
						) }
						checked={
							settings?.[ 'custom-email-customer-name' ] === '1'
						}
						onChange={ ( value ) =>
							updateSetting(
								'custom-email-customer-name',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Virtual order Setting', 'woocommerce-for-japan' ) }
				initialOpen={ false }
			>
				<div style={ { marginBottom: '20px', marginTop: '10px' } }>
					{ __(
						'Check to hide address input field in virtual orders',
						'woocommerce-for-japan'
					) }
				</div>
				<PanelRow>
					<div style={ { width: '100%' } }>
						<div
							style={ {
								display: 'flex',
								flexWrap: 'wrap',
								gap: '20px',
							} }
						>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'Postcode / ZIP',
									'woocommerce-for-japan'
								) }
								checked={ settings?.billing_postcode === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_postcode',
										value ? '1' : ''
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'Prefecture',
									'woocommerce-for-japan'
								) }
								checked={ settings?.billing_state === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_state',
										value ? '1' : ''
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'Town / City',
									'woocommerce-for-japan'
								) }
								checked={ settings?.billing_city === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_city',
										value ? '1' : ''
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'Street address',
									'woocommerce-for-japan'
								) }
								checked={ settings?.billing_address_1 === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_address_1',
										value ? '1' : ''
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __(
									'Apartment, suite, unit, etc. (optional)',
									'woocommerce-for-japan'
								) }
								checked={ settings?.billing_address_2 === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_address_2',
										value ? '1' : ''
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom={ true }
								label={ __( 'Phone', 'woocommerce-for-japan' ) }
								checked={ settings?.billing_phone === '1' }
								onChange={ ( value ) =>
									updateSetting(
										'billing_phone',
										value ? '1' : ''
									)
								}
							/>
						</div>
					</div>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Usage tracking', 'woocommerce-for-japan' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Tracking', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable usage tracking',
							'woocommerce-for-japan'
						) }
						checked={ settings?.tracking === '1' }
						onChange={ ( value ) =>
							updateSetting( 'tracking', value ? '1' : '' )
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

export default GeneralSettings;
