// 本番用
// CSS, JS のみ別ファイルにまとめる。（SCSSはCSS変換されている前提）
const MiniCssExtractPlugin  = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const TerserPlugin = require('terser-webpack-plugin');

const path = require('path');
module.exports = {
    mode: 'production',         // production／development
    entry: {
        all: './Site/User/Web/js/index_all.js',
    },
    output: {
        filename: '[name].js',
        path: path.join(__dirname, '/Site/User/Web/js/')
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            url: false,
                            sourceMap: false,
                        },
                    },
                ],
            }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({filename: '../css/[name].css'}),
    ],
    optimization: {
        minimizer: [new TerserPlugin({}), new OptimizeCSSAssetsPlugin({})],
    },
    // devtool: "source-map"
};
