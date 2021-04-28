<?php

$adminUser = new AdminUser();

//------------------------------------------------
// 削除
//------------------------------------------------
if (isset($param['mode']) && $param['mode'] === 'del'
	&& isset($param['adminId']) && $param['adminId'] !== '') {
	$adminUser->deleteAdminUser($param['adminId']);
	showInfo("ID：{$param['adminId']}を削除しました。");
}

// 管理ユーザーリスト取得
$data = $adminUser->getAdminUserList();
WvSmarty::$smarty->assign('data', $data);
