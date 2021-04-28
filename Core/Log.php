<?php

/**
 *
 * デバッグ情報の出力
 *
 * パラメータ
 *   $object    ：メッセージ（変数・オブジェクト）
 *   $title     ：メッセージタイトル
 *   $fileName  ：出力ファイルパス
 *   $traceLimit：トレースするスタックフレーム数
 *
 * 用例：
 *   debug($object);
 *   debug($object, 'check1');
 *   debug($object, 'check1', './debug1.log');
 *   debug($object, 'check1', '/tmp/debug.log');
 *   debug($object, 'check1', '/tmp/error.log', 10);
 *   debug($_GET, '_GET');
 *   debug($_POST, '_POST');
 *   debug($_SERVER, '_SERVER');
 *
 */
function debug($object, $title = '', $fileName = DEBUG_LOG_FILE, $traceLimit = 7) {

	// デバッグ出力するユーザーを限定
	if (defined('USERID') && defined('DEBUG_OUTPUT_USERID')) {
		if (USERID && DEBUG_OUTPUT_USERID && USERID !== DEBUG_OUTPUT_USERID) {
			return;	
		}
	}

	// 先頭行取得
	$headLine = getHeadLine($title);

    // スタックトレース取得
    $brString = ($fileName ? null : '<br>');
    $trace = @debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $traceLimit);
    $traceLine = formatTraceLine($trace, $brString);

    // オブジェクトの内容出力
    $contents = null;
    if ($object) {
        ob_start();
        print_r ($object);
        $contents = ob_get_contents();
        ob_end_clean();
    }

	if ($fileName) {
		// ファイル出力
		
	    // ファイル作成
	    //     作成者が apache になるので書込み権限を付与しておく。
	    if (!file_exists($fileName)) {
	        touch($fileName);               // 0644で作成される。
	        chmod($fileName, 0666);         // 書込み権限追加。
	    }
		// 5MB以上の場合 5242880
		if (filesize($fileName) > 5242880) {
	    	$timeInfo = getDateTimeInfo();
	    	// 上書き回避
	    	$saveFileName = "{$fileName}_{$timeInfo['date']}";
			if (file_exists($saveFileName)) {
				// 分まで追加
				$saveFileName = "{$fileName}_{$timeInfo['date']}_{$timeInfo['hour']}{$timeInfo['min']}_{$timeInfo['sec']}";
			}
			rename($fileName, $saveFileName);	// リネーム
			touch($fileName);               	// 0644で作成される。
			chmod($fileName, 0666);         	// 書込み権限追加。
		}
	    // ファイル出力
	    $fp = @fopen($fileName, "a+");
	    @fwrite($fp, "===================================================================================\n");
	    @fwrite($fp, "{$headLine}\n");
	    @fwrite($fp, "{$traceLine}");
	    @fwrite($fp, "-----------------------------------------------------------------------------------\n");
	    @fwrite($fp, "{$contents}\n");
	    @fwrite($fp, "===================================================================================\n");
	    @fclose($fp);
    } else {
        // ブラウザ出力         
		$output  = '<hr style="border-top: 5px solid #000000;">';
		$output .= "{$headLine}<br>";
		$output .= "{$traceLine}";
		$output .= '<hr style="border-top: 2px dashed #000000;">';	
		$output .= "<pre>{$contents}</pre>";
		$output .= '<hr style="border-top: 5px solid #000000;">';
		return $output;
    } 
}

/**
 *
 * ライン出力
 *
 * index.php の読込み終了時など、
 * デバッグログの実行毎の切れ目を確認用。
 *
 * 用例：
 *  writeLine();
 *  writeLine('/tmp/error.log');
 *
 */
function writeLine($str = '■', $fileName = DEBUG_LOG_FILE) {
	if (!file_exists($fileName)) {
		return;
	}
	$line = str_repeat($str, 50);

	// ファイル出力
	$fp = @fopen($fileName, "a+");
	@fwrite($fp, "$line\n");
	@fclose($fp);
}

/**
 *
 * デバッグ情報の1行出力
 *
 * 処理結果をトレースする目的。
 * debug() だと情報が多くて見づらいので。
 *
 * 用例：
 *  trace();
 *  trace('/tmp/error.log');
 *  if (WV_DEBUG) trace("施設：{$data['schoolId']}追加");
 *
 */
