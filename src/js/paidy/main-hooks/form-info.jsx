import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	Button,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const FlowExplanation = () => {
	return (
		<>
			<Heading level={ 3 }>
				{ __( 'Application flow', 'woocommerce-for-japan' ) }
			</Heading>
			<ol className="paidy-flow">
				<li>
					{ __(
						'Please fill in all the fields on this application screen and click the "Agree to the above and apply" button at the bottom of the screen.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'Paidy Co., Ltd. will review your application information and may take up to 5 business days.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'The results will be notified via email and dashboard.',
						'woocommerce-for-japan'
					) }
				</li>
				<li>
					{ __(
						'If your application is approved, you will be able to activate Paidy from your dashboard.',
						'woocommerce-for-japan'
					) }{ ' ' }
					<br />
					{ __(
						'If your application is rejected, you will not be able to use Paidy.',
						'woocommerce-for-japan'
					) }
				</li>
			</ol>
		</>
	);
};

const AgreementInfo = () => {
	return (
		<>
			<div className="paidy-heading">
				<Heading level={ 3 }>
					{ __( 'Agreement', 'woocommerce-for-japan' ) }
				</Heading>
			</div>
			<div className="paidy-agreement">
				<p className="paidy-agreement-text">
					{ __(
						'Please confirm and agree to the following contents.',
						'woocommerce-for-japan'
					) }
				</p>
				<ol>
					<li>
						<a href="https://terms.paidy.com/" target="_blank">
							{ __(
								'Paidy Merchant Terms and Conditions',
								'woocommerce-for-japan'
							) }
						</a>
					</li>
					<li>
						{ __(
							'Agreement on handling of affiliated store information',
							'woocommerce-for-japan'
						) }
					</li>
					<li>
						{ __(
							'Add the notation specified by our company to the notation based on the Specified Commercial Transactions Act.',
							'woocommerce-for-japan'
						) }
						<br />
						<a
							href="https://merchant-support.paidy.com/hc/ja/articles/16629903258649-%E7%89%B9%E5%95%86%E6%B3%95%E3%81%AB%E5%9F%BA%E3%81%A5%E3%81%8F%E8%A1%A8%E7%A4%BA%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB"
							target="_blank"
						>
							{ __(
								'Display sample based on the Specified Commercial Transactions Act',
								'woocommerce-for-japan'
							) }
						</a>
					</li>
					<li>
						{ __(
							'Add the specified notation to the privacy policy page.',
							'woocommerce-for-japan'
						) }
						<br />
						<a
							href="https://merchant-support.paidy.com/hc/ja/articles/16631714849561-%E3%83%97%E3%83%A9%E3%82%A4%E3%83%90%E3%82%B7%E3%83%BC%E3%83%9D%E3%83%AA%E3%82%B7%E3%83%BC%E3%81%AE%E8%A8%98%E5%85%A5%E4%BE%8B"
							target="_blank"
						>
							{ __(
								'Example of a privacy policy',
								'woocommerce-for-japan'
							) }
						</a>
					</li>
					<li>
						{ __(
							'Paidy Inc. will set up the API key on your e-commerce site via WooCommerce on your behalf.',
							'woocommerce-for-japan'
						) }
					</li>
					<li>
						{ __(
							'Our company (Shokunin Kobo LLC) will provide personal information to third parties when disclosing such information for purposes such as member store screening by payment companies.',
							'woocommerce-for-japan'
						) }
					</li>
				</ol>
			</div>
		</>
	);
};

