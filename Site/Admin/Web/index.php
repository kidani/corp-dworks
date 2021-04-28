<?php

/**
 *
 * 管理画面WEBルート
 *
 * パラメータに応じて処理するPHP、テンプレートを調節するなど
 * コントローラー的に役割も兼ねる。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// フレームワークルート
define('WVFW_ROOT', realpath(__DIR__ . '/../../..'));

// 管理画面からの実行
define('WEB_ROOT', 'ADMIN');

// フレームワーク共通
require_once 'HTTP/Request2.php';
require_once (WVFW_ROOT . '/Lib/Scraping/phpQuery-onefile.php');
require_once (WVFW_ROOT . '/Lib/Smarty/Smarty.class.php');
require_once (WVFW_ROOT . '/Core/WvSmarty.php');
require_once (WVFW_ROOT . '/Config/Config.php');
require_once (WVFW_ROOT . '/Core/Common.php');
require_once (WVFW_ROOT . '/Core/Db.php');
require_once (WVFW_ROOT . '/Core/Log.php');
require_once (WVFW_ROOT . '/Core/Mail.php');
require_once (WVFW_ROOT . '/Core/Validation.php');
// ユーザー・管理画面共通
require_once (WVFW_ROOT . '/Site/Config/SiteConfig.php');
require_once (WVFW_ROOT . '/Site/Core/Common.php');
require_once (WVFW_ROOT . '/Site/Core/Batch.php');
require_once (WVFW_ROOT . '/Site/Core/User.php');
//require_once (WVFW_ROOT . '/Site/Core/UserIos.php');
//require_once (WVFW_ROOT . '/Site/Core/UserAnd.php');
//require_once (WVFW_ROOT . '/Site/Core/Push.php');
//require_once (WVFW_ROOT . '/Site/Core/NativeNotify.php');
require_once (WVFW_ROOT . '/Site/Core/News.php');
require_once (WVFW_ROOT . '/Site/Core/SendMail.php');
// 管理画面共通
require_once (WVFW_ROOT . '/Site/Admin/Config/Config.php');
require_once (WVFW_ROOT . '/Site/Admin/Core/Common.php');
require_once (WVFW_ROOT . '/Site/Admin/Core/AdminUser.php');
//require_once (WVFW_ROOT . '/Site/Admin/Core/Report.php');

// サイト設定情報取得
$siteConfig = new SiteConfig();
// ユーザー画面設定情報取得
$adminConfig = new AdminConfig();

//------------------------------------------------
// アクセス制御
//------------------------------------------------
// HTTPパラメータ
$param = getHttpQuery();

if (WV_DEBUG) debug($param, 'index.php - $param');
// コマドライン実行の場合（バッチ処理）
if (isset($argv)) {
	$param = formatCommadLineArgv($argv);
}

// ページ識別子設定
$pageName = isset($param['p']) ? $param['p'] : null;

// アクセス元識別情報
$ACCESS = 'USER';							// アクセス元種別：ユーザー
if (strstr($pageName, 'Batch/')) {
	$ACCESS = 'BATCH';						// アクセス元種別：バッチ処理
} elseif ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
 	|| ENV_ID !== 'Prd' && isset($param['ajaxTest'])) {
	$ACCESS = 'AJAX';	
}
define('ACCESS', $ACCESS);

// 管理画面設定
session_start();
$ADMIN_CONFIG = new AdminConfig();

// 管理ユーザー
$adminUser = new AdminUser();
$ADMIN_USER = $adminUser->initAdminUser();

if (WV_DEBUG) {
	// PHPエラー制御
	// set_error_handler('showPhpError');
	if (ACCESS === 'AJAX') {
		set_error_handler('showPhpError');
		// ◆SERVER, AJAX ではデバッグが困難になるので使わない方がいい？
		register_shutdown_function('showPhpFatalError');
	}
}

// デバイス別テンプレート切替閾値
$DEVICE_SCREEN_CHG_SIZE = SiteConfig::$conf['Other']['deviceScreenChgSize'];
$SCREEN_SIZE = 'normal';
if ($ACCESS === 'USER') {
	$DEVICE = getDevice(AdminConfig::$conf['WebOsVersion']);
	// 非対応端末ならエラーページへ
	if (!$DEVICE['available']) {
		// $pageName = 'Error/Device';
	}
	// 画面サイズ取得
	$scrSize = isset($param['scr']) ? $param['scr'] : null;
	$deviceType = isset($DEVICE['type']) ? $DEVICE['type'] : null;
	if ($deviceType === 'Mobile') {
		// ユーザー画面と違い、モバイルなら固定
		// セッション管理はしない点に注意！
		$SCREEN_SIZE = 'small';	
	}
}

//------------------------------------------------
// 実行ファイル・テンプレート割り当て
//------------------------------------------------
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
 	|| WV_DEBUG && isset($param['ajaxTest'])) {
	//------------------------------------------------
	// Ajax通信の場合
	//------------------------------------------------
	// ページ名確定
	if (!isset($_SESSION['adminId'])) {
		// セッション情報なければログイン画面へ
		errorLog($_SESSION, '未ログインでのAjax通信は無効です。');
	}

	$phpFile = '../Ajax/' . $pageName . '.php';
    if (file_exists($phpFile)) {
        include($phpFile);
       exit;
    } else {
        showError(setLogDetail($phpFile), 'PHPファイルが存在しません。', false);
       exit;
    }
} elseif (isset($argv) && preg_match('|Batch/|', $pageName)) {   // 念のためブラウザ実行は除外
	//------------------------------------------------
	// バッチ処理の場合
	//------------------------------------------------
	$phpFile = "../{$pageName}.php";
    if (file_exists($phpFile)) {
        include($phpFile);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        showError(setLogDetail($phpFile), 'PHPファイルが存在しません。');
        exit;
    }
} else {
	//------------------------------------------------
	// 通常遷移の場合
	//------------------------------------------------
	// ページ名確定
	if (!isset($_SESSION['adminId'])) {
		if ($pageName !== 'System/Login') {
			// 戻りURLを保持
			$adminUser->saveBackUrl();
		}
		// セッション情報なければログイン画面へ
		$pageName = 'System/Login';
	} else {
		// ページ識別子なければTOPへ
		if (!$pageName) $pageName = 'Top';
	}
	$pageMainPath = WVFW_ROOT . "/Site/Admin/Template/{$pageName}.htm";
	if (!file_exists($pageMainPath)) {
		// テンプレート不要なページ
		$okPage = array(
			'System/Logout',
		);
		if (!in_array($pageName, $okPage)) {
			if (WV_DEBUG) {
				showError($pageMainPath, 'テンプレートファイルなし');
				exit;
			} else {
				header("HTTP/1.0 404 Not Found");
				exit;
			}
		}
	}
	// メインテンプレート確定
	define('PAGE_MAIN_PATH', $pageMainPath);

	// Smarty 初期化
	new WvSmarty(WVFW_ROOT);

	// PHPインクルード
	$phpFile = "../Page/".$pageName.".php";
	if (file_exists($phpFile)) {
	    include($phpFile);
	}

	// デバッグ情報
	if (WV_DEBUG) {
		WvSmarty::$smarty->assign('WV_DEBUG', WV_DEBUG);
	}

	// テンプレート変数
	WvSmarty::$smarty->assign('ADMIN_CONFIG', AdminConfig::$conf);	    				// 管理画面設定
	WvSmarty::$smarty->assign('ADMIN_USER', $ADMIN_USER);	    						// 管理ユーザー情報
	WvSmarty::$smarty->assign('SITE_NAME', SITE_NAME);                    				// サイト名
	WvSmarty::$smarty->assign('COPYRIGHT', SiteConfig::$conf['Base']['COPYRIGHT']);   	// コピーライト記載名
	WvSmarty::$smarty->assign('SITE_CATCH', SiteConfig::$conf['Base']['SITE_CATCH']);	// キャッチ
	WvSmarty::$smarty->assign('DOMAIN', DOMAIN);                          				// ドメイン
	WvSmarty::$smarty->assign('SITE_URL', SITE_URL);                      				// ユーザー画面URL
	WvSmarty::$smarty->assign('SITE_URL_ADMIN', SiteConfig::$conf['SITE_URL_ADMIN']);	// 管理画面URL
	WvSmarty::$smarty->assign('ENV_ID', ENV_ID);										// 環境（Dev/Prd）
	WvSmarty::$smarty->assign('MASTER_MESSAGE', $MASTER_MESSAGE);						// エラーメッセージ制御用データ
	WvSmarty::$smarty->assign('pageName', $pageName);                     				// メインページ名（ファイル名から拡張子除いたもの）
	WvSmarty::$smarty->assign('PAGE_MAIN_PATH', PAGE_MAIN_PATH);      					// メインページファイルパス
	WvSmarty::$smarty->assign('SCREEN_SIZE', $SCREEN_SIZE);								// スクリーンサイズ（normal／small）
	// WvSmarty::$smarty->assign('API', SiteConfig::$conf['Api']);  					// API
	// ログイン情報
	if (!isset($_SESSION['adminId'])) {
		WvSmarty::$smarty->assign('USER_LOGIN', false);
	} else {
		WvSmarty::$smarty->assign('USER_LOGIN', true);
	}

	/**
	 * JS連携パラメータ設定
	 */
	$paramJs = array(
		  'mode' 		=> WV_DEBUG														// デバッグモード
		, 'scr'  		=> $SCREEN_SIZE													// スクリーンサイズ
		, 'scrChgSize'  => $DEVICE_SCREEN_CHG_SIZE										// デバイス別テンプレート切替閾値
	); 
	$paramJs = json_encode($paramJs);
	WvSmarty::$smarty->assign('paramJs', "<script>var paramJs = $paramJs;</script>");  	// JS連携パラメータ

	// マスターテンプレート読込み
	try {
		WvSmarty::$smarty->display('Master/Main.htm');
	} catch (Exception $e) {
		if (WV_DEBUG) {
			// ブラウザに直接表示
			echo "<pre>";print_r ($e->getMessage());echo "</pre>";
		} else {
			showError($e->getMessage());
		}
	}
}

// デバッグログの改行追加
if (WV_DEBUG) writeLine();
