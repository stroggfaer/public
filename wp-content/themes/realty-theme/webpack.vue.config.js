const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const AutoImport = require('unplugin-auto-import/webpack');
const Components = require('unplugin-vue-components/webpack');
const { ElementPlusResolver } = require('unplugin-vue-components/resolvers');


module.exports = (env, argv) => {
    const isProd = argv.mode === 'production';
    return {
        entry: './assets/vue_module/appModule.js',
        mode: isProd ? 'production' : 'development',
        watch: true,
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
                        process.env.NODE_ENV === 'production'
                            ? MiniCssExtractPlugin.loader
                            : 'style-loader',
                        'css-loader',
                    ],
                },
                {
                    test: /\.scss$/,
                    use: [
                        process.env.NODE_ENV === 'production'
                            ? MiniCssExtractPlugin.loader
                            : 'style-loader',
                        'css-loader',
                        'sass-loader',
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
            AutoImport({
                resolvers: [ElementPlusResolver()],
                imports: ['vue', 'vuex'],
                dts: false,
            }),
            Components({
                resolvers: [ElementPlusResolver()],
                dts: false,
            }),
        ],
        //
        output: {
            filename: 'appVueBuild.js',
            path: path.resolve(__dirname, 'assets/vue_module/dish'),
            ...(isProd && {
                library: {
                    name: 'VueAppModule',
                    type: 'umd',
                },
                globalObject: 'this',
            }),
        },
        externals: isProd ? {
            vue: 'Vue',
        } : {},
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
}