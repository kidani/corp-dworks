<?php

/**
 * 
 * フレームワーク共通の設定
 * 
 */

// デフォルトタイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// デバッグ出力用ファイルパス
$wvfwDir = (WEB_ROOT === 'USER') ? 'User' : 'Admin';
define('DEBUG_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/debug.log");
define('ERROR_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/error.log");
define('EVENT_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/event.log");
define('CHECK_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/check.log");
define('BATCH_EVENT_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/BatchEvent.log");
define('BATCH_ERROR_LOG_FILE', WVFW_ROOT . "/Site/{$wvfwDir}/Tmp/BatchError.log");

/**
 *
 * 共通メッセージ
 *
 * 以下の関数により設定して Master/Message.htm でメッセージを出力する。
 *
 * showInfo($message);
 *     $MASTER_MESSAGE['message']['info'] にエラーメッセージを設定。
 * showWarning($message);
 *     $MASTER_MESSAGE['message']['warning'] にエラーメッセージを設定。
 * showError($message);
 *     $MASTER_MESSAGE['message']['error'] にエラーメッセージを設定。
 *     $MASTER_MESSAGE['message']['debug'] にデバッグメッセージを設定。
 * 
 */
$MASTER_MESSAGE = array(
	// 表示するメッセージ配列
	'message' => array(
		'info' 		=> array(),
		'warning' 	=> array(),
		'error' 	=> array(),
		'debug' 	=> array(),
		'dialog' 	=> array()
	),
	// 戻るボタンのリンク先（Backの場合：onClick="history.back()"）
	'linkPage' => 'Back'
);

















