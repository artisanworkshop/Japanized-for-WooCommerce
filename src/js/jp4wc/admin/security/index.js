import './index.scss';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { SecurityPage } from './components';

const SeuciryCheckPage = () => {
	return <SecurityPage />;
};

addFilter( 'woocommerce_admin_pages_list', 'security-check', ( pages ) => {
	pages.push( {
		container: SeuciryCheckPage,
		path: '/jp4wc-security-check',
		breadcrumbs: [
			__(
				'Simple check to comply with credit card security guidelines (established by the Ministry of Economy, Trade and Industry)',
				'woocommerce-for-japan'
			),
		],
		navArgs: {
			id: 'jp4wc-security-check',
		},
	} );

	return pages;
} );
