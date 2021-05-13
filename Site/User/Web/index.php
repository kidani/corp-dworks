<?php

/**
 *
 * ユーザー画面エントリポイント
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// フレームワークルート
define('WVFW_ROOT', realpath(__DIR__ . '/../../..'));
// ユーザー画面からの実行
define('WEB_ROOT', 'USER');

//------------------------------------------------
// インクルード
//------------------------------------------------
// フレームワーク共通
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/share/pear');	// エックスサーバの場合必須
require_once 'HTTP/Request2.php';
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
require_once (WVFW_ROOT . '/Site/Core/Route.php');
require_once (WVFW_ROOT . '/Site/Core/User.php');
require_once (WVFW_ROOT . '/Site/Core/UserWeb.php');
require_once (WVFW_ROOT . '/Site/Core/News.php');
// ユーザー画面共通
require_once (WVFW_ROOT . '/Site/User/Config/Config.php');
require_once (WVFW_ROOT . '/Site/User/Core/Common.php');

// サイト設定情報取得
$siteConfig = new SiteConfig();
// ユーザー画面設定情報取得
$userConfig = new UserConfig();

if (WV_DEBUG) trace("index.php START");




//------------------------------------------------
// アクセス制御
//------------------------------------------------
// パラメータ、アクセス種別、アクセスページ取得
$argv = isset($argv) ? $argv : null;	// コマンドラインパラメータ
new Route($argv);
$route = Route::get();
//echo "<pre>";print_r ($route);echo "</pre>"; exit;
if (!$route['pageExist']) {
	if (WV_DEBUG) {
		echo "<pre>";print_r (array($_SERVER, "アクセス対象ファイルなし検知"));echo "</pre>";
		exit;
	} else {
		// ボットアクセスが頻発
		// showError(setLogDetail($_SERVER['HTTP_USER_AGENT'], $route), 'アクセス対象ファイルなし検知');
		header("HTTP/1.0 404 Not Found");
		exit;
	}
}
$param = $route['param'];
$paramOrg = $route['paramOrg'];
if (WV_DEBUG) debug($paramOrg, 'index.php - $paramOrg');
$pageName = $route['pageName'];
define('ACCESS', $route['access']);

// PHPエラー出力制御
if (WV_DEBUG) {
	// PHPワーニング発生時にAJAXでエラーにならないよう出力抑止
	set_error_handler('showPhpError');
	if (ACCESS === 'USER') {
		// ◆SERVER, AJAX ではデバッグが困難になるので使わないこと！
		register_shutdown_function('showPhpFatalError');
	}
}

// 不正アクセス拒否
if (isset($_SERVER['HTTP_USER_AGENT'])) {
	if (checkCrawler($_SERVER['HTTP_USER_AGENT']) === 'forbidden') {
		// errorLog($_SERVER['HTTP_USER_AGENT'], '不正アクセス');
		header("HTTP/1.0 404 Not Found");
		exit;
	}
}

// 開発以外でのデバッグページアクセス拒否
if (!WV_DEBUG && strstr($pageName, 'Debug')) {
	errorLog($_SERVER['HTTP_USER_AGENT'], '本番でのデバッグページアクセス検知');
	header("HTTP/1.0 404 Not Found");
	exit;
}

//------------------------------------------------
// プラットフォーム（web／android／ios）
//------------------------------------------------
session_start();
$PF = User::getPfType();
define('PF', $PF);

// サーバ通信、バッチの場合はここで処理して終了
if (ACCESS === 'SERVER' || ACCESS === 'BATCH') {
	include($route['php']);
	exit;
}

//------------------------------------------------
// ユーザー情報
//------------------------------------------------
// $user = new User();
global $user;
if ($PF === 'ios') {
	define('NATIVE', true);
	$user = new UserIos();
} elseif ($PF === 'android') {
	define('NATIVE', true);
	$user = new UserAnd();
} else {
	define('NATIVE', false);
	$user = new UserWeb();
}

if (ACCESS === 'USER' || ACCESS === 'AJAX') {
	// ユーザー初期化
	// リダイレクトが2箇所あるので注意
	//     ネイティブでのセッション切れでUUIDが取得できなかった場合
	//     showAppReviewCount が発火してアプリ内レビュー誘導ダイアログを表示する場合
	$user->initUser($param, $pageName);
}

// ネイティブの場合
if (NATIVE && isset(User::$userInfo['status']) && User::$userInfo['status'] === 'インストール') {
	// Users 側か UsersTemp 側かのフラグセット
	define('USERS_TEMP', true);
} else {
	define('USERS_TEMP', false);
}

// デバッグ用ユーザー登録
if (isset(SiteConfig::$conf['Debug']['userId'])
	&& in_array(User::$userId, SiteConfig::$conf['Debug']['userId'])) {
	define('DEBUG_USER', true);
} else {
	define('DEBUG_USER', false);
}
if (DEBUG_USER || DEBUG_IP) {
	// エラー制御上書き（既に Config.php で設定済み）
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

// デバッグモード確定
// WV_DEBUG の値自体は後から変更できないので、本番で debug.log を出力させる場合は、
// 一時的に if (WV_DEBUG) を削除すること。
$debugFlg = intval((DEBUG_USER || DEBUG_IP) ? 1 : WV_DEBUG);

// システム状態
$SYSTEM_STATUS = SiteConfig::$conf['System']['status'];
if ($SYSTEM_STATUS === 'メンテナンス中' && !DEBUG_USER && !DEBUG_IP) {
	$pageName = 'Error/Maintenance';
}

//------------------------------------------------
// 各種初期設定
//------------------------------------------------
// デバイス別テンプレート切替閾値
$DEVICE_SCREEN_CHG_SIZE = SiteConfig::$conf['Other']['deviceScreenChgSize'];
// 画面サイズ取得（normal／small）
if (ACCESS === 'USER') {
	// デバイス情報取得
	// 	   [type] => Mobile
	// 	   [ua] => Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_4 like Mac OS X) ...
	// 	   [available] => 1
	// 	   [os] => iOS
	// 	   [products] => iPhone
	// 	   [osVersion] => 9.3.4		// PCの場合はキー自体ない！
	$DEVICE = getDevice();
	// 画面サイズ取得
	$scrSize = isset($param['scr']) ? $param['scr'] : null;
	$deviceType = isset($DEVICE['type']) ? $DEVICE['type'] : null;
	$SCREEN_SIZE = getScreenSize($scrSize, $deviceType);
}
if (isset($SCREEN_SIZE)) {
	define('SCREEN_SIZE', $SCREEN_SIZE);
} else {
	define('SCREEN_SIZE', 'normal');
}

// ヘッダー固定
// スマホとネイティブについてはヘッダー固定する。
if (SCREEN_SIZE === 'small' || NATIVE) {
	define('FIX_HEADER', true);
} else {
	define('FIX_HEADER', false);
}

//------------------------------------------------
// 実行ファイル・テンプレート割り当て
//------------------------------------------------
if (ACCESS === 'AJAX') {
	//------------------------------------------------
	// Ajax通信の場合
	//------------------------------------------------
	include($route['php']);
	exit;
} else {
	//------------------------------------------------
	// 通常遷移の場合
	//------------------------------------------------
	// POST後画面からのブラウザバック有効化
	header('Expires: -1');
	header('Cache-Control:');
	header('Pragma:');

	// 強制遷移の制御
	if (!User::$userInfo['userLogin']) {
		//------------------------------------------------
		// 未ログイン時の強制遷移
		//------------------------------------------------
	 	// 閲覧禁止ページ
		//$forbiddenPage = array(
		//	'Contact/Alert',
		//	'Contact/Request',
		//);
		//if (in_array($pageName, $forbiddenPage)) {
		//	// 戻りURLを保持
		//	//     未ログインの場合、User::$userId は仮ユーザーID。
		//	$user->saveBackUrl(User::$userId, $param);
		//	if (!strstr($pageName, 'Error/')) {
		//		// エラーページ以外はログイン画面へ
		//		$pageName = 'Login';
		//	}
		//}
	} else {
		// ログイン済みの場合
		//------------------------------------------------
		// ニックネーム未登録時の強制遷移
		//------------------------------------------------
		//if (!(isset(User::$userInfo['nickname']) && User::$userInfo['nickname'])) {
		//	// ニックネーム登録なしの場合
		//
		//	// 閲覧可能ページ
		//	$okPage = array(
		//		  'Regist/Base'
		//		, 'Regist/BaseResult'
		//		, 'Debug/Debug'
		//	);
		//	if (!in_array($pageName, $okPage)) {
		//		// 戻りURLを保持
		//		$user->saveBackUrl(User::$userId, $param);
		//		// ニックネーム登録画面へ
		//		$pageName = 'Regist/Base';
		//	}
		//}
	}

	// 	メンテナンスにする場合
	//if (PF === 'android') {
	//	$pageName = 'Error/Maintenance';
	//}

	// テンプレート設定取得
	$template = $userConfig->getTemplateByRoute($route);

	// メインページのテンプレート確定
	define('PAGE_MAIN_PATH', $template['pageMain']);

	// Smarty 初期化
	new WvSmarty(WVFW_ROOT);

	// メインページ用PHP読込み
	// テンプレートだけの場合は読み込まない
	if (file_exists($route['php'])) {
		include($route['php']);
	}

	// メインページのテンプレート設定
	WvSmarty::$smarty->assign('pageName', $pageName);                         			// ページ名（ファイル名から拡張子除いたもの）
	WvSmarty::$smarty->assign('PAGE_MAIN_PATH', PAGE_MAIN_PATH);      					// パス

	// システム情報
	WvSmarty::$smarty->assign('masterPage', $template['masterPage']);					// マスターテンプレート
	WvSmarty::$smarty->assign('MASTER_MESSAGE', $MASTER_MESSAGE);						// エラーメッセージ制御用データ
	WvSmarty::$smarty->assign('SCREEN_SIZE', $SCREEN_SIZE);					    		// スクリーンサイズ（normal／small）
	WvSmarty::$smarty->assign('ENV_ID', ENV_ID);                              			//

	// PF情報
	WvSmarty::$smarty->assign('PF', PF);                              					// PF
	WvSmarty::$smarty->assign('NATIVE', NATIVE);                              			// NATIVE
	WvSmarty::$smarty->assign('FIX_HEADER', FIX_HEADER);                              	// 固定ヘッダー

	// サイト・アプリ情報
	WvSmarty::$smarty->assign('SITE_NAME', SITE_NAME);                            		// サイト名
	WvSmarty::$smarty->assign('SITE_NAME_EN', SiteConfig::$conf['Base']['SITE_NAME_EN']);
	WvSmarty::$smarty->assign('COPYRIGHT', SiteConfig::$conf['Base']['COPYRIGHT']); 	// コピーライト
	WvSmarty::$smarty->assign('DOMAIN', DOMAIN);                              			// ドメイン
	WvSmarty::$smarty->assign('SITE_URL', SITE_URL);                              		// サイトURL
	WvSmarty::$smarty->assign('SITE_CATCH', SiteConfig::$conf['Base']['SITE_CATCH']);

	// ユーザー情報
	WvSmarty::$smarty->assign('USER_ID', User::$userId);								// 未ログイン時は仮ID
	WvSmarty::$smarty->assign('USER_LOGIN', User::$userInfo['userLogin']);				// ログイン状態
	if (isset(User::$userInfo)) {
		WvSmarty::$smarty->assign('USER_INFO', User::$userInfo);
		WvSmarty::$smarty->assign('LANG', 'Jp');
	}

	// テンプレートのデバッグモード制御
	//     デバッグユーザーの場合、本番でもデバッグ表示させるための処理。
	//     PHP側とテンプレート側で WV_DEBUG の値が相違する場合がある点に注意！
	WvSmarty::$smarty->assign('WV_DEBUG', $debugFlg);									// デバッグモード
	WvSmarty::$smarty->assign('SYSTEM_STATUS', $SYSTEM_STATUS);							// システム状態

	// 運営会社
	WvSmarty::$smarty->assign('COMPANY', SiteConfig::$conf['Company']);

	// その他
	// WvSmarty::$smarty->assign('ETAX', ETAX);                   					 	// 消費税
	WvSmarty::$smarty->assign('VERSION_UP', SiteConfig::$conf['VersionUp']);  			// バージョンアップ

	/**
	 * JS連携パラメータ設定
	 */
	$paramJs = array(
		'wvDebug' 		=> intval($debugFlg),											// デバッグモード
		'pageName' 		=> $pageName,													// ページ名（リダイレクトの場合、URLで識別できないので渡す。）
		'query' 		=> $paramOrg,													// クエリ（GET、POSTパラメータ）
		'scr'  			=> $SCREEN_SIZE,												// スクリーンサイズ
		'scrChgSize'  	=> $DEVICE_SCREEN_CHG_SIZE,										// デバイス別テンプレート切替閾値
		'userLogin'  	=> User::$userInfo['userLogin'],								// ログイン状態
		'native'  		=> NATIVE,														// ネイティブ
		'fixHeader'  	=> FIX_HEADER,													// ヘッダの固定
	);
	$paramJs = json_encode($paramJs);
	WvSmarty::$smarty->assign('paramJs', "<script>var paramJs = $paramJs;</script>");

	// 全変数出力
	// Smarty 3 からそうなのかもしれないが、$smarty->_tpl_vars では取得できない？
	// echo "<pre>";print_r (WvSmarty::$smarty->tpl_vars);echo "</pre>"; exit;

	// マスターテンプレート読込み
	if ($template['pageMain']) {
		// Smarty3 から tyr～catch の実装が必須になった。
		try {
			WvSmarty::$smarty->display("{$template['masterPage']['main']}.htm");
		} catch (Exception $e) {
			if (WV_DEBUG) {
				// ブラウザに直接表示
				echo "<pre>";print_r ($e->getMessage());echo "</pre>";
			} else {
				showError($e->getMessage());
			}
		}
	}
}

// デバッグログの改行追加
if (WV_DEBUG) writeLine();
