
/**
 * These types are by no means correct but accurate atleast to how stripe is
 * currently used and makes the linter happy so do not be afraid to add when
 * needed.
 * */

import {StripePaymentRequest, StripePaymentRequestDetails} from './stripePaymenRequest.ts';

export interface StripeEvent extends Event {
	error?: Error;
}

export interface IStripeElement {
	mount(selector: string): void;
	on(event: string, cb: (event: StripeEvent) => void): void;
}

export interface IStripeResult {
	error: Error;
	token: {
		id: string;
		card: {
			brand: string;
			last4: string;
		};
	};
	paymentIntent: {
		status: string;
	};
}

export interface StripeInstance {
	paymentRequest: (details: StripePaymentRequestDetails) => StripePaymentRequest;
	createToken: (element: IStripeElement) => Promise<IStripeResult>;
	retrievePaymentIntent: (secret: string) => Promise<IStripeResult>;
	elements(options?: StripeElementsInstanceOptions): StripeElementsInstance;
}

export interface StripeElementsInstanceOptions {
	appearance: any;// TODO
	clientSecret: string;
}

export interface StripeElementsInstance {
	create(type: string, options: any): IStripeElement;
}
