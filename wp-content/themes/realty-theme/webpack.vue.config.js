const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
    entry: './assets/vue_module/appModule.js',
    mode: 'production',
    resolve: {
        alias: {
            '@assets': path.resolve(__dirname, 'assets'),
            '@': path.resolve(__dirname, 'assets/vue_module'),
        },
        extensions: ['.js', '.vue', '.json'],
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                    },
                },
            },
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader',
                ],
            },
            {
                test: /\.(png|jpe?g|gif|svg)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]',
                },
            },
        ],
    },
    plugins: [
        new VueLoaderPlugin(),
    ],
    output: {
        filename: 'appVueBuild.js',
        path: path.resolve(__dirname, 'assets/vue_module/dish'),
        library: {
            name: 'VueAppModule',
            type: 'umd',
        },
        globalObject: 'this',
    },
    externals: {
        vue: 'Vue',
    },
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                terserOptions: {
                    compress: {
                        drop_console: true,
                    },
                    output: {
                        comments: false,
                    },
                },
                extractComments: false,
            }),
        ],
    },
};
