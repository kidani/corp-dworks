<?php

/**
 *
 * 管理画面設定
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

 class AdminConfig {

	 // 設定
	 public static $conf = array();

    /**
     * コンストラクタ
     */
    public function __construct() {
		if (self::$conf) return;
    	// INI ファイル取得
		self::$conf = parse_ini_file("../Config/Config.ini", true);
		// 定型エラーメッセージ（showError 関数で表示）
		define('ERROR_MESSAGE_DEFAULT', self::$conf['Message']['errorMessage']);
		// 管理画面用コピーライト
		define('COPYRIGHT_ADMIN', self::$conf['Base']['copyright']);
    }
}


