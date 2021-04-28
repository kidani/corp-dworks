<?php

/**
 *
 * 機能：週次バックアップ
 *   ・ソースのフルバックアップ
 *   ・古いフルバックアップの削除
 *   ・古いスクリプトバックアップの削除
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
$bkupDir = BKUP_DIR;          	// バックアップ先ディレクトリ
$datetime = date("Ymd-Hi");
$dstPath = "$bkupDir/$prefix-full-$datetime.tgz";

//------------------------------------------------
// フルバックアップ
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

// 7日（10080分）より後に変更されたファイルがあるか検索
//     例：find ../../Dev -mmin -10080| grep -v Template_c | grep -v .git | grep -v .gitignore
$command = "find $srcDir" . ' -mmin -10080 '
	. $excludePh;
exec($command, $output, $returnVar);

if ($returnVar != 0 && !$output) {
	// 更新ファイルなしの場合
	eventLog($dstPath, 'Full Backup 更新ファイルなし', BATCH_EVENT_LOG_FILE);
} else {
	// 更新ファイルありの場合
	//     1ファイルでも更新があれば、全体をバックアップ

	// 除外ファイル・ディレクトリ設定
	$excludePh = '--exclude ' . implode(" --exclude ", $exclude);

	// 例：tar -czf ../../../../Bkup/Slg/Cnc1/Dev/hoge.com-full-20150810-1608.tgz
	//    --exclude Template_c --exclude .git --exclude .gitignore ../../Dev
	$command = "tar -czf $dstPath $excludePh $srcDir";

	exec($command, $output, $returnVar);
	if ($returnVar != 0) {
		errorLog($command, 'Full Backup 失敗 returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
		eventLog($dstPath, 'Full Backup 失敗', BATCH_ERROR_LOG_FILE);
		exit;
	}
	if ($remoteBkup['Copy']) { 
		// バックアップサーバにコピー
		$command = "scp -rp $dstPath {$remoteBkup['User']}" . "@" . "{$remoteBkup['Server']}:$remoteBkupDir";
		exec($command, $output, $returnVar);
		if ($returnVar != 0) {
			errorLog($command, 'Full Backup リモートコピー失敗 returnVar - ' . $returnVar, BATCH_ERROR_LOG_FILE);
			eventLog($dstPath, 'Full Backup リモートコピー失敗', BATCH_EVENT_LOG_FILE);
		}
	 }
}

// スクリプトフルバックアップ成功
eventLog($dstPath, 'Full Backup 成功', BATCH_EVENT_LOG_FILE);

//------------------------------------------------
// ローテーション
//------------------------------------------------
$batch = new Batch();

/**
 * フルバックアップ
 */
// 保存条件
$limitCntFull = 30;						// 30世代
$limitTimeFull = 24*60*60*200;          // 200日（単位：秒）
// ローテーション実行
$batch->logRotate('Full Backup', $bkupDir, "$prefix-full", $limitCntFull, $limitTimeFull);

/**
 * スクリプトバックアップ
 */
// 保存条件
$limitCntScript = 50;					// 50世代
$limitTimeScript = 24*60*60*50;         // 50日（単位：秒）
// ローテーション実行
$batch->logRotate('Script Backup', $bkupDir, "$prefix-script", $limitCntScript, $limitTimeScript);

/**
 * DBバックアップ
 */

// 保存条件
$limitCntDB = 30;						// 30世代
$limitTimeDB = 24*60*60*30;             // 30日（単位：秒）

// DB情報取得
$iniFilePath = WVFW_ROOT . '/Site/Config/Config.ini';
$ini = parse_ini_file($iniFilePath, true);
$dbName = $ini['Db']['name'];

// ローテーション実行
if ($dbName) {
	$res = $batch->logRotate('DB Backup', $bkupDir, "{$dbName}.*\.sql", $limitCntDB, $limitTimeDB);
	if ($res === false) {
		errorLog($bkupDir, "DB Backup ローテーション失敗", BATCH_ERROR_LOG_FILE);
	}
} else {
	eventLog("DB Backup 削除対象ファイルなし（定期バックアップDBなし）", '', BATCH_EVENT_LOG_FILE);
}

