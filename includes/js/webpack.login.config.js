module.exports = {
    mode: 'production',
    entry: './login.js',
    output: {
        filename: 'login.js',
        publicPath: 'dist'
    },
    optimization: {
        minimize: false
    },
}