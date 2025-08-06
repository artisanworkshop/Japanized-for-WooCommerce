import { __ } from '@wordpress/i18n';
import { 
    PaidyTitle,
    ApplyButton,
    UnderReviewMessage,
} from '../components';
import {
	ReviewRejectedMessage,
	ReviewApprovedMessage,
} from '../../main-hooks/form-info';
import {
	useOnBoardingSettings,
} from '../../main-hooks/on-boarding-settings';

export const OnBoardingStep = () => {
    const {
        currentStep
    } = useOnBoardingSettings();
	const paidyAdUrl = window.paidyForWcSettings?.paidyAdUrl || 'https://paidy.com/campaign/merchant/202404_WW';

	if ( currentStep === 2 ) {
        return (
            <div>
            <PaidyTitle />
            <UnderReviewMessage />
            </div>
        );
    } else if ( currentStep === 3 ) {
        return (
            <div>
            <PaidyTitle />
            <ReviewApprovedMessage />
            </div>
        );
    } else if ( currentStep === 99 ) {
        return (
            <div>
            <PaidyTitle />
            <ReviewRejectedMessage />
            </div>
        );
    } else {
        return (
            <>
            <PaidyTitle />
            <div className="paidy-on-boarding">
                <div className="paidy-on-boarding__img">
                <a href={paidyAdUrl} target="_blank" rel="noreferrer">
                <img src="/wp-content/plugins/woocommerce-for-japan/assets/images/paidy_logo_w800.png" alt="Paidy" />
                </a>
                </div>
                <div className="paidy-on-boarding__content">
                    <p>{ __('Applying for and setting up Paidy is easy.', 'woocommerce-for-japan') }</p>
                    <ApplyButton/>
                </div>
            </div>
            <div className="paidy-on-boarding__description">
                <p>
                    { __( 'There is no setup cost, payment fees start from 3.5%, and if you apply via this service, you will receive a one-month trial with no payment fees!', 'woocommerce-for-japan' ) }
                     <a href={paidyAdUrl} target="_blank" rel="noreferrer">{ __( 'Learn more about Paidy', 'woocommerce-for-japan' ) }</a>
                </p>
            </div>
            </>
        );
    }
}