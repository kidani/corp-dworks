<?php

/**
 *
 * プロフィール登録画面
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */




// プロフィール取得
$profile = new Profile();
$dataProf = $profile->getByUserId(User::$userId);
if (!$dataProf) {
	$dataProf['nickname'] = null;
	$dataProf['profile'] = null;
	$dataProf['sex'] = null;
}

WvSmarty::$smarty->assign('data', $dataProf);

$mode = isset($param['mode']) ? $param['mode'] : 'add';
if ($mode === 'update') {
	// 登録・更新の場合

	// 入力パラメータで上書き
	WvSmarty::$smarty->assign('data', $param);

	// 公開プロフィール入力チェック
	if ($errMessage = $profile->validate($param)) {
		showWarning(array($errMessage));
		return;
	}

	// 公開プロフィール登録
	$searchPoint = $arr = array(
		'address'   => $param['address'],
		'latitude'  => $param['latitude'],
		'longitude' => $param['longitude'],
	);
	$param['searchPoint'] = json_encode($searchPoint);
	if (!$profile->upsert($param, User::$userId)) {
		showError('公開プロフィール登録失敗');
		return;
	}

	// 重複更新ガード
	header("location:?p=MyPage/Profile&mode=finish&message=プロフィールを登録しました。");
	exit;
}






























