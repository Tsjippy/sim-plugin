module.exports = {
    mode: 'production',
    entry: './register_fingerprint.js',
    output: {
        filename: 'main.js',
        publicPath: 'dist'
    },
    optimization: {
        minimize: false
    },
}