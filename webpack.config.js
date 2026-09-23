const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'build/index': './src/index.js',
        'build/theme': './src/styles/main.scss',
    },
    output: {
        path: __dirname,
        filename: '[name].js',
    },
};
