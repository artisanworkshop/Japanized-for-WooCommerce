/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';

const settings = getPaymentMethodData( 'paidy', {} );
const defaultLabel = __( 'Paidy', 'woocommerce-for-japan' );
const label = decodeEntities( settings?.title || '' ) || defaultLabel;
const content = settings.description + settings.paidy_description;
const icons = settings.icons || '';

/**
 * Content component
 */
const Content = () => {
	return <RawHTML>{ content || '' }</RawHTML>;
};

/**
 * Get Web Money icons
 *
 * @return {Array} Array of Web Money icons.
 *
 */
const getPaidyIcons = () => {
	const [ [ id, { src, alt } ] ] = Object.entries( icons );
	return [ { id, src, alt } ];
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel, PaymentMethodIcons } = props.components;
	const paidyIcons = getPaidyIcons();
	return (
		<div style={ { display: 'flex', alignItems: 'center' } }>
			<PaymentMethodLabel text={ label } />
			<PaymentMethodIcons icons={ paidyIcons } align="left" />
		</div>
	);
};

/**
 * BANK PAYMENT IN JAPAN payment method config object.
 */
const paidyTransferPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( paidyTransferPaymentMethod );
