import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { FirstMainPage } from './components/first-main-page';
import './index.scss';

const BackAdminFooter = () => {
    return (
        <div className="paidy-on-boarding footer">
            <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paidy">
            { __( 'Back to Paidy settings', 'woocommerce-for-japan') }
            </a>
        </div>
    );
}

const PaidyOnBoardingPage = () => {
    return (
        <>
        <FirstMainPage />
        <BackAdminFooter />
        </>
    );
}

addFilter( 'woocommerce_admin_pages_list', 'paidy-on-boarding', ( pages ) => {
	pages.push( {
		container: PaidyOnBoardingPage,
		path: '/paidy-on-boarding',
		breadcrumbs: [ __( 'Easy setup for Paidy payment', 'woocommerce-for-japan' ) ],
        capability: 'manage_woocommerce'
	} );

	return pages;
} );
