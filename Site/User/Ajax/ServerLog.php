<?php

/**
 *
 * JSからのサーバログ出力
 *
 * ログフォーマット
 * 
 *   serverLog("パラメータエラー", "storyId を取得できません。", );
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

$title = isset($param['title']) ? $param['title'] : '';
$detail = isset($param['detail']) ? $param['detail'] : '';
$file = isset($param['file']) ? $param['file'] : '';
$detail = isset($param['detail']) ? $param['detail'] : '';
if (isJson($detail)) {	
	$detail = json_decode($param['detail']);	
}

$logfile = $file ? '../Tmp/' . $file : ERROR_LOG_FILE;
errorLog($detail, $title, $logfile);
header("Content-Type: application/json; charset=utf-8");
echo json_encode(array('response' => 'success'));	
