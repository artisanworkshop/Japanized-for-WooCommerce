import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

const JP4WCDeliveryBlockEditor = () => {
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

	// Don't show if neither date nor time is enabled
	if ( ! isDateEnabled && ! isTimeEnabled ) {
		return null;
	}

	return (
		<div className="wc-block-components-checkout-step__container jp4wc-delivery-fields">
			<h3 className="wc-block-components-checkout-step__title">
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
							value=""
							onChange={ () => {} }
							options={ deliveryDates }
							required={ isDateRequired }
							disabled={ true }
							help={ __(
								'This is a preview. The actual field will be shown on the frontend.',
								'woocommerce-for-japan'
							) }
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
							value=""
							onChange={ () => {} }
							options={ timeZones }
							required={ isTimeRequired }
							disabled={ true }
							help={ __(
								'This is a preview. The actual field will be shown on the frontend.',
								'woocommerce-for-japan'
							) }
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize
						/>
					</div>
				) }
			</div>
		</div>
	);
};

export default JP4WCDeliveryBlockEditor;
