import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	Button,
} from '@wordpress/components';

const PaidyTitle = () => {
	const heading_text = __(
		'Easy Paidy payment setup',
		'woocommerce-for-japan'
	);
	return <Heading level={ 2 }>{ heading_text }</Heading>;
};

const ApplyButton = ( { onClick } ) => {
	const handleClick = () => {
		window.location.href =
			'/wp-admin/admin.php?page=wc-admin&path=%2Fpaidy-on-boarding';
	};

	return (
		<Button
			className="paidy-setting-step1 paidy-button"
			onClick={ handleClick }
		>
			{ __( 'Start easy Paidy payment setup', 'woocommerce-for-japan' ) }
		</Button>
	);
};

const UnderReviewMessage = () => {
	return (
		<div className="paidy-completed-message">
			<Heading level={ 3 }>
				{ __( 'Status: Under review', 'woocommerce-for-japan' ) }
			</Heading>
			<p>
				{ __(
					'Thank you for your application. We will notify you of the results via email and dashboard.',
					'woocommerce-for-japan'
				) }
			</p>
			<ul>
				<li>
					{ __(
						'The review process may take up to 5 business days.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'Inquiries regarding screening: sales@paidy.com',
						'woocommerce-for-japan'
					) }
				</li>
			</ul>
		</div>
	);
};

const ReviewApprovedMessage = () => {
	const restUrl = window.paidyForWcSettings?.restUrl || '';
	return (
		<div className="paidy-completed-message">
			<Heading level={ 3 }>
				{ __( 'Status: Approved', 'woocommerce-for-japan' ) }
			</Heading>
			<p>
				{ __(
					'The review has been completed and the merchant agreement has been concluded.',
					'woocommerce-for-japan'
				) }
			</p>
			<p>
				{ __(
					'For terms of use, please check the terms and conditions notification email sent by Paidy Inc.',
					'woocommerce-for-japan'
				) }
			</p>
			<ul>
				<li>
					{ __(
						'We recommend setting the Webhook URL in the Paidy merchant management screen before publishing in production mode. Set the Webhook URL to the following value.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'[Common for test and production]',
						'woocommerce-for-japan'
					) }{ ' ' }
					{ restUrl }wp-json/paidy/v1/order/
				</li>
				<li>
					{ __(
						'Please refer to the manual for information on the Paidy merchant management screen.',
						'woocommerce-for-japan'
					) }
				</li>
			</ul>
		</div>
	);
};

const ReviewRejectedMessage = () => {
	return (
		<div className="paidy-completed-message">
			<Heading level={ 3 }>
				{ __( 'Status: Rejected', 'woocommerce-for-japan' ) }
			</Heading>
			<ul>
				<li>
					{ __(
						'After careful and repeated review, we regret to inform you that we have decided to postpone the transaction at this time.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'We sincerely apologize for not being able to meet your expectations.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'Please understand that we cannot provide details about the content of the review.',
						'woocommerce-for-japan'
					) }
				</li>
			</ul>
		</div>
	);
};

export {
	PaidyTitle,
	ApplyButton,
	UnderReviewMessage,
	ReviewApprovedMessage,
	ReviewRejectedMessage,
};
