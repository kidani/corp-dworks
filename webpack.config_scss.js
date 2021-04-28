// Sass コンパイル専用
// SCSS → CSS への変換のみに使用する。
const MiniCssExtractPlugin  = require('mini-css-extract-plugin');
// const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
// const TerserPlugin = require('terser-webpack-plugin');
const path = require('path');
module.exports = {
    mode: 'development',         // production／development
    entry: {
        custom      : "./Site/User/Web/scss/bootstrap-custom.scss",
        main        : "./Site/User/Web/scss/main.scss",
    },
    output: {
        filename: '[name].css',
        path: path.join(__dirname, '/Site/User/Web/css/')
    },
    module: {
        rules: [
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            url: false,
                            sourceMap: true,
                        },
                    },
                    {
                        loader: "postcss-loader",
                        options: {
                            plugins: function () {
                                return [
                                    require('precss'),
                                    require('autoprefixer')
                                ];
                            }
                        }
                    },
                    {
                        loader: 'sass-loader',
                        options: {
                            sourceMap: true,
                        }
                    },
                ],
            }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({filename: './[name].css'}),
    ],
    // optimization: {
    //     minimizer: [new TerserPlugin({}), new OptimizeCSSAssetsPlugin({})],
    // },
    devtool: "source-map"
};

