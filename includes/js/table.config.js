const path = require('path');

module.exports = {
    entry: {
		"sim_table_functions": './table.js',
	},
    mode: 'production',
    output: {
		path: path.resolve(__dirname, ''),
		filename: 'table.min.js',
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