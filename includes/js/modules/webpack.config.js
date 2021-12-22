module.exports = {
    mode: 'production',
    entry: './main.js',
    output: {
        filename: 'main.js',
        publicPath: 'dist'
    },
    optimization: {
        minimize: false
    },
}