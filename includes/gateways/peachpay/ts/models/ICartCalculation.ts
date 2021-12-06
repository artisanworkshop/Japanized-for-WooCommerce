// Deno-lint-ignore-file camelcase

export interface IShippingMethod {
	title: string;
	total: number;
}

export interface IShippingPackage {
	package_name: string;
	selected_method: string;
	methods: Record<string, IShippingMethod | undefined>;
}

export interface ICartCalculation {
	package_record: Record<string, IShippingPackage | undefined>;
	cart: unknown;
	summary: {
		fees_record: Record<string, number | undefined>;
		coupons_record: Record<string, number | undefined>;
		gift_card_record: Record<string, number | undefined>;// Currently empty record is returned.
		subtotal: number;
		total_shipping: number;
		total_tax: number;
		total: number;
	};
	cart_meta: ICartMetaData;
}

export interface ICartMetaData {
	is_virtual: boolean;
	subscription?: ISubscriptionMeta;
}

export interface ISubscriptionMeta {
	length: number;
	period: string;
	period_interval: number;
	first_renewal: string;
}

export interface IWCNotice {
	notice: string;
	data: unknown[];
}

export interface ICartCalculationResponse {
	success: boolean;
	notices: Record<'success' | 'error', IWCNotice[]>;
	data?: {
		cart_calculation_record: ICartCalculationRecord;
	};
}

export interface ICartCalculationRecord {
	[key: string]: ICartCalculation | undefined;
	// Default cart key
	'0': ICartCalculation;
}

// What is sent. Could be a lot less if we do not ping to our server and back
export interface ICartCalculationRequest {
	session: {
		id: string;
		merchant_name: string;
		merchant_hostname: string;
	};
	order: {
		currency_code: string;
		cart: unknown;
		coupons: string[];
		gift_cards: string[];
		selected_shipping: Record<string, string>;
		shipping_location: {
			country: string;
			state: string;
			postcode: string;
			city: string;
		};
	};
}

