/**
 * Admin Settings Page
 * 
 * Gutenberg block based settings page for Japanized for WooCommerce
 */

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Settings from './components/Settings';

/**
 * Import styles
 */
import './index.scss';

/**
 * Render the settings page
 */
document.addEventListener('DOMContentLoaded', () => {
	const settingsRoot = document.getElementById('jp4wc-admin-settings-root');

	if (settingsRoot) {
		const root = createRoot(settingsRoot);
		root.render(<Settings />);
	}
});

