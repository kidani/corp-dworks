<?php

/**
 *
 * 非対応バージョン画面
 *
 */

// 必要なバージョン情報
WvSmarty::$smarty->assign('UserConfig', UserConfig::$conf);

// アプリのOSバージョンチェック
if (!isset($_SESSION['osVersion']) || !checkVersion($_SESSION['osVersion'], UserConfig::$conf['AppOsVersion'][PF])) {
	WvSmarty::$smarty->assign('osVersion', 1);
	return;
}

// アプリのビルドバージョンチェック（versionCode）
// ブラウザ側のバージョンアップと同期が必要な場合にチェック必須。
if (!isset($_SESSION['versionCode']) || !checkVersion($_SESSION['versionCode'], UserConfig::$conf['AppVersionCode'][PF])) {
	WvSmarty::$smarty->assign('versionCode', 1);
	return;
}