function trace($str, $fileName = DEBUG_LOG_FILE) {
	if (!file_exists($fileName)) {
		touch($fileName);               // 0644で作成される。
		chmod($fileName, 0666);         // 書込み権限追加。
		if (!file_exists($fileName)) {
			// 作成失敗
			return;
		}
	}
	// ファイル出力
	$fp = @fopen($fileName, "a+");
	$str = getHeadLine() . $str;		// タイムスタンプ追加
	@fwrite($fp, ">>>>> {$str}\n");
	@fclose($fp);
}

/**
 *
 * チェックログファイル出力
 *
 * 用例：
 *  checkLog($hoge);
 *  checkLog($mailTo, 'mail error');
 *  checkLog($hoge, 'mail error', './error.log')
 *  checkLog($hoge, 'mail error', '/tmp/error.log')
 *  checkLog($hoge, 'mail error', '/tmp/error.log')
 *
 */
function checkLog($object, $title = '', $fileName = CHECK_LOG_FILE) {
    debug($object, $title, $fileName);
}

/**
 *
 * エラーのファイル出力
 *
 * パラメータ
 *   $object        ：メッセージ（変数・オブジェクト）
 *   $title         ：メッセージタイトル
 *   $traceLevel    ：トレースする階層、0で1つ上。
 *   $fileName      ：出力ファイルパス
 *
 * 用例：
 *  errorLog($hoge);
 *  errorLog($mailTo, 'mail error');
 *  errorLog($hoge, 'mail error', './error.log')
 *  errorLog($hoge, 'mail error', '/tmp/error.log')
 *  errorLog($hoge, 'mail error', '/tmp/error.log')
 *
 */
function errorLog($object, $title = '', $fileName = ERROR_LOG_FILE) {
	if (isset($_SERVER['HTTP_USER_AGENT']) && checkCrawler($_SERVER['HTTP_USER_AGENT']) !== 'none') {
		// クローラーはスルー
		// バッチ処理の場合 HTTP_USER_AGENT なし。
		//header("HTTP/1.0 404 Not Found");
		//exit;
	} else {
		// エラーログ出力
		debug($object, $title, $fileName);
		// 開発環境の場合
		//     同時にデバッグログも出力
		if (defined('SHOW_DEBUG_ERROR') && SHOW_DEBUG_ERROR) {
			debug($object, $title);
		}
	}
}

/**
 *
 * イベントログのファイル出力
 *
 * パラメータ
 *  $message    ：メッセージ（変数のみ、オブジェクト不可）
 *  $title      ：メッセージタイトル
 *  $fileName   ：出力ファイルパス
 *
 * 用例：
 *      eventLog($hoge);
 *      eventLog($hoge, 'backup success');
 *      eventLog($hoge, 'copy done', '/Tmp/event.log')
 *      eventLog($hoge, 'move done', '../Tmp/event.log')
 *
 */
function eventLog($message, $title = null, $fileName = EVENT_LOG_FILE) {
	
	// 先頭行取得
	$headLine = getHeadLine($title);

    // ファイル作成
    //     作成者が apache になるので書込み権限を付与しておく。
    if (!file_exists($fileName)) {
        touch($fileName);               // 0644で作成される。
        chmod($fileName, 0666);         // 書込み権限追加。
    }

    // ファイル出力
    $fp = @fopen($fileName, "a+");
    @fwrite($fp, "{$headLine}");
    @fwrite($fp, " {$message}\n");
    @fclose($fp);
}

/**
 *
 * 先頭行に表示する「現在時刻 タイトル」取得
 *
 */
function getHeadLine($title = null) {
    // 現在時刻取得
    $now_milli = ((float)microtime())*1000;
	$now = date('Y-m-d H:i:s.') . sprintf("%03d", $now_milli);
	// PF
	$pf = defined('PF') ? PF : '';
	$str = "{$now} ";
	if ($pf) {
		$str .= "{$pf} ";
	}
	if ($title) {
		$str .= "{$title} ";
	}
	return $str;
}

