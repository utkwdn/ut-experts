const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'patterns-style': path.resolve(process.cwd(), 'src/patterns.scss'),
	},
};
