const path = require('path');

module.exports = {
    entry: {
		"main": './main.js',
		"fileupload": './fileupload.js',
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

//if (process.env.NODE_ENV !== 'production') {
	module.exports['devtool'] = 'source-map';
//}