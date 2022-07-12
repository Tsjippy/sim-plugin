const path = require('path');

module.exports = {
    entry: {
		"vimeo_upload": './vimeo_upload.js',
	},
    mode: 'production',
    output: {
		path: path.resolve(__dirname, ''),
		filename: 'vimeo_upload.min.js',
		library: {
			name: 'vimeoUploader',
			type: 'umd',
		},
	},
    optimization: {
		usedExports: true,
    }
}

module.exports['devtool'] = 'source-map';