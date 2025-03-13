import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	Button,
	Panel,
} from '@wordpress/components';
import { useSettings } from '../hooks';
import { Notices } from './notices';
import {
    ExplainCSG,
    CheckAdminLogin,
    CheckSeucirytPluigns,
    VulnerabilityAssessment,
    CheckMalwareScanner,
    PromotionSecurity,
} from './controls';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Credit Card Security Guidelines Check', 'woocommerce-for-japan' ) }
		</Heading>
	);
};

const SaveButton = ( { onClick } ) => {
	return (
		<div className="jp4wc-save">
		<Button className="jp4wc-save jp4wc-button is-primary" onClick={ onClick }>
			{ __( 'Save', 'woocommerce-for-japan' ) }
		</Button>
		</div>
	);
};

const SecurityPage = () => {
    const {
        checkAdminLogin,
        setCheckAdminLogin,
        checkSeucirytPluigns,
        setCheckSeucirytPluigns,
        saveSettings,
    } = useSettings();
    return (
		<>
			<SettingsTitle />
            <Notices />
            <Panel>
                <ExplainCSG />
                <CheckAdminLogin 
    				value={ checkAdminLogin }
	    			onChange={ ( value ) => setCheckAdminLogin( value ) }
                />
                <CheckSeucirytPluigns
                    value={ checkSeucirytPluigns }
                    onChange={ ( value ) => setCheckSeucirytPluigns( value ) }
                />
                <VulnerabilityAssessment />
                <CheckMalwareScanner />
                <PromotionSecurity />
            </Panel>
            <SaveButton onClick={ saveSettings } />
        </>
    );
};
export { SecurityPage };