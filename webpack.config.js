const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
module.exports = {
    entry: './assets/sass/main.scss',
    mode: 'production',// Минификация включена автоматически в этом режиме
    resolve: {
        alias: {
            '@assets': path.resolve(__dirname, 'assets'), // Указание пути к папке assets
        },
    },
    module: {
        rules: [
            {
                test: /\.(png|jpe?g|gif|svg)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]', // Путь для выходных файлов (в папку dist/img)
                },
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
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: "css-loader",
                        options: {
                            sourceMap: true
                        }
                    },
                    {
                        loader: 'postcss-loader',
                        options: {
                            postcssOptions: {
                                plugins: [
                                    require('autoprefixer'),
                                ],
                            },
                        },
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            sourceMap: true,
                            sassOptions: {
                                quietDeps: true, // Подавляет предупреждения
                            },
                        },
                    },
                ],
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/main.min.css', // указываем имя выходного CSS файла
        }),
    ],
    output: {
        filename: 'js/main.min.js', // указываем имя выходного JS файла
        path: path.resolve(__dirname, 'dist'), // указываем путь к каталогу выходных файлов
    },
};
