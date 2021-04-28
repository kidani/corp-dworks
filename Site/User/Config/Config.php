<?php

/**
 *
 * ユーザー画面設定
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class UserConfig {

	// 設定
	public static $conf = array();

    /**
     * マスターページ情報
     */
    public $masterPage = array(
            'main'        => 'Master/Main'            // メイン
          , 'head'        => 'Master/Head'            // headタグ
          , 'header'      => 'Master/Header'          // ヘッダー
          , 'footer'      => 'Master/Footer'          // フッター
          , 'message'     => 'Master/Message'         // メッセージ
          , 'debug'       => 'Master/Debug'           // デバッグ
          , 'pager'       => 'Master/Pager'           // ページャ
          , 'crumb'       => 'Master/BreadCrumb'      // パンくずリスト
    );

	//------------------------------------------------
	// 可変項目
	//------------------------------------------------
    /**
     * コンストラクタ
     */
    public function __construct() {
		if (self::$conf) return;
    	// INI ファイル取得 	
		self::$conf = parse_ini_file("../Config/Config.ini", true);
		// 定型エラーメッセージ（showError 関数で表示）
		define('ERROR_MESSAGE_DEFAULT', self::$conf['Message']['errorMessage']);
    }

	/**
	 * テンプレート取得（Route利用版）
	 *
	 * ファイルの存在チェックは Route クラス側で行うため簡易化。
	 * $pfId（複数プラットフォーム用）には対応しない。
	 *
	 */
	public function getTemplateByRoute(&$route) {
		return array(
			'masterPage' => $this->masterPage,
			'pageMain'   => $route['html']
		);
	}

}
































































































