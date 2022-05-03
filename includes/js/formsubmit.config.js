const path = require('path');

module.exports = {
    entry: {
		"formsubmit": './form_submit.js',
	},
    mode: 'production',
    output: {
		path: path.resolve(__dirname, ''),
		filename: '[name].min.js',
		library: {
			name: '[name]',
			type: 'umd',
		},
	},
    optimization: {
		usedExports: true,
    }
}

module.exports['devtool'] = 'source-map';