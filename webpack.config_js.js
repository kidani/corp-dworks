/*!
 *
 * JS圧縮専用
 *
 * npx webpack --watch --config webpack.config_js.js
 *
 * webpack だと単純にまとめて圧縮してくれるのではなくファイルごとにモジュール化されてしまう。
 * common.js はグローバル変数・関数を多用しているため、webpack で圧縮すると main.js から参照できずエラーになる。
 * 単純にまとめて圧縮してくれるオプションとかありそうだが見つからず断念。
 *
 * common.js は単純な圧縮専用ツールで配置すること！
 * https://syncer.jp/js-minifier
 *
 * @author     : kidani@wd-valley.com
 * @copyright  : Wood Valley Co., Ltd.
 *
 */

const path = require('path');
module.exports = {
    mode: 'production',     // development／production
    entry: {
        // all   : "./Site/User/Web/js/all.js",     // 本来はここで複数ファイルを import する。
        main     : "./Site/User/Web/js/main.js",
    },
	output: {
        filename: '[name].min.js',
		path: path.join(__dirname, '/Site/User/Web/js/')
  	},
};
