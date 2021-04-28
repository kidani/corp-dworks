<?php

/**
 *
 * 住所登録・更新・削除画面
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 * 
 */

// 住所ID
$address = new Address();
$addressId = isset($param['addressId']) ? $param['addressId'] : null;
if ($addressId) {
	$data = $address->getById($addressId, User::$userId);
	if (!$data) {
		showError($addressId, '住所が取得できません。');
		return;
	}
	WvSmarty::$smarty->assign('data', $data);
}

// モード
$mode = isset($param['mode']) ? $param['mode'] : 'add';
if ($mode === 'update') {
	// 登録・更新の場合

	// 入力パラメータで上書き
	WvSmarty::$smarty->assign('data', $param);

	// 市区町村コード取得
	$area = new Area();
	$city = $area->getCityByPrefAndCityName($param['prefName'], $param['cityName']);
	$param['cityCode'] = $city['cityCode'];

	// 入力チェック
	if ($errMessage = $address->validate($param, User::$userId)) {
		showWarning(array($errMessage));
		return;
	}

	if ($addressId) {
		// 更新の場合

		// 更新
		$paramUpdate = array(
			'updTime'  	=> array('value' => date('Y-m-d H:i:s')   	, 'type' => 'datetime'),
			'sei'    	=> array('value' => $param['sei']       	, 'type' => PDO::PARAM_STR),
			'mei'    	=> array('value' => $param['mei']       	, 'type' => PDO::PARAM_STR),
			'seiKana'   => array('value' => $param['seiKana']    	, 'type' => PDO::PARAM_STR),
			'meiKana'   => array('value' => $param['meiKana']       , 'type' => PDO::PARAM_STR),
			'zip'      	=> array('value' => $param['zip']     		, 'type' => PDO::PARAM_STR),
			'cityCode' 	=> array('value' => $param['cityCode']     	, 'type' => PDO::PARAM_INT),
			'street'   	=> array('value' => $param['street']     	, 'type' => PDO::PARAM_STR),
		);
		$paramWhere = array(
			'id'         => array('value' => $addressId				, 'type' => PDO::PARAM_INT),
			'userId'     => array('value' => User::$userId			, 'type' => PDO::PARAM_STR),
		);
		if (!Db::pdoUpdate('Address', $paramUpdate, $paramWhere)) {
			showError($paramUpdate, '住所更新失敗');
		}

		// 重複更新ガード
		header("location:?p=MyPage/Profile&mode=finish&message=住所を登録しました。");
		exit;

	} else {
		// 新規登録の場合

		// 新規登録
		$paramInsert = array(
			'userId'   	=> array('value' => User::$userId         	, 'type' => PDO::PARAM_STR),
			'insTime'  	=> array('value' => date('Y-m-d H:i:s')   	, 'type' => 'datetime'),
			'sei'    	=> array('value' => $param['sei']       	, 'type' => PDO::PARAM_STR),
			'mei'    	=> array('value' => $param['mei']       	, 'type' => PDO::PARAM_STR),
			'seiKana'   => array('value' => $param['seiKana']    	, 'type' => PDO::PARAM_STR),
			'meiKana'   => array('value' => $param['meiKana']       , 'type' => PDO::PARAM_STR),
			'zip'      	=> array('value' => $param['zip']     		, 'type' => PDO::PARAM_STR),
			'cityCode' 	=> array('value' => $param['cityCode']     	, 'type' => PDO::PARAM_INT),
			'street'   	=> array('value' => $param['street']     	, 'type' => PDO::PARAM_STR),
		);
		if (!Db::pdoInsert('Address', $paramInsert)) {
			showError('住所追加失敗');
			return;
		}
		// 戻りURL
		if ($backUrl = $user->getBackUrl(User::$userId)) {
			header("location:{$backUrl['url']}");
		} else {
			// 重複更新ガード
			header("location:?p=MyPage/Profile&mode=finish&message=住所を登録しました。");
		}
		exit;
	}
} elseif ($mode === 'del') {
	// 削除の場合

	// 住所IDチェック
	if (!$addressId) {
		showError($param, '住所IDが取得できません。');
		return;
	}

	// 削除
	if (!$address->deleteById($addressId, User::$userId)) {
		showError($param, '住所の削除に失敗しました。');
		return;
	}

	header("location:?p=MyPage/Profile&mode=finish&message=住所を削除しました。");
	exit;
}

