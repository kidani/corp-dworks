<?php

/**
 * 日次バックアップ
 *
 *     スクリプトのバックアップ
 *     DBのフルバックアップ
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Admin/Config/BatchConfig.php');

//------------------------------------------------
// ファイル・ディレクトリ構成
//------------------------------------------------
$prefix = DOMAIN;
$srcDir = WVFW_ROOT;     		// バックアップ対象ディレクトリ
$dstDir = BKUP_DIR;          	// バックアップ先ディレクトリ
$datetime = date("Ymd-Hi");
$dstPath = "$dstDir/$prefix-script-$datetime.tgz";

//------------------------------------------------
// スクリプトバックアップ（php, js, css, html のみ）
//------------------------------------------------
// バックアップ先ディレクトリチェック
if (!file_exists($dstDir)) {
	errorLog("バックアップ先ディレクトリ未作成検知：{$dstDir}", BATCH_ERROR_LOG_FILE);
	exit;
}

// 除外ファイル・ディレクトリ設定
$exclude = array(
	'Template_c',
	'Lib',
	'Admin/Tmp',
	'User/Tmp',
	'node_modules',
	'.git',
);
$excludePh = ' | grep -v ' . implode(" | grep -v ", $exclude);

// 1日（1440分）より後に変更されたファイルがあるか検索
//     例：find ../../Dev -mmin -1440 \( -iname \*.php -or -iname \*.htm -or -iname \*.css -or -iname \*.js \) | grep -v Template_c | grep -v Lib | grep -v .git | grep -v .gitignore
$command = "find $srcDir" . ' -mmin -1440 \( -iname \*.php -or -iname \*.htm -or -iname \*.css -or -iname \*.js \) '
	. $excludePh;
exec($command, $output, $returnVar);

if ($returnVar != 0 && !$output) {
	// 更新ファイルなしの場合
	eventLog($dstPath, 'Source Script Backup No Update', BATCH_EVENT_LOG_FILE);
} else {
	// 更新ファイルありの場合
	//     1ファイルでも更新があれば、スクリプト全体をバックアップ

	// 例：find ../../Dev \( -iname \*.php -or -iname \*.htm -or -iname \*.css -or -iname \*.js \) |
	//     grep -v Template_c | grep -v Lib | grep -v .git | grep -v .gitignore |
	//     xargs tar -czf ../../../../Bkup/Slg/Cnc1/Dev/hoge.com-script-20150810-1540.tgz
	//    標準エラー出力(2)に「tar: Removing leading `../../' from member names」が出るので、
	//    クーロンで「2> /dev/null」すること。
	$command = "find {$srcDir}" . ' \( -iname \*.php -or -iname \*.htm -or -iname \*.css -or -iname \*.js \) '
		. $excludePh . " | xargs tar -czf {$dstPath}";
	exec($command, $output, $returnVar);
	if ($returnVar != 0) {
		eventLog($dstPath, 'Source Script Backup Error', BATCH_EVENT_LOG_FILE);
		errorLog($command, 'Source Script Backup Error returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
		exit;
	}
	if ($remoteBkup['Copy']) {
		// バックアップサーバにコピー
		//     ・sudo を付けると「sudo: sorry, you must have a tty to run sudo」となるので付けないこと。
		//     ・パスフレーズを聞かれずにリモートコピーするために、リモート側の実行ユーザーと
		//       ローカルのスクリプト実行ユーザー間でSSH鍵認証方式の設定が必要。
		$command = "scp -rp {$dstPath} {$remoteBkup['User']}" . "@" . "{$remoteBkup['Server']}:{$remoteBkupDir}";
		exec($command, $output, $returnVar);
		if ($returnVar != 0) {
			eventLog($dstPath, 'Source Script Backup RemoteCopy Error', BATCH_EVENT_LOG_FILE);
			errorLog($command, 'Source Script Backup RemoteCopy Error returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
			print_r ('Source Script Backup RemoteCopy Error returnVar - ' . $returnVar);
			exit;
		}
	}
}

// スクリプトバックアップ成功
eventLog($dstPath, 'Source Script Backup Success', BATCH_EVENT_LOG_FILE);

//------------------------------------------------
// DBバックアップ
//------------------------------------------------

// DB情報取得
$iniFilePath = WVFW_ROOT . '/Site/Config/Config.ini';
$ini = parse_ini_file($iniFilePath, true);
$dbHost = $ini['Db']['host'];
$dbUser = $ini['Db']['user'];
$dbPass = $ini['Db']['pass'];
$dbName = $ini['Db']['name'];
$dstPath = $dstDir . "/$prefix-{$dbName}-$datetime.sql";

// バックアップ実行
//$command = '/usr/local/bin/mysqldump -h '. DB_HOST . ' -u' . DB_USER. ' -p' . DB_USER_PASS . " $dbName > $dstPath";
//    標準エラー出力(2)に「Warning: Using a password on the command line interface can be insecure.」
//    が出るので、クーロンで「2> /dev/null」すること。
$command = 'mysqldump -h '. $dbHost . ' -u' . $dbUser. ' -p' . $dbPass . " {$dbName} > {$dstPath}";
exec($command, $output, $returnVar);
if ($returnVar != 0) {
    eventLog($dstPath, 'DB Backup Error', BATCH_EVENT_LOG_FILE);
    errorLog($command, 'DB Backup Error returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
	exit;
}
if ($remoteBkup['Copy']) {
	// バックアップサーバにコピー
	$command = "scp -rp {$dstPath} {$remoteBkup['User']}" . "@" . "{$remoteBkup['Server']}:{$remoteBkupDir}";
	exec($command, $output, $returnVar);
	if ($returnVar != 0) {
		eventLog($dstPath, 'DB Backup RemoteCopy Error', BATCH_EVENT_LOG_FILE);
		errorLog($command, 'DB Backup RemoteCopy Error returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
		exit;
	} else {

	}
}

// DBバックアップ成功
eventLog($dstPath, 'DB Backup Success', BATCH_EVENT_LOG_FILE);













