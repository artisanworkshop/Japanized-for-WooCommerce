const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
module.exports = {
	...defaultConfig,
	entry: {
		'admin/security': '/src/js/jp4wc/admin/security/index.js',
//		'admin/settings': '/src/js/jp4wc/admin/settings/index.js',
		'frontend/blocks/atstore': '/src/js/jp4wc/frontend/blocks/atstore/index.js',
		'frontend/blocks/bank-jp': '/src/js/jp4wc/frontend/blocks/bank-jp/index.js',
		'frontend/blocks/postofficebank': '/src/js/jp4wc/frontend/blocks/postofficebank/index.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js/build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	]
};
