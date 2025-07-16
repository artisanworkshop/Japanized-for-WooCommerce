import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
    CheckboxControl,
    ProgressBar,
    Spinner,
    Button,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

const ExplainCSG = () => {
    return (
        <PanelBody title={ __( 'About Credit Card Security Guidelines', 'woocommerce-for-japan' ) }>
            <PanelRow>
                <p className='jp4wc-explain-csg'>
                    { __( 'Ministry of Economy, Trade and Industry has established the "Credit Card Security Guidelines" to prevent unauthorized use of credit card information. This page provides a simple check to see if your site complies with these guidelines.', 'woocommerce-for-japan' ) }
                    <br /><br />
                    <a href="https://www.meti.go.jp/press/2024/03/20250305002/20250305002.html" target="_blank">
                    { __( 'Credit Card Security Guidelines[6.0](2025/3/5 updated)', 'woocommerce-for-japan' ) }
                    </a>
                    <br />
                    <a href="https://www.j-credit.or.jp/security/pdf/security_measures_for_EC_member_stores_installation_guide.pdf" target="_blank">
                    { __( 'Security Measures for E-Commerce Affiliates: Implementation Guide [Appendix 20][PDF]', 'woocommerce-for-japan' ) }
                    </a>
                    <br />
                    <a href="https://www.j-credit.or.jp/security/download/supporting_document_c.xlsx" target="_blank">
                    { __( '[Appendix 20, Appendix c] Declaration of the status of security measures implemented by EC sites (example)[Excel]', 'woocommerce-for-japan' ) }
                    </a>
                    <br /><br />
                    <a href="https://www.j-credit.or.jp/security/document/index.html" target="_blank">
                    { __( 'Related Documents', 'woocommerce-for-japan' ) }
                    </a>
                </p>
            </PanelRow>
        </PanelBody>
    );
}
const CheckAdminLogin = ( { value, onChange } ) => {
    const checkBasic = window.jp4wcSecurityCheckResult?.checkBasic || false;
    const checkIp = window.jp4wcSecurityCheckResult?.checkIp || false;
    const checkTwoFactor = window.jp4wcSecurityCheckResult?.checkTwoFactor || false;
    let messageBasic = '';
    if ( ! checkBasic && ! checkIp ) {
        messageBasic = __( 'Basic authentication or IP restrictions are not set for the administrator login URL.', 'woocommerce-for-japan' );
    }
    return (
        <PanelBody title={ __( 'Administrator screen access restrictions and administrator ID/PW management', 'woocommerce-for-japan' ) }>
            <PanelRow>
                <CheckboxControl
                    label={ __( 'Please check this box if you have implemented security measures for your admin screen.', 'woocommerce-for-japan' ) }
                    help={ __( 'If a warning message appears below, it is possible that your security measures are not in line with the credit card security guidelines.', 'woocommerce-for-japan' ) }
                    className={ "jp4wc_security_check_admin_login" }
                    checked={ value }
                    onChange={ onChange }
                />
            </PanelRow>
                {messageBasic && 
                <PanelRow>
                <div className="jp4wc-security-check__message">
                <h3>{ __( 'Basic authentication or IP restrictions Check result', 'woocommerce-for-japan' ) + __('[Warning]', 'woocommerce-for-japan' ) }</h3>
                <p>{ messageBasic }</p>
                </div>
                </PanelRow>
                }
                {checkTwoFactor === false &&
                <PanelRow>
                <div className="jp4wc-security-check__message">
                <h3>{ __( 'Two-factor authentication Check result', 'woocommerce-for-japan' ) + __('[Warning]', 'woocommerce-for-japan' ) }</h3>
                <p>{ __( 'There is no two-step authentication plugin installed. You need to check whether other plugins support two-step authentication for administrator accounts.', 'woocommerce-for-japan' ) }</p>
                </div>
                </PanelRow>
                }
        </PanelBody>
    );
};

const CheckSeucirytPluigns = ( { value, onChange } ) => {
    const checkPlugins = window.jp4wcSecurityCheckResult?.checkPlugins || false;

    return (
        <PanelBody title={ __( 'Security plugin installation', 'woocommerce-for-japan' ) }>
            <PanelRow>
                <CheckboxControl
                    label={ __( 'Please check this box if you have installed a security plugin.', 'woocommerce-for-japan' ) }
                    help={ __( 'If a warning message appears below, it is possible that your security measures are not in line with the credit card security guidelines.', 'woocommerce-for-japan' ) }
                    className={ "jp4wc_security_check_security_plugins" }
                    checked={ value }
                    onChange={ onChange }
                />
            </PanelRow>
            {checkPlugins === false &&
            <PanelRow>
            <div className="jp4wc-security-check__message">
            <h3>{ __( 'Security plugin Check result', 'woocommerce-for-japan' ) + __('[Warning]', 'woocommerce-for-japan' ) }</h3>
            <p>{ __( 'There is no security plugin installed. You need to check whether other plugins support security measures.', 'woocommerce-for-japan' ) }</p>
            </div>
            </PanelRow>
            }
        </PanelBody>
    );
};

