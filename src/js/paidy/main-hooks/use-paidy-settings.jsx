import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const usePaidySettings = () => {
    const [ settingPaidy, setSettingPaidy ] = useState();
    const [ errorMessage, setErrorMessage ] = useState();

    useEffect( () => {
        apiFetch( { path: '/wc/v3/payment_gateways/paidy' } ).then( ( data ) => {
            setSettingPaidy( data );
        }).catch( ( err ) => {
            setErrorMessage( err.message );
        }
    )});
    return {
        settingPaidy,
        setSettingPaidy,
        errorMessage,
    };
};

export {
    usePaidySettings,
};