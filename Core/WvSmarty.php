<?php

/**
 *
 * Smarty のラップクラス
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class WvSmarty extends Smarty {

	/**
	 *
	 * インスタンス保持
	 *
	 */
	public static $smarty = null;

	public function __construct($root) {
		if (!self::$smarty) {
			parent::__construct();

			$dirType = (WEB_ROOT === 'ADMIN') ? 'Admin' :'User';
			$this->template_dir = "{$root}/Site/{$dirType}/Template";				// テンプレート
			$this->compile_dir = "{$root}/Site/{$dirType}/Tmp/Smarty/Template_c";	// コンパイル
			$this->cache_dir = "{$root}/Site/{$dirType}/Tmp/Smarty/Cache";			// キャッシュ
			$this->config_dir = "{$root}/Site/{$dirType}/Tmp/Smarty/Configs";		// 設定ファイル
			// $smarty->plugins_dir[] = "{$root}/Lib/Smarty/plugins";  				// プラグイン

			$this->auto_literal = false;											// デリミタ間のスペース許可

			// エラーレベル
			// Smarty 3.0 ではデフォルトで未定義変数のエラー出力がされるようになったので「~E_NOTICE」で除外する。
			// $this->error_reporting = E_ERROR;
			$this->error_reporting = E_ALL & ~E_NOTICE;
			// $this->error_reporting = E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED;

			$this->left_delimiter = '%%';                                    		// 左デリミタ
			$this->right_delimiter = '%%';                                   		// 右デリミタ

			// 問題ないかチェック
			// $this->testInstall();

			self::$smarty = $this;
		}
	}
}

