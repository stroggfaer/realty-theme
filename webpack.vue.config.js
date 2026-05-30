const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = (env, argv) => {
    const isProd = argv.mode === 'production';

    return {
        entry: './assets/vue_module/appModule.js',
        mode: isProd ? 'production' : 'development',

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
            minimize: isProd,
            minimizer: isProd
                ? [
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
                ]
                : [],
        },

        watchOptions: {
            ignored: /node_modules/,
            // poll: 1000,
            aggregateTimeout: 300,
        },

        devtool: isProd ? false : 'eval-source-map',
    };
};