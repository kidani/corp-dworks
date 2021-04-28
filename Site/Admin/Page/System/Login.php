<?php

/**
 *
 * ログイン画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

if (isset($_SESSION['adminId'])) {
	// セッション情報あり（ログイン済み）ならトップへ
    header("Location:" . SiteConfig::$conf['SITE_URL_ADMIN']);
    exit(0);
}

$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'login') {
	// 管理ユーザー情報取得
	$adminUser = new AdminUser();
	$data = $adminUser->getAdminUserByLoginIdPass($param['loginId'], $param['pass']);
	if (!$data) {
		showWarning('IDまたはパスワードが違います。');
		return;
	}
	$adminId = $_SESSION['adminId'] = $data['adminId'];

	// 保持した戻りURL取得
	$backUrl = $adminUser->getBackUrl();
	if ($backUrl) {
		header("location:{$backUrl}");
		exit;
	} else {
		// トップへ
		header("Location:" . SiteConfig::$conf['SITE_URL_ADMIN']);
		exit;
	}
}