const VulnerabilityAssessment = () => {
    const checkPHPFlag = window.jp4wcSecurityCheckResult?.checkPHPFlag || false;
    const checkUpdateFlag = window.jp4wcSecurityCheckResult?.checkUpdateFlag || false;
    const checkUpdateCoreMessage = window.jp4wcSecurityCheckResult?.checkUpdateCoreMessage || '';
    const checkUpdatePluginsMessage = window.jp4wcSecurityCheckResult?.checkUpdatePluginsMessage || '';
    const checkUpdateThemesMessage = window.jp4wcSecurityCheckResult?.checkUpdateThemesMessage || '';

    return (
        <PanelBody title={ __( 'Vulnerability Assessment', 'woocommerce-for-japan' ) }>
            <PanelRow>
                <p>{__('If you see the following warning message, your system may not be protected from vulnerabilities. Please check the details.','woocommerce-for-japan')}</p>
            </PanelRow>
            <PanelRow>
            <div className="jp4wc-security-check__message">
            {checkPHPFlag === false &&
            <h3>{ __( 'PHP version Check result', 'woocommerce-for-japan' ) + __('[Warning]', 'woocommerce-for-japan' ) }</h3>
            }
            <p>{ window.jp4wcSecurityCheckResult?.checkPHPMessage }</p>
            </div>
            </PanelRow>
            {checkUpdateFlag === false &&
            <PanelRow>
                <div className="jp4wc-security-check__message">
                    <h3>{ __( 'Update Check result', 'woocommerce-for-japan' ) + __('[Warning]', 'woocommerce-for-japan' ) }</h3>
                    {checkUpdateCoreMessage && 
                    <div>{checkUpdateCoreMessage}</div>
                    }
                    {checkUpdatePluginsMessage &&
                    <div>{checkUpdatePluginsMessage}</div>
                    }
                    {checkUpdateThemesMessage &&
                    <div>{checkUpdateThemesMessage}</div>
                    }
                </div>
            </PanelRow>
            }
        </PanelBody>
    );
}

const CheckMalwareScanner = () => {
    const [scanning, setScanning] = useState(false);
    const [progress, setProgress] = useState({ current: 0, total: 0, suspiciousFiles: [] });
    const [complete, setComplete] = useState(false);
    const [error, setError] = useState('');

    const startScan = () => {
        setScanning(true);
        setError('');
        apiFetch({ path: '/jp4wc/v1/security-start-scan', method: 'POST' })
            .then( res => {
                if ( res.totalFiles ) {
                    pollProgress();
                }
            })
            .catch( err => {
                setError( __( 'Scan start failed: ', 'woocommerce-for-japan' ) + err.message);
                setScanning(false);
            });
    };

    const pollProgress = () => {
        apiFetch({ path: '/jp4wc/v1/security-process-scan-batch', method: 'POST' } )
            .then( res => {
                if ( res.current !== undefined ) {
                    setProgress(res);
                }
                if ( res.complete ) {
                    setComplete(true);
                    setScanning(false);
                } else {
                    setTimeout(pollProgress, 500);
                }
            })
            .catch( err => {
                setError( __('Scan progress error: ', 'woocommerce-for-japan' ) + err.message );
                setScanning(false);
        });
    };
    let percent = 0;
    if ( progress.total > 0 ) {
        percent = Math.round(( progress.current / progress.total ) * 100);
    }

    return (
        <PanelBody title={ __( 'Malware security quick check (PHP files only)', 'woocommerce-for-japan' ) }>
            <PanelRow className="jp4wc-security-check__malware-scan">
                { __( 'Check for suspicious PHP files in the wp-content folder in WordPress.', 'woocommerce-for-japan' ) }<br />
                { __( 'This is a simple test, but if you have never checked your server for malware before, please try it. It may take a few minutes to process, so please do not move away from the page.', 'woocommerce-for-japan' ) }<br />
                { __( 'Suspicious files are judged based on whether the following codes are embedded in them: ', 'woocommerce-for-japan' ) }
                { '(eval, gzinflate, str_rot13, shell_exec, system, exec)' }<br />
                { __( 'Note: Ideally, you should check all files, not just PHP files.', 'woocommerce-for-japan' ) }
            </PanelRow>
            <PanelRow className="jp4wc-security-check__malware-scan">
            { error && <p style={{ color: 'red' }}>{ error }</p> }
                { !scanning && !complete && (
                    <Button onClick={ startScan } className='is-primary'>{ __( 'Start Scan', 'woocommerce-for-japan' ) }</Button>
                ) }
                { scanning && (
                    <div>
                        <p>{__( 'Scanning: ', 'woocommerce-for-japan' ) }{ progress.current } / { progress.total } ({ percent }%)</p>
                        <ProgressBar value={ percent } />
                        <Spinner />
                    </div>
                ) }
                { complete && (
                    <div className="jp4wc-security-check__malware-scan-results">
                        <p><b>{ __( 'Scan Complete!', 'woocommerce-for-japan' ) }</b></p>
                        { progress.suspiciousFiles.length > 0 ? (
                            <div>
                                <h3>{ __('Suspicious files:', 'woocommerce-for-japan') }({ progress.suspiciousFiles.length })</h3>
                                <ul>
                                { progress.suspiciousFiles.map( ( item, idx ) => (
                                        <li key={ idx }><p>
                                            { item.file }<br />
                                            <small>
                                                { __( 'Detected pattern:', 'woocommerce-for-japan' ) } { item.pattern }{', '}
                                                { __( 'Line#:', 'woocommerce-for-japan' ) } { item.line }<br />
                                                { __( 'Line:', 'woocommerce-for-japan' ) } { 
                                                item.line_code && item.line_code.length > 100 
                                                    ? item.line_code.substring(0, 100) + '...' 
                                                    : item.line_code 
                                                }
                                             </small></p>
                                        </li>
                                    ) ) }
                                </ul>
                                { __('The number and line number within the file will be displayed so you can verify that the contents are safe.', 'woocommerce-for-japan')}<br />
                                { __('If a line is longer than 100 characters, only the first 100 characters are displayed.', 'woocommerce-for-japan') }<br />
                                { __('If you have any questions, please contact the developer.', 'woocommerce-for-japan') }
                            </div>
                        ) : (
                            <p className='no_suspicious_files'><b>{ __( 'No suspicious files were found.', 'woocommerce-for-japan' ) }</b></p>
                        ) }
                    </div>
                ) }
            </PanelRow>
        </PanelBody>
    );
}

