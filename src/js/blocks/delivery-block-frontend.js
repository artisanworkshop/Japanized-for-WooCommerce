import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
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


	// Set initial values based on required state
	const getInitialDateValue = () => {
		if ( deliveryDates.length > 0 ) {
			// If required, select first valid date (skip '0' if it exists)
			if ( isDateRequired ) {
				const firstDate = deliveryDates.find(
					( date ) => date.value !== '0'
				);
				return firstDate ? firstDate.value : deliveryDates[ 0 ].value;
			}
			// If not required, use '0' if available, otherwise first option
			const defaultOption = deliveryDates.find(
				( date ) => date.value === '0'
			);
			return defaultOption ? defaultOption.value : deliveryDates[ 0 ].value;
		}
		return '';
	};

	const getInitialTimeValue = () => {
		if ( timeZones.length > 0 ) {
			// If required, select first valid time (skip '0' if it exists)
			if ( isTimeRequired ) {
				const firstTime = timeZones.find( ( time ) => time.value !== '0' );
				return firstTime ? firstTime.value : timeZones[ 0 ].value;
			}
			// If not required, use '0' if available, otherwise first option
			const defaultOption = timeZones.find(
				( time ) => time.value === '0'
			);
			return defaultOption ? defaultOption.value : timeZones[ 0 ].value;
		}
		return '';
	};

	const [ deliveryDate, setDeliveryDate ] = useState( getInitialDateValue() );
	const [ deliveryTime, setDeliveryTime ] = useState( getInitialTimeValue() );

	// Get dispatch function for checkout store only
	const { setAdditionalFields } = useDispatch( CHECKOUT_STORE_KEY );

	// Check if cart contains only virtual products
	const cartData = useSelect(
		( select ) => {
			const store = select( CHECKOUT_STORE_KEY );
			return store.getCartData?.() || {};
		},
		[]
	);

	const items = cartData?.items || [];
	const isVirtualOnly = items.length > 0 && items.every( ( item ) => item.is_virtual === true );

	// Don't show for virtual-only carts
	if ( isVirtualOnly ) {
		return null;
	}

	// Don't show if neither date nor time is enabled
	if ( ! isDateEnabled && ! isTimeEnabled ) {
		return null;
	}

	// Update additional_fields when values change
	// WooCommerce handles validation automatically based on field 'required' property
	useEffect( () => {
		if ( setAdditionalFields ) {
			const additionalFieldsData = {};
			
			if ( isDateEnabled ) {
				additionalFieldsData[ 'jp4wc/delivery-date' ] = deliveryDate;
			}
			
			if ( isTimeEnabled ) {
				additionalFieldsData[ 'jp4wc/delivery-time' ] = deliveryTime;
			}
			
			setAdditionalFields( additionalFieldsData );
		}
	}, [ deliveryDate, deliveryTime, setAdditionalFields, isDateEnabled, isTimeEnabled ] );

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
