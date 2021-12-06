// Deno-lint-ignore-file camelcase

import {IWindowMessage} from '../util/dom.ts';
import {ICartCalculationRecord} from './ICartCalculation.ts';

export type StripePaymentRequestStatus = 'success' | 'fail' | 'invalid_payer_name' | 'invalid_payer_phone' | 'invalid_payer_email' | 'invalid_shipping_address';

export type StripeShippingOption = {
	id: string;
	label: string;
	amount: number;
	detail?: string;
};
export type StripePaymentItem = {
	amount: number;
	label: string;
	pending?: boolean;
};
export type PaymentAvailability = {applePay: boolean; googlePay: boolean};
export type StripePaymentResponse = {
	token: StripeToken;
	complete: (status: StripePaymentRequestStatus) => void;
	payerName: string;
	payerEmail: string;
	payerPhone: string;
	shippingAddress: StripeShippingAddress;
	shippingOption: StripeShippingOption;
	walletName: string;
};

export interface StripeToken {
	id: string;
	walletName: string;
	card: StripeCard;
}

export interface StripeCard {
	id: string;
	brand: string;
	name: string;
	last4: string;
	address_city: string;
	address_country: string;
	address_line1: string;
	address_line2: string;
	address_state: string;
}

export type StripePaymentRequest = {
	canMakePayment: () => Promise<PaymentAvailability | null>;
	update: (options: StripePaymentRequestUpdateDetails) => void;
	isShowing: () => boolean;
	on: (event: string, listener: (event: any) => Promise<void> | void) => void;
};

export type StripeShippingAddressChangedEvent = {
	shippingAddress: StripeShippingAddress;
	updateWith: (details: StripeUpdateDetails) => void;
};

export type StripeShippingOptionChangedEvent = {
	shippingOption: StripeShippingOption;
	updateWith: (details: StripeUpdateDetails) => void;
};

export interface StripeShippingAddress {
	country: string;
	addressLine: string[];
	region: string;
	city: string;
	postalCode: string;
	recipient: string;
	phone: string;
	sortingCode: string;
	dependentLocality: string;
}

export interface StripePaymentRequestDetails {
	country: string;
	currency: string;

	total: {
		label: string;
		amount: number;
	};

	displayItems?: StripePaymentItem[];
	shippingOptions?: StripeShippingOption[];

	requestPayerName?: boolean;
	requestPayerEmail?: boolean;
	requestPayerPhone?: boolean;
	requestShipping?: boolean;
}

export interface StripePaymentRequestUpdateDetails {
	currency: string;
	total: {
		label: string;
		amount: number;
	};
	displayItems?: StripePaymentItem[];
	shippingOptions?: StripeShippingOption[];
}

export interface StripeUpdateDetails {
	status: 'success' | 'fail' | 'invalid_shipping_address';
	total: StripePaymentItem;
	displayItems?: StripePaymentItem[];
	shippingOptions?: StripeShippingOption[];
}

export interface ButtonInitEvent extends IWindowMessage {
	stripe: {
		locale: string;
		live: boolean;
	};
	currencyCode: string;
	cartCalculationRecord: ICartCalculationRecord;
}

export interface ButtonUpdateEvent extends IWindowMessage {
	currencyCode: string;
	cartCalculationRecord: ICartCalculationRecord;
}

export interface ButtonPaymentProcessedEvent {
	status: 'success' | 'fail';
	redirectURL?: string;
}

export interface StripePaymentToken {
	token: StripeToken;
	payerName: string;
	payerEmail: string;
	payerPhone: string;
	shippingAddress: StripeShippingAddress;
	shippingOption: StripeShippingOption;
	walletName: string;
}