const PromotionSecurity = () => {
    const salesLast30Days = window.jp4wcSecurityCheckResult?.salesLast30Days || false;
    return (
        <PanelBody title={ __( '🚀✨[Limited time offer! Safe and secure professional support]✨🚀', 'woocommerce-for-japan' ) } className="jp4wc-security-check__promotion">
            <PanelRow>
                { __( '🔒 If you\'re worried about security measures, this is a must-see!', 'woocommerce-for-japan' ) }<br />
                { __( 'Our experts will do their best to protect your business💼.', 'woocommerce-for-japan' ) }<br />
            </PanelRow>
            <PanelRow>
                { salesLast30Days > 1000000 &&
                    <Button href="https://wc4jp-pro.work/about-security-service/?utm_source=plugin&utm_medium=link&utm_campaign=jp4wc" target="_blank" rel="noopener noreferrer" className="jp4wc-security-check__promotion-button">
                    { __( 'Security measures for WooCommerce', 'woocommerce-for-japan' ) }
                    </Button>
                }
                { salesLast30Days < 1000000 &&
                    <div>
                    <h3>{ __( '💰 Exclusive for stores aiming to achieve their dream of 1 million yen in monthly sales! 💰', 'woocommerce-for-japan' ) }</h3>
                    { __( '🎉 In addition, this special service is only available on a limited number of sites! 🎉', 'woocommerce-for-japan' ) }<br />
                    { __( '🔮 As a smart investment for the future, you can achieve reliable security measures for only 1,000 yen per month for the first year 💪!', 'woocommerce-for-japan' ) }<br />
                    { __( '🔥 Don\'t miss this chance to take your store operations to the next level right now! 🔥', 'woocommerce-for-japan' ) }<br />
                    <Button href="https://wc4jp-pro.work/about-security-service/descript-starter-plan/?utm_source=plugin&utm_medium=link&utm_campaign=jp4wc" target="_blank" rel="noopener noreferrer" className="jp4wc-security-check__promotion-button">
                    { __( 'Starter Plan: ', 'woocommerce-for-japan' ) }{ __( 'Security measures for WooCommerce', 'woocommerce-for-japan' ) }
                    </Button>
                    </div>
                }
            </PanelRow>
        </PanelBody>
    );
};

export {
    ExplainCSG,
    CheckAdminLogin,
    CheckSeucirytPluigns,
    VulnerabilityAssessment,
    CheckMalwareScanner,
    PromotionSecurity,
};
