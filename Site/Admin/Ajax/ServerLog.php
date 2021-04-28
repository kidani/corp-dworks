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

$title = $param['title'];
$detail = $param['detail'];	 
$logfile = ($param['file'] && $param['file'] !== '') ? '../Tmp/' . $param['file'] : ERROR_LOG_FILE;
errorLog($detail, $title, $logfile);

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(response => 'success'));	
