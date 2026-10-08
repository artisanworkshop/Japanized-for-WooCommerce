/**
 * Main Settings Component
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';

import GeneralSettings from './GeneralSettings';
import ShipmentSettings from './ShipmentSettings';
import PaymentSettings from './PaymentSettings';
import LawSettings from './LawSettings';
import AffiliateSettings from './AffiliateSettings';

import './Settings.scss';

/**
 * Fill in the default "Morning" delivery time zone label when the option is
 * on and the label is empty.
 *
 * The default is saved from here rather than left to the server: the plugin's
 * own translations are used here, while a WordPress.org language pack that
 * predates the string takes precedence over them in PHP. Every tab saves the
 * whole settings object, so this runs for all of them.
 *
 * @param {Object} values Settings about to be saved.
 * @return {Object} Settings to save.
 */
const withMorningLabel = ( values ) => {
	const label = values?.[ 'delivery-time-morning-label' ];
	if (
		values?.[ 'delivery-time-morning' ] !== '1' ||
		( typeof label === 'string' && label.trim() )
	) {
		return values;
	}
	return {
		...values,
		'delivery-time-morning-label': __( 'Morning', 'woocommerce-for-japan' ),
	};
};

const Settings = () => {
	const [ settings, setSettings ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ message, setMessage ] = useState( null );
	const [ error, setError ] = useState( null );

	// Load settings from API
	useEffect( () => {
		loadSettings();
	}, [] );


	const loadSettings = async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: '/jp4wc/v1/settings',
				method: 'GET',
			} );
			setSettings( response );
		} catch ( err ) {
			setError(
				err.message ||
					__( 'Failed to load settings', 'woocommerce-for-japan' )
			);
		} finally {
			setLoading( false );
		}
	};

	const saveSettings = async ( updatedSettings ) => {
		setSaving( true );
		setMessage( null );
		setError( null );

		try {
			const response = await apiFetch( {
				path: '/jp4wc/v1/settings',
				method: 'POST',
				data: withMorningLabel( updatedSettings ),
			} );

			setSettings( response );
			setMessage(
				__( 'Settings saved successfully.', 'woocommerce-for-japan' )
			);

			// Clear message after 3 seconds
			setTimeout( () => setMessage( null ), 3000 );
		} catch ( err ) {
			setError(
				err.message ||
					__( 'Failed to save settings', 'woocommerce-for-japan' )
			);
		} finally {
			setSaving( false );
		}
	};

	const updateSetting = ( key, value ) => {
		// Functional update so that several calls in one handler all apply.
		setSettings( ( prevSettings ) => ( {
			...prevSettings,
			[ key ]: value,
		} ) );
	};

	if ( loading ) {
		return (
			<div className="jp4wc-settings-loading">
				<Spinner />
				<p>{ __( 'Loading settings...', 'woocommerce-for-japan' ) }</p>
			</div>
		);
	}

	const tabs = applyFilters( 'jp4wc.settings.tabs', [
		{
			name: 'general',
			title: __( 'General Settings', 'woocommerce-for-japan' ),
			className: 'jp4wc-tab-general',
		},
		{
			name: 'shipment',
			title: __( 'Shipment Settings', 'woocommerce-for-japan' ),
			className: 'jp4wc-tab-shipment',
		},
		{
			name: 'payment',
			title: __( 'Payment Settings', 'woocommerce-for-japan' ),
			className: 'jp4wc-tab-payment',
		},
		{
			name: 'law',
			title: __( 'Commercial Law', 'woocommerce-for-japan' ),
			className: 'jp4wc-tab-law',
		},
		{
			name: 'affiliate',
			title: __( 'Affiliate', 'woocommerce-for-japan' ),
			className: 'jp4wc-tab-affiliate',
		},
	] );

	return (
		<div className="jp4wc-settings-container">
			<div className="jp4wc-settings-header">
				<h1>
					{ __(
						'Japanized for WooCommerce Settings',
						'woocommerce-for-japan'
					) }
				</h1>
			</div>

			{ message && (
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setMessage( null ) }
				>
					{ message }
				</Notice>
			) }

			{ error && (
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setError( null ) }
				>
					{ error }
				</Notice>
			) }

			<TabPanel
				className="jp4wc-settings-tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => (
					<div className="jp4wc-settings-tab-content">
						{ tab.name === 'general' && (
							<GeneralSettings
								settings={ settings }
								updateSetting={ updateSetting }
								saveSettings={ saveSettings }
								saving={ saving }
							/>
						) }
						{ tab.name === 'shipment' && (
							<ShipmentSettings
								settings={ settings }
								updateSetting={ updateSetting }
								saveSettings={ saveSettings }
								saving={ saving }
							/>
						) }
						{ tab.name === 'payment' && (
							<PaymentSettings
								settings={ settings }
								updateSetting={ updateSetting }
								saveSettings={ saveSettings }
								saving={ saving }
							/>
						) }
						{ tab.name === 'law' && (
							<LawSettings
								settings={ settings }
								updateSetting={ updateSetting }
								saveSettings={ saveSettings }
								saving={ saving }
							/>
						) }
						{ tab.name === 'affiliate' && (
							<AffiliateSettings
								settings={ settings }
								updateSetting={ updateSetting }
								saveSettings={ saveSettings }
								saving={ saving }
							/>
						) }
						{ applyFilters(
							'jp4wc.settings.tabContent',
							null,
							tab,
							{
								settings,
								updateSetting,
								saveSettings,
								saving,
							}
						) }
					</div>
				) }
			</TabPanel>

			<div className="jp4wc-settings-info-footer">
				<div className="jp4wc-consultation-notice">
					<h3>
						{ __(
							'For those who are having trouble with WooCommerce',
							'woocommerce-for-japan'
						) }
					</h3>
					<p>
						{ __(
							'We are currently offering 30-minute paid Zoom consultations. A professional from Woo Agency will assess your current situation and suggest the best course of action.',
							'woocommerce-for-japan'
						) }
					</p>
					<a
						href="https://calendar.google.com/calendar/appointments/AcZssZ3mnRQcAL8LU9tPWptKCm05Zge58Oy2jffVIIQ=?gv=true"
						target="_blank"
						rel="noopener noreferrer"
						className="jp4wc-consultation-button button button-primary"
						style={ { backgroundColor: '#8E24AA', borderColor: '#8E24AA' } }
					>
						{ __( '有料相談に申し込む', 'woocommerce-for-japan' ) }
					</a>
				</div>
			</div>
		</div>
	);
};

export default Settings;
