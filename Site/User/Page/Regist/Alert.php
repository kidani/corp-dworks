<?php

/**
 *
 * お知らせ通知登録画面
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// 現在の設定値取得
$alertSetting = new AlertSetting();
if (!$data = $alertSetting->getByUserId(User::$userId)) {
	showError(User::$userInfo, 'お知らせ通知設定取得失敗');
	return;
}
WvSmarty::$smarty->assign('data', $data);

$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'finish') {
	showInfo('お知らせ通知設定を更新しました。');
	return;
} elseif ($mode === 'update') {

	// 更新の場合
	//     お知らせ通知設定はメール認証完了時点でデフォルト値がセットされるので更新のみ。
	if (!$alertSetting->update(User::$userId, $param)) {
		showError($param, 'お知らせ通知設定更新失敗');
	}
	// 重複更新ガード
	header("location:?p=Regist/Alert&mode=finish");
	exit;
}
