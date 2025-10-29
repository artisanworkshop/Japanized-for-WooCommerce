import { sprintf, __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useOnBoardingSettings = () => {
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ storeName, setStoreName ] = useState();
	const [ siteName, setSiteName ] = useState();
	const [ storeUrl, setStoreUrl ] = useState();
	const [ registEmail, setRegistEmail ] = useState();
	const [ contactPhone, setContactPhone ] = useState();
	const [ representativeLastName, setRepesentativeLastName ] = useState();
	const [ representativeFirstName, setRepresentativeFirstName ] = useState();
	const [ representativeLastNameKana, setRepresentativeLastNameKana ] = useState();
	const [ representativeFirstNameKana, setRepresentativeFirstNameKana ] = useState();
	const [ representativeDateOfBirth, setRepresentativeDateOfBirth ] = useState();
	const [ annualGrossValue, setAnnualGrossValue ] = useState();
	const [ averagePurchaseAmount, setAveragePurchaseAmount ] = useState();
	const [ securitySurvey01RadioControl, setSecuritySurvey01RadioControl ] = useState();
	const [ securitySurvey01TextControl, setSecuritySurvey01TextControl ] = useState( '' );
	const [ securitySurvey11CheckControl, setSecuritySurvey11CheckControl ] = useState();
	const [ securitySurvey12CheckControl, setSecuritySurvey12CheckControl ] = useState();
	const [ securitySurvey13CheckControl, setSecuritySurvey13CheckControl ] = useState();
	const [ securitySurvey14CheckControl, setSecuritySurvey14CheckControl ] = useState();
	const [ securitySurvey10TextAreaControl, setSecuritySurvey10TextAreaControl ] = useState('');
	const [ securitySurvey08RadioControl, setSecuritySurvey08RadioControl ] = useState('yes');
	const [ securitySurvey09RadioControl, setSecuritySurvey09RadioControl ] = useState('yes');

	const { createErrorNotice, createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( settings ) => {
			const onBoardingSettings = settings.woocommerce_paidy_on_boarding_settings;
			
			setCurrentStep( Number( onBoardingSettings.currentStep || 0 ) );
			setStoreName( onBoardingSettings.storeName || '' );
			setSiteName( onBoardingSettings.siteName || '' );
			setStoreUrl( onBoardingSettings.storeUrl || '' );
			setRegistEmail( onBoardingSettings.registEmail || '' );
			setContactPhone( onBoardingSettings.contactPhone || '' );
			setRepesentativeLastName( onBoardingSettings.representativeLastName || '' );
			setRepresentativeFirstName( onBoardingSettings.representativeFirstName || '' );
			setRepresentativeLastNameKana( onBoardingSettings.representativeLastNameKana || '' );
			setRepresentativeFirstNameKana( onBoardingSettings.representativeFirstNameKana || '' );
			setRepresentativeDateOfBirth( onBoardingSettings.representativeDateOfBirth );
			setAnnualGrossValue( onBoardingSettings.annualGrossValue );
			setAveragePurchaseAmount( onBoardingSettings.averagePurchaseAmount );
			setSecuritySurvey01RadioControl( onBoardingSettings.securitySurvey01RadioControl || '' );
			setSecuritySurvey01TextControl( onBoardingSettings.securitySurvey01TextControl || '' );
			setSecuritySurvey11CheckControl( onBoardingSettings.securitySurvey11CheckControl );
			setSecuritySurvey12CheckControl( onBoardingSettings.securitySurvey12CheckControl );
			setSecuritySurvey13CheckControl( onBoardingSettings.securitySurvey13CheckControl );
			setSecuritySurvey14CheckControl( onBoardingSettings.securitySurvey14CheckControl );
			setSecuritySurvey10TextAreaControl( onBoardingSettings.securitySurvey10TextAreaControl || '' );
			setSecuritySurvey08RadioControl( onBoardingSettings.securitySurvey08RadioControl || 'yes' );
			setSecuritySurvey09RadioControl( onBoardingSettings.securitySurvey09RadioControl || 'yes' );
		} );
	}, [] );

	const saveSettings = () => {
		let requiredFlag = false;
		const requiredFields = [
			{ value: storeName, label: __( 'Trade name / Store name', 'woocommerce-for-japan' ) },
			{ value: siteName, label: __( 'Your EC site name', 'woocommerce-for-japan' ) },
			{ value: storeUrl, label: __( 'Store URL', 'woocommerce-for-japan' ) },
			{ value: registEmail, label: __( 'Email address for registering Paidy', 'woocommerce-for-japan' ) },
			{ value: contactPhone, label: __( 'Contact phone number', 'woocommerce-for-japan' ) },
			{ value: representativeLastName, label: __( 'Representative (last name)', 'woocommerce-for-japan' ) },
			{ value: representativeFirstName, label: __( 'Representative (first name)', 'woocommerce-for-japan' ) },
			{ value: representativeLastNameKana, label: __( 'Representative (last name kana)', 'woocommerce-for-japan' ) },
			{ value: representativeFirstNameKana, label: __( 'Representative (first name kana)', 'woocommerce-for-japan' ) },
			{ value: representativeDateOfBirth, label: __( 'Representative\'s date of birth (Gregorian calendar)', 'woocommerce-for-japan' ) },
		];
		requiredFields.forEach( ( field ) => {
			if ( ! field.value ) {
				createErrorNotice(
					/* translators: %s: field label name */
					sprintf( __( '%s is a required field. Please enter it.', 'woocommerce-for-japan' ), field.label ),
					{ type: 'snackbar', isDismissible: true, autoDismiss: false }
				);
				requiredFlag = true;
			}
		} );

		let kanaFlag = false;
		const fullWidthKatakanaRegex = /^[ァ-ヶー]+$/;
		const requiredKanaFields = [
			{ value: representativeLastNameKana, label: __( 'Representative (last name kana)', 'woocommerce-for-japan' ) },
			{ value: representativeFirstNameKana, label: __( 'Representative (first name kana)', 'woocommerce-for-japan' ) },
		];
		requiredKanaFields.forEach( ( field ) => {
			if ( ! fullWidthKatakanaRegex.test( field.value ) ) {
				createErrorNotice(
					/* translators: %s: field label name */
					sprintf( __( '%s must be in full-width katakana. Please confirm.', 'woocommerce-for-japan'), field.label ),
					{ type: 'snackbar', isDismissible: true, autoDismiss: false }
				);
			kanaFlag = true;
			}
		} );

		let securityValidationFlag = false;
		if ( securitySurvey01RadioControl === 'no' && !securitySurvey01TextControl?.trim() ) {
			createErrorNotice(
				__( 'When selecting "No" for the security survey question, please provide additional details in the text field.', 'woocommerce-for-japan' ),
				{ type: 'snackbar', isDismissible: true, autoDismiss: false }
			);
			securityValidationFlag = true;
		}

		const hasAnyYesCheck = securitySurvey11CheckControl === true || 
							securitySurvey12CheckControl === true || 
							securitySurvey13CheckControl === true || 
							securitySurvey14CheckControl === true;

		if ( !hasAnyYesCheck && !securitySurvey10TextAreaControl?.trim() ) {
			createErrorNotice(
				__( 'Since none of the security measures are implemented, please provide details about your security approach in the additional information field.', 'woocommerce-for-japan' ),
				{ type: 'snackbar', isDismissible: true, autoDismiss: false }
			);
			securityValidationFlag = true;
		}

		if ( requiredFlag  ) {
			createErrorNotice(
				__( 'Please fill in all the fields.', 'woocommerce-for-japan' ),
				 { type: 'snackbar', isDismissible: true, autoDismiss: false }
			);
			return;
		}

		if ( kanaFlag || securityValidationFlag ) {
			return;
		}

		const nextStep = currentStep + 1;
		setCurrentStep( nextStep );

		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				woocommerce_paidy_on_boarding_settings: {
					currentStep: nextStep,
					storeName,
					siteName,
					storeUrl,
					registEmail,
					contactPhone,
					representativeLastName,
					representativeFirstName,
					representativeLastNameKana,
					representativeFirstNameKana,
					representativeDateOfBirth,
					annualGrossValue,
					averagePurchaseAmount,
					securitySurvey01RadioControl,
					securitySurvey01TextControl,
					securitySurvey11CheckControl,
					securitySurvey12CheckControl,
					securitySurvey13CheckControl,
					securitySurvey14CheckControl,
					securitySurvey10TextAreaControl,
					securitySurvey08RadioControl,
					securitySurvey09RadioControl,
				},
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'Settings saved.', 'woocommerce-for-japan' )
			);
		} );
	};

	return {
		currentStep,
		setCurrentStep,
		storeName,
		setStoreName,
		siteName,
		setSiteName,
		storeUrl,
		setStoreUrl,
		registEmail,
		setRegistEmail,
		contactPhone,
		setContactPhone,
		representativeLastName,
		setRepesentativeLastName,
		representativeFirstName,
		setRepresentativeFirstName,
		representativeLastNameKana,
		setRepresentativeLastNameKana,
		representativeFirstNameKana,
		setRepresentativeFirstNameKana,
		representativeDateOfBirth,
		setRepresentativeDateOfBirth,
		annualGrossValue,
		setAnnualGrossValue,
		averagePurchaseAmount,
		setAveragePurchaseAmount,
		securitySurvey01RadioControl,
		setSecuritySurvey01RadioControl,
		securitySurvey01TextControl,
		setSecuritySurvey01TextControl,
		securitySurvey11CheckControl,
		setSecuritySurvey11CheckControl,
		securitySurvey12CheckControl,
		setSecuritySurvey12CheckControl,
		securitySurvey13CheckControl,
		setSecuritySurvey13CheckControl,
		securitySurvey14CheckControl,
		setSecuritySurvey14CheckControl,
		securitySurvey10TextAreaControl,
		setSecuritySurvey10TextAreaControl,
		securitySurvey08RadioControl,
		setSecuritySurvey08RadioControl,
		securitySurvey09RadioControl,
		setSecuritySurvey09RadioControl,
        saveSettings,
    };
};

export {
	useOnBoardingSettings
};