const ApplyCompletedMessage = () => {
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

const ReviewRejectedMessage = () => {
	return (
		<div className="paidy-rejected-message">
			<Heading level={ 3 }>
				{ __(
					'Status: The review was rejected',
					'woocommerce-for-japan'
				) }
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

const EnableTestButton = ( { onClick } ) => {
	const [ environment, setEnvironment ] = useState();

	useEffect( () => {
		// WooCommerce Payment Gateway APIを使用
		apiFetch( { path: '/wc/v3/payment_gateways/paidy' } )
			.then( ( response ) => {
				const currentEnvironment =
					response.settings?.environment?.value ||
					response.settings?.environment ||
					'';
				setEnvironment( currentEnvironment );
			} )
			.catch( ( error ) => {
				return apiFetch( { path: '/wp/v2/settings' } );
			} )
			.then( ( settings ) => {
				if ( settings ) {
					const paidySettings =
						settings.woocommerce_paidy_settings || {};
					if ( ! environment ) {
						// まだ環境が設定されていない場合のみ
						setEnvironment( paidySettings.environment || '' );
					}
				}
			} )
			.catch( ( error ) => {
				console.error( 'Settings API Error:', error );
			} );
	}, [] );

	if ( environment === 'sandbox' ) {
		return (
			<div className="paidy-enabled-test-message">
				{ __( 'Now test mode', 'woocommerce-for-japan' ) }
			</div>
		);
	} else {
		return (
			<Button className="paidy-button test-button" onClick={ onClick }>
				{ __( 'Enable test mode', 'woocommerce-for-japan' ) }
			</Button>
		);
	}
};

const ReviewApprovedMessage = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ environment, setEnvironment ] = useState();
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( noticesStore );
	const restUrl = window.paidyForWcSettings?.restUrl || '';

	useEffect( () => {
		apiFetch( { path: '/wc/v3/payment_gateways/paidy' } )
			.then( ( response ) => {
				const currentEnvironment =
					response.settings?.environment?.value ||
					response.settings?.environment ||
					'';
				console.log( 'Current Environment:', currentEnvironment );
				setEnvironment( currentEnvironment );

				// environmentがsandboxまたはliveの場合、CSSを変更
				if (
					currentEnvironment === 'sandbox' ||
					currentEnvironment === 'live'
				) {
					const paidySettingsElement = document.getElementById(
						'paidy-payment-settings'
					);
					if ( paidySettingsElement ) {
						paidySettingsElement.style.display = 'block';
						console.log(
							'CSS updated: #paidy-payment-settings display set to block'
						);
					} else {
						console.warn(
							'Element #paidy-payment-settings not found'
						);
					}
				}
			} )
			.catch( ( error ) => {
				console.error( 'Failed to fetch environment:', error );
				// フォールバック: WordPress Settings API
				return apiFetch( { path: '/wp/v2/settings' } );
			} )
			.then( ( settings ) => {
				if ( settings && ! environment ) {
					const paidySettings =
						settings.woocommerce_paidy_settings || {};
					const fallbackEnvironment = paidySettings.environment || '';
					setEnvironment( fallbackEnvironment );

					// フォールバックでも同様にCSSを変更
					if (
						fallbackEnvironment === 'sandbox' ||
						fallbackEnvironment === 'live'
					) {
						const paidySettingsElement = document.getElementById(
							'paidy-payment-settings'
						);
						if ( paidySettingsElement ) {
							paidySettingsElement.style.display = 'block';
							console.log(
								'CSS updated (fallback): #paidy-payment-settings display set to block'
							);
						}
					}
				}
			} )
			.catch( ( error ) => {
				console.error( 'Failed to fetch settings:', error );
			} );
	}, [] );

	const onSavingTestMode = () => {
		apiFetch( {
			path: '/wc/v3/payment_gateways/paidy',
			method: 'PUT',
			data: {
				enabled: true,
				settings: {
					environment: 'sandbox',
				},
			},
		} )
			.then( ( response ) => {
				createSuccessNotice(
					__( 'Settings saved.', 'woocommerce-for-japan' )
				);
				window.location.href =
					'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy';
			} )
			.catch( ( error ) => {
				setIsLoading( false );
				createErrorNotice(
					error.message ||
						__( 'Error enabling Paidy', 'woocommerce-for-japan' ),
					{
						type: 'snackbar',
						isDismissible: true,
						autoDismiss: false,
					}
				);
			} );
	};

	const onSavingProductionMode = () => {
		apiFetch( {
			path: '/wc/v3/payment_gateways/paidy',
			method: 'PUT',
			data: {
				enabled: true,
				settings: {
					environment: 'live',
				},
			},
		} )
			.then( ( response ) => {
				window.location.href =
					'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy';
			} )
			.catch( ( error ) => {
				setIsLoading( false );
				createErrorNotice(
					error.message ||
						__( 'Error enabling Paidy', 'woocommerce-for-japan' ),
					{
						type: 'snackbar',
						isDismissible: true,
						autoDismiss: false,
					}
				);
			} );
	};
	if ( environment === 'sandbox' ) {
		return (
			<>
				<SettingSandboxMessage />
				<div className="paidy-enabled-button">
					<Button
						className="paidy-button production-button"
						onClick={ onSavingProductionMode }
						disabled={ isLoading }
					>
						{ __(
							'Enable production mode',
							'woocommerce-for-japan'
						) }
					</Button>
				</div>
			</>
		);
	} else if ( environment === 'live' ) {
		return <SettingCompletedMessage />;
	} else {
		return (
			<div className="paidy-approved-message">
				<Heading level={ 3 }>
					{ __(
						'Status: The review approved',
						'woocommerce-for-japan'
					) }
				</Heading>
				<p>
					{ __(
						'The review has been completed and the merchant agreement has been concluded.',
						'woocommerce-for-japan'
					) }
					<br />
					{ __(
						'For terms of use, please check the terms and conditions notification email sent by Paidy Inc.',
						'woocommerce-for-japan'
					) }
				</p>
				<ul className="paidy-approved-list">
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
						) }
					</li>
					<li>{ restUrl }/wp-json/paidy/v1/order/</li>
					<li>
						{ __(
							'Please refer to the manual for information on the Paidy merchant management screen.',
							'woocommerce-for-japan'
						) }
					</li>
				</ul>
				<div className="paidy-enabled-button">
					<p>
						{ __(
							'Please click one of the buttons below.',
							'woocommerce-for-japan'
						) }
					</p>
					<EnableTestButton onClick={ onSavingTestMode } />
					<br />
					<br />
					<Button
						className="paidy-button production-button"
						onClick={ onSavingProductionMode }
						disabled={ isLoading }
					>
						{ __(
							'Enable production mode',
							'woocommerce-for-japan'
						) }
					</Button>
				</div>
			</div>
		);
	}
};