/**
 *
 * スタックトレースの整形
 *
 * パラメータ
 *   $backTraces    ：スタックトレースリスト
 *   $brString      ：改行（\n：ファイル出力、<br>：ブラウザ出力）
 *
 */
function formatTraceLine($backTraces, $brString = null) {
	$traceLine = null;
    foreach ($backTraces as $key => $value) {
    	if (!isset($value['line'])) {
			// 何故かこのファイル内の関数 showPhpFatalError から debug が呼ばれた場合
			// $backTraces に file, line が入って来ない。
			$value['file'] = __FILE__;
			$value['line'] = '行数不明';
		}
        $lineNo = $value['line'];
		// $fileName = $value['file'];              // フルパス
       // $fileName = basename($value['file']);		// ファイル名のみ
	   // フレームワークルートからの相対パス（WVFW_ROOTを除去）
	   $WVFW_ROOT = WVFW_ROOT;
	   $fileName = preg_replace("|$WVFW_ROOT|u", 	"", $value['file']);
		$class_name = isset($value['class']) ? $value['class'] : null;
		$type = isset($value['type']) ? $value['type'] : null;
        if ($type === null) {
            $functionName = $value['function'];
        } else {
            $functionName = $class_name . $type . $value['function'];
        }
        //  [ファイル名 関数名 :行番号]
        if ($brString) {
        	$traceLine .= "[{$fileName} {$functionName}:{$lineNo}]{$brString}";
		} else {
			$traceLine .= "[{$fileName} {$functionName}:{$lineNo}]\n";	
		}
    }
    return $traceLine;
}

/**
 *
 * 表示メッセージ取得
 *
 * Ajaxの場合に利用
 *
 */
function getMasterMessage($type) {
    global $MASTER_MESSAGE;
	return $MASTER_MESSAGE['message'][$type];
}

/**
 *
 * インフォメーションメッセージ表示
 *
 * 用例：
 *      showInfo($message);
 *
 */
function showInfo($message) {
    global $MASTER_MESSAGE;
    // インフォメーションメッセージ表示を追加
    if (is_array($message)) {
    	// 配列の場合
    	$MASTER_MESSAGE['message']['info'] 
    		= array_merge($MASTER_MESSAGE['message']['info'], $message);	
	} else {
		// 変数の場合	
		array_push($MASTER_MESSAGE['message']['info'], $message);	
	}
}

/**
 *
 * メッセージダイアログ表示
 *
 * 用例：
 *      showDialog($message);
 *
 */
function showDialog($message) {
	global $MASTER_MESSAGE;
	// インフォメーションメッセージ表示を追加
	if (is_array($message)) {
		// 配列の場合
		$MASTER_MESSAGE['message']['dialog']
			= array_merge($MASTER_MESSAGE['message']['dialog'], $message);
	} else {
		// 変数の場合
		array_push($MASTER_MESSAGE['message']['dialog'], $message);
	}
}

/**
 *
 * ワーニングメッセージ表示
 *
 * $message ：変数・配列
 * 
 * 用例：
 *      showWarning($message);
 *
 */
function showWarning($message) {
    global $MASTER_MESSAGE;
    // ワーニングメッセージを追加
    if (is_array($message)) {
    	// 配列の場合
    	$MASTER_MESSAGE['message']['warning'] 
    		= array_merge($MASTER_MESSAGE['message']['warning'], $message);	
	} else {
		// 変数の場合	
		array_push($MASTER_MESSAGE['message']['warning'], $message);	
	}	 
}

