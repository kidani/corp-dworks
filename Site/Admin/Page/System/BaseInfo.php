<?php

//------------------------------------------------
// 更新
//------------------------------------------------
$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'finish') {
	showInfo($param['redirectMessage']);
} elseif ($mode === 'update') {
	$status = isset($param['status']) ? $param['status'] : null;
	if (SiteConfig::$conf['System']['status'] !== $status) {
		// INIファイル更新
		$siteIniFile = WVFW_ROOT . '/Site/Config/Config.ini';
		updateIniFile($siteIniFile, 'System', 'status', $status);
		// リダイレクト
		$redirectMessage = "システム状態を{$status}に変更しました。";
		header("location:?p={$pageName}&mode=finish&redirectMessage={$redirectMessage}");
		exit;
	}
}

// 基本情報
$baseInfo = null;

// システム環境
if (ENV_ID === 'Dev') {
	$baseInfo['ENV_ID'] = '開発環境';
} elseif (ENV_ID === 'Prd') {
	$baseInfo['ENV_ID'] = '本番環境';
}

// 稼働状態
$baseInfo['systemStatus'] = SiteConfig::$conf['System']['status'];

// デバッグ用IPリスト
if (isset(SiteConfig::$conf['Debug']['ip'])) {
	$baseInfo['debugIp'] = SiteConfig::$conf['Debug']['ip'];
}

// デバッグ用ユーザーリスト
if (isset(SiteConfig::$conf['Debug']['userId'])) {
	$baseInfo['debugUserId'] = SiteConfig::$conf['Debug']['userId'];
}

// 各種メールアドレス
$baseInfo['Mail'] = SiteConfig::$conf['Mail'];

WvSmarty::$smarty->assign('baseInfo', $baseInfo);

