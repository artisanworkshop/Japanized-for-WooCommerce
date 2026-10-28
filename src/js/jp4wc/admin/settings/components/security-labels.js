/**
 * Labels of the security check (issue #259).
 *
 * The check IDs and requirements are those of JP4WC_Security_Checks::CHECKS.
 * Functions rather than constants, so that the strings are translated when
 * they are shown.
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Severities, most severe first.
 */
export const SEVERITY_ORDER = [ 'critical', 'high', 'medium', 'low' ];

/**
 * Requirements, in the order of the score.
 */
export const REQUIREMENT_ORDER = [ 'vuln', 'admin', 'data', 'malware' ];

/**
 * Returns the severity of a vulnerability record from its CVSS rating, as
 * the check takes it for V-01: a record without a rating counts as medium.
 *
 * @param {string} rating CVSS rating, or ''.
 * @return {string} Severity.
 */
export const ratingSeverity = ( rating ) => {
	switch ( rating ) {
		case 'Critical':
			return 'critical';
		case 'High':
			return 'high';
		case 'Low':
		case 'None':
			return 'low';
		default:
			return 'medium';
	}
};

/**
 * Returns the names of the four requirements of the Credit Card Security Guidelines.
 *
 * @return {Object<string, string>} Requirement => name.
 */
export const requirementLabels = () => ( {
	vuln: __( 'Vulnerability management', 'woocommerce-for-japan' ),
	admin: __( 'Admin access control', 'woocommerce-for-japan' ),
	data: __( 'Data directory exposure', 'woocommerce-for-japan' ),
	malware: __( 'Malware protection', 'woocommerce-for-japan' ),
} );

/**
 * Returns the severity labels.
 *
 * @return {Object<string, string>} Severity => label.
 */
export const severityLabels = () => ( {
	critical: _x(
		'Critical',
		'vulnerability severity',
		'woocommerce-for-japan'
	),
	high: _x( 'High', 'vulnerability severity', 'woocommerce-for-japan' ),
	medium: _x( 'Medium', 'vulnerability severity', 'woocommerce-for-japan' ),
	low: _x( 'Low', 'vulnerability severity', 'woocommerce-for-japan' ),
} );

/**
 * Returns what each check looks at and how to fix it when it fails.
 *
 * @return {Object<string, {title: string, fix: string}>} Check ID => labels.
 */
export const checkLabels = () => ( {
	'V-01': {
		title: __(
			'Known vulnerabilities in the installed software',
			'woocommerce-for-japan'
		),
		fix: __(
			'Update the affected plugins, themes or WordPress to a fixed version, or remove the ones that have no fix.',
			'woocommerce-for-japan'
		),
	},
	'V-02': {
		title: __(
			'Plugin updates left uninstalled for 180 days or more',
			'woocommerce-for-japan'
		),
		fix: __(
			'Update the plugins on the Plugins screen, and delete the plugins you no longer use.',
			'woocommerce-for-japan'
		),
	},
	'V-03': {
		title: __(
			'PHP, WordPress or WooCommerce version that is no longer supported',
			'woocommerce-for-japan'
		),
		fix: __(
			'Update WordPress and WooCommerce, and ask your hosting provider how to switch to a supported PHP version.',
			'woocommerce-for-japan'
		),
	},
	'V-04': {
		title: __(
			'Automatic updates of WordPress security releases and plugins',
			'woocommerce-for-japan'
		),
		fix: __(
			'Turn on automatic updates for every plugin on the Plugins screen, and do not turn off the automatic security updates of WordPress (WP_AUTO_UPDATE_CORE or a filter).',
			'woocommerce-for-japan'
		),
	},
	'A-01': {
		title: __( 'User named "admin"', 'woocommerce-for-japan' ),
		fix: __(
			'Create an administrator with another user name, then delete the admin user and attribute its content to the new one.',
			'woocommerce-for-japan'
		),
	},
	'A-02': {
		title: __( 'Login page protection', 'woocommerce-for-japan' ),
		fix: __(
			'Turn on the login attempt limiter under Settings on this tab, limit login attempts with a security plugin, or restrict wp-login.php to your IP addresses or a password in the server configuration.',
			'woocommerce-for-japan'
		),
	},
	'A-03': {
		title: __( 'Two-factor authentication', 'woocommerce-for-japan' ),
		fix: __(
			'Install a two-factor authentication plugin and turn it on for administrators.',
			'woocommerce-for-japan'
		),
	},
	'A-04': {
		title: __( 'XML-RPC open to anyone', 'woocommerce-for-japan' ),
		fix: __(
			'Turn on "Disable XML-RPC" under Settings on this tab: Jetpack keeps working, and the XML-RPC usage list shows what else still uses it so that you can allow those methods. Or block xmlrpc.php with a security plugin or the server configuration if nothing uses XML-RPC.',
			'woocommerce-for-japan'
		),
	},
	'A-05': {
		title: __( 'User list in the REST API', 'woocommerce-for-japan' ),
		fix: __(
			'Turn on "Hide the user list from the REST API" under Settings on this tab, or stop the users REST route (/wp-json/wp/v2/users) from listing users to visitors who are not logged in with a security plugin.',
			'woocommerce-for-japan'
		),
	},
	'D-01': {
		title: __(
			'File listing of the uploads directory',
			'woocommerce-for-japan'
		),
		fix: __(
			'Turn off directory listing in the server configuration (on Apache, add Options -Indexes to .htaccess).',
			'woocommerce-for-japan'
		),
	},
	'D-02': {
		title: __(
			'Backups, database dumps and logs in public directories',
			'woocommerce-for-japan'
		),
		fix: __(
			'Move or delete the backups, archives, database dumps and log files in the WordPress, wp-content and uploads directories, or block them in the server configuration.',
			'woocommerce-for-japan'
		),
	},
	'D-03': {
		title: __(
			'Error messages shown on the site',
			'woocommerce-for-japan'
		),
		fix: __(
			'Set WP_DEBUG_DISPLAY (or WP_DEBUG) to false in wp-config.php.',
			'woocommerce-for-japan'
		),
	},
	'D-04': {
		title: __(
			'WooCommerce log files that can be downloaded',
			'woocommerce-for-japan'
		),
		fix: __(
			'Block wp-content/uploads/wc-logs/ in the server configuration (nginx does not read the .htaccess file WooCommerce puts there).',
			'woocommerce-for-japan'
		),
	},
	'D-05': {
		title: __(
			'HTTPS on the checkout and My account pages',
			'woocommerce-for-japan'
		),
		fix: __(
			'Install an SSL certificate and change the WordPress Address and Site Address in Settings > General to https://.',
			'woocommerce-for-japan'
		),
	},
	'M-01': {
		title: __(
			'File editing in the admin screens',
			'woocommerce-for-japan'
		),
		fix: __(
			'Turn on "Forbid file editing in the admin screens" under Settings on this tab, or add define( \'DISALLOW_FILE_EDIT\', true ); to wp-config.php.',
			'woocommerce-for-japan'
		),
	},
	'M-02': {
		title: __(
			'Malware scanning or a web application firewall',
			'woocommerce-for-japan'
		),
		fix: __(
			'Install a security plugin that scans for malware or filters attacks.',
			'woocommerce-for-japan'
		),
	},
	'M-03': {
		title: __(
			'PHP files in the uploads directory',
			'woocommerce-for-japan'
		),
		fix: __(
			'Check the PHP files in the uploads directory and delete the ones you do not know, which may be malware. Also stop PHP from running there in the server configuration.',
			'woocommerce-for-japan'
		),
	},
} );