/**
 *
 * エラーメッセージ表示とエラーログ出力 
 *
 * パラメータ
 *   $data          ：エラーログ詳細（変数・配列・オブジェクトなどエラー解析用データ）
 *   $title         ：エラーログのタイトル
 *   $showDefault   ：ユーザー画面へのエラーメッセージ表示（true：定型文を表示、false：詳細を表示）
 *   $outputLog     ：エラーログへの出力（true：出力あり、false：出力なし）
 *   $linkPage      ：戻るボタンのリンク先（デフォルト：Back［history.back()］）
 *                  ：出力ページがPOSTの場合は、Back だと遷移できないので、Top 等を指定すること。
 * 
 * 用例：
 *      showError($data);								// ユーザーにはデフォルトメッセージを表示。
 *      showError($data, '購入エラー');					// ユーザーにはデフォルトメッセージを表示。
 *      showError(null, null, true, false);				// ユーザーにはデフォルトメッセージを表示、エラーログ出力なし。 
 *      showError(null, $title, false, false);			// ユーザーには「$title」を表示。エラーログ出力なし。 
 *      showError(null, '全角1～4文字で登録して下さい。', false, false, 'Name/NameChg');  // ユーザーには「$title」を表示。エラーログ出力なし。戻るボタンのリンク先指定あり。
 *
 * 備考：
 *      開発環境の場合：常にページ上部にデバッグ情報を表示する。
 * 						（ページ遷移ない場合はこの関数内で直接出力して終了）
 *      本番環境の場合：通常は定型メッセージのみ表示するが、
 *                      オプションにより設定したメッセージの表示も可能。
 *      Exception オブジェクトを Smarty に渡すと、自動的に加工整形して出力されてしまうので注意。     
 *
 */
function showError($message, $title = '', $showDefault = true, $outputLog = true, $linkPage = 'Back') {
    // メッセージ用の配列
    global $MASTER_MESSAGE;

	// エラーログ出力
    if ($outputLog) errorLog($message, $title);

	// デバッグ情報表示
    if (SHOW_DEBUG_ERROR || (defined('DEBUG_USER') && DEBUG_USER)) {
    	// デバッグ向けのエラー情報表示が有効の場合

		// デバッグ出力取得
		$output = debug($message, $title, '');

		if (defined('PAGE_MAIN_PATH')) {
			// メインテンプレート確定済みの場合
			//     メッセージテンプレートに表示する。
			array_push($MASTER_MESSAGE['message']['debug'], "{$output}");
		} else {
			// メインテンプレート未確定の場合
			//     メインページのPHP読込み前のエラー発生時に通る。
			if (defined('ACCESS') && ACCESS === 'USER') {
				// ユーザーアクセスの場合
				//     ここで表示して終了。
				echo $output;
				echo 'テンプレート未確定のため以降の処理を停止しました。';
				exit;
			} else {
				// ユーザーアクセス以外の場合
				//     AJAXやサーバー間通信の場合、以降のエラー出力も確認できるよう処理を継続
			}
		}

	}

	// ユーザー画面へのエラー内容表示

    // 戻るボタンの遷移ページを指定
    $MASTER_MESSAGE['linkPage'] = $linkPage;

	// 表示メッセージのセット
	$MASTER_MESSAGE['message']['error'] = array();
   if ($showDefault === false) {
        // $title をそのまま表示
		array_push($MASTER_MESSAGE['message']['error'], "{$title}");	
    } else {
        // 定型メッセージを表示
		array_push($MASTER_MESSAGE['message']['error'], ERROR_MESSAGE_DEFAULT);
	}	
}

/**
 *
 * PHPエラー制御（Fatal以外）
 *
 * Ajax実行の場合に WARNING や NOTICE が出力されることで
 * JS処理が停止しないよう index.php の set_error_handler で有効にしている。
 *
 * Fatal Error は set_error_handler で捕捉できないので注意！
 * WV_DEBUG の際以外は呼ばないこと。
 *
 */
function showPhpError ($errno, $errstr, $errfile, $errline) {
	switch ($errno) {
		case E_USER_ERROR:
			$errstr = "ERROR {$errstr}";
			break;
		case E_USER_WARNING:
			$errstr = "WARNING {$errstr}";
			break;
		case E_USER_NOTICE:
			$errstr = "NOTICE {$errstr}";
			break;
		default:
			$errstr = "UNKNOWN ERROR {$errstr}";
			break;
	}
	$detail['エラー内容'] = $errstr;
	$detail['ファイル'] = $errfile;
	$detail['行番号'] = $errline;
	// エラー表示
	if (defined('ACCESS') && ACCESS === 'AJAX') {
		// Ajax の場合、エラー停止しないようログで表示
		debug($detail, 'PHPエラー発生');
	} else {
		// Ajax 以外はブラウザで表示
		//if (!strstr($detail['ファイル'], 'Smarty/Template_c')) {
			 // Smarty のエラー「UNKNOWN ERROR Undefined index」は頻繁に出るので除外
			echo "<pre>";print_r ('showPhpError');echo "</pre>";
			echo "<pre>";print_r ($detail);echo "</pre>";
		//}
	}
	// PHP の内部エラー出力を停止
	return true;
}

