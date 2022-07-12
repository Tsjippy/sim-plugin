const path = require('path');

module.exports = {
    entry: {
		"forms": './forms.js',
	},
    mode: 'production',
    output: {
		path: path.resolve(__dirname, ''),
		filename: 'forms.min.js',
		library: {
			name: 'FormFunctions',
			type: 'umd',
		},
	},
    optimization: {
		usedExports: true,
    }
}

//if (process.env.NODE_ENV !== 'production') {
	module.exports['devtool'] = 'source-map';
//}