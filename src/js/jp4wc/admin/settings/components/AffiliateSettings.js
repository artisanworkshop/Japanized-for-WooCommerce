/**
 * Affiliate Settings Component
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	Button,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const AffiliateSettings = ( {
	settings,
	updateSetting,
	saveSettings,
	saving,
} ) => {
	// Panel open state management
	const [ panelStates, setPanelStates ] = useState( () => {
		const saved = localStorage.getItem( 'jp4wc-affiliate-panel-states' );
		return saved
			? JSON.parse( saved )
			: {
					a8net: true,
					felmat: true,
			  };
	} );

	useEffect( () => {
		localStorage.setItem(
			'jp4wc-affiliate-panel-states',
			JSON.stringify( panelStates )
		);
	}, [ panelStates ] );

	const togglePanel = ( panelName ) => {
		setPanelStates( ( prev ) => ( {
			...prev,
			[ panelName ]: ! prev[ panelName ],
		} ) );
	};

	const handleSave = () => {
		saveSettings( settings );
	};

	return (
		<div className="jp4wc-affiliate-settings">
			<PanelBody
				title={ __( 'A8.net Setting', 'woocommerce-for-japan' ) }
				opened={ panelStates.a8net }
				onToggle={ () => togglePanel( 'a8net' ) }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Enable A8.net', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable A8.net affiliate tracking',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'affiliate-a8' ] === '1' }
						onChange={ ( value ) =>
							updateSetting( 'affiliate-a8', value ? '1' : '' )
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Test Mode', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable test mode for A8.net',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'affiliate-a8-test' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'affiliate-a8-test',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'A8.net PID', 'woocommerce-for-japan' ) }
						help={ __(
							'Enter your A8.net PID',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'affiliate-a8-pid' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'affiliate-a8-pid', value )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'felmat Setting', 'woocommerce-for-japan' ) }
				opened={ panelStates.felmat }
				onToggle={ () => togglePanel( 'felmat' ) }
			>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom={ true }
						label={ __( 'Enable felmat', 'woocommerce-for-japan' ) }
						help={ __(
							'Enable felmat affiliate tracking',
							'woocommerce-for-japan'
						) }
						checked={ settings?.[ 'affiliate-felmat' ] === '1' }
						onChange={ ( value ) =>
							updateSetting(
								'affiliate-felmat',
								value ? '1' : ''
							)
						}
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom={ true }
						label={ __( 'felmat PID', 'woocommerce-for-japan' ) }
						help={ __(
							'Enter your felmat PID',
							'woocommerce-for-japan'
						) }
						value={ settings?.[ 'affiliate-felmat-pid' ] || '' }
						onChange={ ( value ) =>
							updateSetting( 'affiliate-felmat-pid', value )
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

export default AffiliateSettings;
