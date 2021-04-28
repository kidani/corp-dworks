<?php

/**
 * 
 * バッチ処理設定
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// リモートバックアップサーバ
$ini = parse_ini_file("../Config/Config.ini", true);
$remoteBkup['Copy'] = $ini['BatchBackup']['remoteCopy'];
$remoteBkup['Server'] = $ini['BatchBackup']['remoteServer'];
$remoteBkup['User'] = $ini['BatchBackup']['remoteUser'];
$remoteBkup['Dir'] = $ini['BatchBackup']['remoteDir'];

$conf = SiteConfig::$conf['Base'];

// リモートのバックアップディレクトリ
// $remoteBkupDir = $remoteBkup['Dir'] . '/' . WVFW_ID . '/' . SYSTEM_ID . '/' . ENV_ID;
$remoteBkupDir = "{$remoteBkup['Dir']}/{$conf['WVFW_ID']}/{$conf['SYSTEM_ID']}/{$conf['ENV_ID']}";

// ローカルのバックアップディレクトリ
define('BKUP_DIR', "/var/www/Bkup/{$conf['WVFW_ID']}/{$conf['SYSTEM_ID']}/{$conf['ENV_ID']}");
