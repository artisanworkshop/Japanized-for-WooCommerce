/**
 * Main Settings Component
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import GeneralSettings from './GeneralSettings';
import ShipmentSettings from './ShipmentSettings';
import PaymentSettings from './PaymentSettings';
import LawSettings from './LawSettings';
import AffiliateSettings from './AffiliateSettings';

import './Settings.scss';

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

	// Load Google Calendar Scheduling Button
	useEffect( () => {
		// Load CSS
		const link = document.createElement( 'link' );
		link.href =
			'https://calendar.google.com/calendar/scheduling-button-script.css';
		link.rel = 'stylesheet';
		document.head.appendChild( link );

		// Load Script
		const script = document.createElement( 'script' );
		script.src =
			'https://calendar.google.com/calendar/scheduling-button-script.js';
		script.async = true;
		script.onload = () => {
			// Wait a bit for the script to initialize
			setTimeout( () => {
				if ( window.calendar && window.calendar.schedulingButton ) {
					const container = document.querySelector(
						'.jp4wc-consultation-button'
					);
					if ( container ) {
						window.calendar.schedulingButton.load( {
							url: 'https://calendar.google.com/calendar/appointments/AcZssZ3mnRQcAL8LU9tPWptKCm05Zge58Oy2jffVIIQ=?gv=true',
							color: '#8E24AA',
							label: '有料相談に申し込む',
							target: container,
						} );
					}
				}
			}, 100 );
		};
		document.body.appendChild( script );

		return () => {
			// Cleanup
			if ( link.parentNode ) {
				link.parentNode.removeChild( link );
			}
			if ( script.parentNode ) {
				script.parentNode.removeChild( script );
			}
		};
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
				data: updatedSettings,
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
		setSettings( {
			...settings,
			[ key ]: value,
		} );
	};

	if ( loading ) {
		return (
			<div className="jp4wc-settings-loading">
				<Spinner />
				<p>{ __( 'Loading settings...', 'woocommerce-for-japan' ) }</p>
			</div>
		);
	}

	const tabs = [
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
	];

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
					<div className="jp4wc-consultation-button"></div>
				</div>
			</div>
		</div>
	);
};

export default Settings;
