import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useSettings = () => {
	const [ checkAdminLogin, setCheckAdminLogin ] = useState();
	const [ checkSeucirytPluigns, setCheckSeucirytPluigns ] = useState();

	const { createErrorNotice } = useDispatch( noticesStore );
	const { createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( settings ) => {
			setCheckAdminLogin(
				settings.jp4wc_security_settings.checkAdminLogin
			);
			setCheckSeucirytPluigns(
				settings.jp4wc_security_settings.checkSeucirytPluigns
			);
		} );
	}, [] );

	const saveSettings = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				jp4wc_security_settings: {
					checkAdminLogin,
					checkSeucirytPluigns,
				},
			},
		} )
			.then( () => {
				createSuccessNotice(
					__( 'Settings saved.', 'woocommerce-for-japan' )
				);
			} )
			.catch( ( error ) => {
				createErrorNotice( error.message );
			} );
	};

	return {
		checkAdminLogin,
		setCheckAdminLogin,
		checkSeucirytPluigns,
		setCheckSeucirytPluigns,
		saveSettings,
	};
};

export default useSettings;