const SettingSandboxMessage = () => {
	return (
		<div className="paidy-setting-sandbox-message">
			<Heading level={ 3 }>
				{ __( 'Paidy test payments enabled', 'woocommerce-for-japan' ) }
			</Heading>
			<p>
				{ __(
					'You can now test Paidy payments.',
					'woocommerce-for-japan'
				) }
				<br />
				{ __(
					'Please check the test payment flow.',
					'woocommerce-for-japan'
				) }
				<br />
				<a
					href="https://paidy.com/docs/jp/testing.html"
					target="_blank"
				>
					{ __( 'Paidy test payment flow', 'woocommerce-for-japan' ) }
				</a>
				<br />
				{ __(
					'After confirming the test payment, please switch to production mode.',
					'woocommerce-for-japan'
				) }
			</p>
		</div>
	);
};

const SettingCompletedMessage = () => {
	return (
		<div className="paidy-setting-completed-message">
			<Heading level={ 3 }>
				{ __( 'Paidy Payments is now live', 'woocommerce-for-japan' ) }
			</Heading>
		</div>
	);
};

export {
	FlowExplanation,
	AgreementInfo,
	ApplyCompletedMessage,
	ReviewRejectedMessage,
	ReviewApprovedMessage,
	SettingSandboxMessage,
	SettingCompletedMessage,
};