/**
 *
 * PHP Fatal Error 制御（デバッグ専用）
 *
 * Fatal Error は set_error_handler でハンドリングできないのでこちらで制御する。
 * 正常終了で exit した場合でもここに入ってくるので注意！
 * デバッグ（WV_DEBUG）以外では呼ばないこと。
 * Parse error の捕捉はできない。
 * FIXME：何故この処理を追加したのか理由を忘れた。Ajax のデバッグをしやすいようにかも。
 * この関数を使わないで、そのままPHP内部エラー出した方がいいかも。
 *
 */
function showPhpFatalError () {

	// 最後のエラーを取得
	// http://nkawamura.hatenablog.com/entry/2016/10/26/180122
	// https://gist.github.com/koshiaaaaan/903173
	// ＜例＞
	// --------------------------------------------------
	// 	Array
	// 	(
	// 	    [type] => 2
	// 	    [message] => Missing argument 5 for showPhpFatalError()
	// 	    [file] => /var/www/html/Social/Hoikuen/Dev/Core/Log.php
	// 	    [line] => 469
	// 	)
	// --------------------------------------------------

	// バッファの取得とクリア
	// この時点までのワーニング、エラー等のPHP出力バッファを取得して削除する。
	// これをしないと、次の error_get_last で Fatal Error の内容を取得できないみたい。
	$bufError = ob_get_clean();

	// Error の内容取得
	// Fatal Error 発生時に通常ブラウザ出力される内容を取得できる。
	$lastError = error_get_last();
	if (!$lastError) {
		// ◆正常終了で exit した場合、ここに入ってくる！
		if ($bufError) {
			// 正常終了だが出力バッファありの場合
			// 今までバッファに貯めたエラーを出力
			// showPhpFatalError が有効（register_shutdown_function 登録されている）の場合
			// デバッグ用の echo ～ exit; はその場では出力されず、ここで初めて出力されるので注意！
			if (WV_DEBUG) trace("showPhpFatalError（正常終了）：{$bufError}");
			echo ($bufError);		// これがないと echo ～ exit; が出力されない！
			// ややこしくなるので AJAX, SERVER の場合、showPhpFatalError は無効にしているが、
			// 仮に有効にしていた場合、AJAX 処理の正常終了応答 echo json_encode(array('result' => 'success'));
			// もここで echo ($bufError); した際に初めて出力されるので注意！
		} else {
			// 正常終了で出力バッファなしの場合（通常の正常終了）
		}
	} else {
		switch ($lastError['type']) {
			case E_ERROR:
				$errstr = "FATAL ERROR";
				break;
			default:
				$errstr = "UNKNOWN ERROR";
				break;
		}
		if (WV_DEBUG) debug($lastError, "showPhpFatalError：{$errstr} 発生");
		echo "<pre>";print_r ("showPhpFatalError：{$errstr} 発生");echo "</pre>";
		echo "<pre>";print_r ($lastError);echo "</pre>";
	}
	exit;
}

/**
 *
 * ログ出力の詳細情報を設定
 *
 * 用例：
 *      setLogDetail($var1, $var2, $var3, ...);  可変引数
 *      showError(setLogDetail(), 'エラー：◯◯を取得できません。');
 *
 */
function setLogDetail() {
	$detail = null;
	$idx = 1;
	$args = func_get_args();
	foreach ($args as $key => $value) {
		$detail["args{$idx}"] = $value;
		$idx++;
	}
	// パラメータ追加
	global $param;
	// $param = getHttpQuery();
	if ($param) $detail['param'] = $param;

	// ユーザー情報追加
	if (isset(User::$userInfo) && User::$userInfo) $detail['userInfo'] = User::$userInfo;

	// ユーザーエージェント
	if (isset($_SERVER['HTTP_USER_AGENT']))
		$detail['userInfo'] = $_SERVER['HTTP_USER_AGENT'];

	// セッション
	// $detail['session'] = $_SESSION;
	return $detail;
}
