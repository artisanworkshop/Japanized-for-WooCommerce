/**
 * Law Settings Component
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	TextControl,
	TextareaControl,
	Button,
} from '@wordpress/components';

const LawSettings = ( { settings, updateSetting, saveSettings, saving } ) => {
	const handleSave = () => {
		saveSettings( settings );
	};

	return (
		<div className="jp4wc-law-settings">
			<PanelBody
				title={ __(
					'Specified Commercial Transaction Law',
					'woocommerce-for-japan'
				) }
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Shop Name', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-shop-name' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-shop-name', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Sales company name (company name)',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-company-name' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-company-name', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Owner Name', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-owner-name' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-owner-name', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Manager Name', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-manager-name' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-manager-name', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Location', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-location' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-location', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Contact', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-contact' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-contact', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'Telephone', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-tel' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-tel', value )
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Selling price', 'woocommerce-for-japan' ) }
						value={ settings?.[ 'law-price' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-price', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Payment method',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-payment' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-payment', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Product purchase method',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-purchase' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-purchase', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Product delivery time',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-delivery' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-delivery', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Costs other than product charges',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-cost' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-cost', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Returns / Cancellations',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-return' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-return', value )
						}
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						__nextHasNoMarginBottom={ true }
						label={ __(
							'Special conditions',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'law-special' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'law-special', value )
						}
						rows={ 3 }
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

export default LawSettings;
