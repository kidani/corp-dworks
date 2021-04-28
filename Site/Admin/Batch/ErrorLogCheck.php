<?php

/**
 * 
 * エラーログ監視
 * 
 * 10分毎にエラーログをチェックして、
 * 追加されたエラー出力があればメールで通知する。
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once ("../Config/BatchConfig.php");

define('INI_FILE', '../Config/BatchErrorLogCheck.ini');

// INIファイル取得
$ini = parse_ini_file(INI_FILE, true);

foreach ($ini as $key => $value) {

    if (!file_exists($key)) {
    	// エラーファイルなければ次へ
    	//errorLog($key, 'ファイルがありません。', BATCH_ERROR_LOG_FILE);
		continue;
    }

	// ファイルサイズ取得（バイト数）
	$curFileSize = filesize($key);

	// 読込対象ファイルを開く
	$handle = fopen($key, "r");

	// 出力開始位置の特定
	if ($curFileSize > $value['fileEnd']) {
		// 前回処理時のバイト数まで移動
		fseek($handle, $value['fileEnd']);
	} else {
		// ファイルがリセットされた可能性があるので、
		// ファイルの先頭から出力対象にする。
	}

	// 更新行の内容を取得
	$updateText = '';
	$updateLineCnt = 0;
	if($handle){
	  	while ($line = fgets($handle)) {
	  		$updateText .= $line;
			$updateLineCnt++;
	  	}
	}
	$curPos = ftell($handle);
	fclose($handle);

	if ($curPos != $value['fileEnd']) {
		// 更新ありの場合

		// アラートメール送信元
		//     Gmail で信元のドメインがサーバと相違すると「このメッセージは
		//     次のアドレスから送信されたものではない可能性があります。」の警告が表示される。
		$from = "From:{$SITE_CONFIG['Mail']['fromSite']}";
		
		// アラートメール送信先
		$mailTo = $SITE_CONFIG['Mail']['toSystemAlert'];
		
		$subject = DOMAIN . ' ' . $key;
		$message = $updateText;

		if (!mail($mailTo, $subject, $message ,$from)) {
			$errMessage = "アラートメール送信エラー\n
							BatchErrorLogCheck：[$key]";
			errorLog('mailTo : ' . $mailTo . 'subject : ' . $subject, 'メール送信エラー');
			return;
		}

		// INIファイルの書込み履歴を更新
		updateIniFile(INI_FILE, $key, 'time', date('Y-m-d H:i:s'));		// 更新時間
		updateIniFile(INI_FILE, $key, 'fileEnd', $curPos);				// ファイルエンドのバイト数
	}
	
	// イベントログ出力
	// eventLog(INI_FILE, 'BatchErrorLogCheck Finish', BATCH_EVENT_LOG_FILE);
}





















