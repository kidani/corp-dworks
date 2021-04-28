<?php

/**
 *
 * 分毎のチェック（基本的に10分毎）
 *
 * クーロンで実行
 * cd /var/www/html/Social/Hoikuen/Dev/Site/Admin/Web; /usr/bin/php index.php p=Batch/CheckMinutely 2> /dev/null
 *
 */

require_once (WVFW_ROOT . '/Site/Admin/Config/BatchConfig.php');

//------------------------------------------------
// エラーログチェック
//------------------------------------------------
// 以前は Batch/ErrorLogCheck.php でエラーログチェックのみ処理していたが、関西ぱどの際に
// クローンの起動数を増やしたくないので、エラーログチェックを Core/Batch.php に
// 配置して Batch/CheckMinutely.php にまとめた。
$errroLogIni = WVFW_ROOT . '/Site/Admin/Config/BatchErrorLogCheck.ini';
$batch = new Batch();
$batch->errorLogCheck($errroLogIni);
