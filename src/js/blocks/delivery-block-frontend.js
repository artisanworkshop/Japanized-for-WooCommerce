import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import './delivery-block-frontend.scss';

const DeliveryFieldsContent = () => {
	// Get script data from localized data
	const scriptData = window.jp4wcDeliveryData || {};
	const {
		deliveryDates = [],
		timeZones = [],
		isDateEnabled = false,
		isTimeEnabled = false,
		isDateRequired = false,
		isTimeRequired = false,
	} = scriptData;

	// Debug: Log the script data
	console.log( '[JP4WC Delivery] Script data:', {
		isDateEnabled,
		isTimeEnabled,
		deliveryDatesCount: deliveryDates.length,
		timeZonesCount: timeZones.length,
	} );

	// Set initial values based on required state
	const getInitialDateValue = () => {
		if ( isDateRequired && deliveryDates.length > 0 ) {
			// If required, select first available date (skip '0' if it exists)
			const firstDate = deliveryDates.find(
				( date ) => date.value !== '0'
			);
			return firstDate ? firstDate.value : deliveryDates[ 0 ].value;
		}
		return '0';
	};

	const getInitialTimeValue = () => {
		if ( isTimeRequired && timeZones.length > 0 ) {
			// If required, select first available time (skip '0' if it exists)
			const firstTime = timeZones.find( ( time ) => time.value !== '0' );
			return firstTime ? firstTime.value : timeZones[ 0 ].value;
		}
		return '0';
	};

	const [ deliveryDate, setDeliveryDate ] = useState( getInitialDateValue() );
	const [ deliveryTime, setDeliveryTime ] = useState( getInitialTimeValue() );

	// Get setExtensionData from checkout store
	const { setExtensionData } = useDispatch( CHECKOUT_STORE_KEY );

	// Check if cart contains only virtual products
	const isVirtualOnly = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		const cartData = store.getCartData?.();
		const items = cartData?.items || [];
		return (
			items.length > 0 &&
			items.every( ( item ) => item.is_virtual === true )
		);
	}, [] );

	// Don't show for virtual-only carts
	if ( isVirtualOnly ) {
		return null;
	}

	// Don't show if neither date nor time is enabled
	if ( ! isDateEnabled && ! isTimeEnabled ) {
		return null;
	}

	// Update extension data when values change
	useEffect( () => {
		if ( setExtensionData ) {
			const extensionData = {
				wc4jp_delivery_date: deliveryDate,
				wc4jp_delivery_time_zone: deliveryTime,
			};
			console.log(
				'[JP4WC Delivery] Setting extension data:',
				extensionData
			);
			setExtensionData( 'jp4wc-delivery', extensionData );
		} else {
			console.error( '[JP4WC Delivery] setExtensionData not available' );
		}
	}, [ deliveryDate, deliveryTime, setExtensionData ] );

	return (
		<div className="wc-block-components-checkout-step__container jp4wc-delivery-fields">
			<h3 className="wc-block-components-checkout-step__title wc-block-components-title">
				{ __(
					'Delivery request date and time',
					'woocommerce-for-japan'
				) }
			</h3>
			<div className="wc-block-components-checkout-step__content">
				{ isDateEnabled && (
					<div className="wc-block-components-text-input delivery-date">
						<SelectControl
							label={ __(
								'Preferred delivery date',
								'woocommerce-for-japan'
							) }
							value={ deliveryDate }
							onChange={ ( value ) => setDeliveryDate( value ) }
							options={ deliveryDates }
							required={ isDateRequired }
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize
						/>
					</div>
				) }
				{ isTimeEnabled && (
					<div className="wc-block-components-text-input delivery-time">
						<SelectControl
							label={ __(
								'Delivery Time Zone',
								'woocommerce-for-japan'
							) }
							value={ deliveryTime }
							onChange={ ( value ) => setDeliveryTime( value ) }
							options={ timeZones }
							required={ isTimeRequired }
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize
						/>
					</div>
				) }
			</div>
		</div>
	);
};

const render = () => {
	return (
		<ExperimentalOrderShippingPackages>
			<DeliveryFieldsContent />
		</ExperimentalOrderShippingPackages>
	);
};

registerPlugin( 'jp4wc-delivery-block', {
	render,
	scope: 'woocommerce-checkout',
} );
