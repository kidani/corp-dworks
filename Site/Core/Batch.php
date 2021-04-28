<?php
    
/**
 *
 * バッチ処理
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Batch {

	/**
	 *
	 * エラーログチェック
	 *
	 * エラーログ管理ファイル（Site/Admin/Config/BatchErrorLogCheck.ini）
	 * に記載された内容に応じて、各種エラーログ（error.log、BatchError.log など）
	 * をチェックして、追加されたエラー出力があればメールで通知する。
	 * チェック後に更新履歴をエラーログ管理ファイルに保存。
	 *
	 */
	public function errorLogCheck($iniFilePath) {
		$ini = parse_ini_file($iniFilePath, true);
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
				//     Gmail で送信元のドメインがサーバと相違すると
				//    「このメッセージは次のアドレスから送信されたものではない可能性があります。」の警告が表示される。
				$mailFrom = SiteConfig::$conf['Mail']['fromSite'];
				$from = "From:{$mailFrom}";
				// アラートメール送信先
				$mailTo = SiteConfig::$conf['Mail']['toSystemAlert'];
				$subject = DOMAIN . ' ' . $key;
				$message = $updateText;
				if (!mail($mailTo, $subject, $message ,$from)) {
					errorLog("mailFrom:{$mailFrom} mailTo:{$mailTo} subject:{$subject} message:{$message}", 'メール送信エラー');
					return;
				}

				// INIファイルの書込み履歴を更新
				updateIniFile($iniFilePath, $key, 'time', date('Y-m-d H:i:s'));		// 更新時間
				updateIniFile($iniFilePath, $key, 'fileEnd', $curPos);				// ファイルエンドのバイト数
			}
			// イベントログ出力
			// eventLog($iniFilePath, 'エラーログチェック完了', BATCH_EVENT_LOG_FILE);
		}
	}

	/**
	 *
	 * 死活監視
	 *
	 * ping, http の死活を監視する。
	 *
	 */
	public function checkAlive() {
		// 監視対象サーバ取得
		$server = new Server();
		$dataServer = $server->getActive();
		$resultStr = null;
		foreach($dataServer as $key => $value) {
			//------------------------------------------------
			// PING死活チェック
			//------------------------------------------------
			if ($value['ip']) {
				//     ＜出力例＞
				//	   PING 160.16.77.238 (160.16.77.238): 56 data bytes
				//	   64 bytes from 160.16.77.238: icmp_seq=0 ttl=63 time=0.848 ms
				//	   64 bytes from 160.16.77.238: icmp_seq=1 ttl=63 time=0.584 ms
				//	   64 bytes from 160.16.77.238: icmp_seq=2 ttl=63 time=0.493 ms
				//
				//	   --- 160.16.77.238 ping statistics ---
				//	   3 packets transmitted, 3 packets received, 0.0% packet loss
				//	   round-trip min/avg/max/stddev = 0.493/0.642/0.848/0.151 ms
				// 試行回数3回、タイムアウト5秒で送信
				// $command = "/bin/ping -c 3 -w 5 {$value['ip']}";				// CentOSの場合（さくらVPS）
				// $command = "/sbin/ping -c 3 -t 5 {$value['ip']}";			// FreeBSD（さくらレンタルサーバ）
				$command = "/bin/ping -c 3 -w 5 {$value['ip']}";
				$response = shell_exec($command);
				// パーセント部分のみ抽出
				$lossPercent = preg_replace("/.*received, ([0-9.]+)%.*/", "$1" , $response);
				$lossPercen = intval($lossPercent);
				if ($lossPercent <= 80) {
					// パケットロス80%以下は正常とみなす。
					// $resultStr .= "{$value['name']} ping OK\n";
				} else {
					// 81%以上は異常値とみなす。
					$resultStr .= "{$value['name']} ping NG\n{$response}\n";
				}
			}
			//------------------------------------------------
			// HTTP死活チェック
			//------------------------------------------------
			if ($value['checkUrl']) {
				if ($res = file_get_contents($value['checkUrl'])) {
					// 正常の場合「I am alive!」が返る。
					// $resultStr .= "{$value['name']} http OK\n";
				} else {
					// エラーありの場合
					$resultStr .= "{$value['name']} http NG\n";
				}
			}
		}
		// 異常ありならアラートメール送信
		if ($resultStr) {
			$mailFrom = SiteConfig::$conf['Mail']['fromSite'];
			$from = "From:{$mailFrom}";
			$mailTo = SiteConfig::$conf['Mail']['toSystemAlert'];
			$subject = 'Check Alive Result';
			if (!mail($mailTo, $subject, $resultStr ,$from)) {
				errorLog("mailFrom:{$mailFrom} mailTo:{$mailTo} subject:{$subject} message:{$resultStr}", 'メール送信エラー');
			}
		}
		// イベントログ出力
		// eventLog($iniFilePath, '死活監視チェック完了', BATCH_EVENT_LOG_FILE);
	}



	/**
	 *
	 * ログローテート
	 *
	 * ファイル保存数を超過、かつ、ファイル保存期間を
	 * 超過している場合にのみローテーションを実行する。
	 *
	 * $type		：ローテーションの種別
	 * $bkupDir		：バックアップファイル配置ディレクトリ
	 * $matchKey	：バックアップファイル確定のキーワード
	 * $limitCount	：保存数の上限
	 * $limitTime	：保存期間の上限
	 *
	 */
	public function logRotate($type, $bkupDir, $matchKey, $limitCount, $limitTime) {

		if (!file_exists($bkupDir)) {
			errorLog($bkupDir, "指定ディレクトリなし" , BATCH_ERROR_LOG_FILE);
			return false;
		}

		// 削除対象ファイル数を取得
		//     環境に依存しないコマンドを使うこと。
		$command = "ls $bkupDir | grep $matchKey | wc -l";
		$listCount = exec($command, $output, $return_var);
		if ($return_var !== 0 || count($output) <= 0) {
			errorLog($command, "コマンド実行エラー" , BATCH_ERROR_LOG_FILE);
			return false;
		}
		if ($listCount > $limitCount) {
			// 保存数を超えている場合

			// 更新時間の昇順（古い順）で一覧取得
			$command = "ls -tr $bkupDir | grep $matchKey";
			$output = array();	// この初期化注意！
			$res = exec($command, $output, $return_var);
			if ($return_var !== 0 || count($output) <= 0) {
				errorLog($command, "コマンド実行エラー" , BATCH_ERROR_LOG_FILE);
				return false;
			}

			// 削除するファイル数
			$delRemainCount = $listCount - $limitCount;
			// 現在時間
			$curTime = time();

			// 削除実行
			foreach($output as $key => $value) {
				if ($delRemainCount < 1)
					break;
				// 削除対象ファイルパス
				$targetFile = $bkupDir . '/' . $value;
				if ($curTime - filemtime($targetFile) > $limitTime) {
					// ファイル保存期間を超過している場合
					if (!unlink($targetFile)) {
						errorLog($targetFile, "{$type} 削除失敗" , BATCH_ERROR_LOG_FILE);
						return false;
					} else {
						eventLog($targetFile, "{$type} 削除成功" , BATCH_EVENT_LOG_FILE);
					}
				} else {
					// ファイル保存期間を超過していない場合
					//     最初のファイルが対象外なら以降も全て対象外なので終了する。
					eventLog("{$type} 削除対象ファイルなし（最長保存期間を超えていないため）", '', BATCH_EVENT_LOG_FILE);
					return true;
				}
				$delRemainCount--;
			}
		} else {
			// 削除対象ファイルなし
			eventLog("{$type} 削除対象ファイルなし（現在の保存数：{$listCount}、保存数の上限：{$limitCount}）", '', BATCH_EVENT_LOG_FILE);
			return true;
		}
		return true;
	}

}






























