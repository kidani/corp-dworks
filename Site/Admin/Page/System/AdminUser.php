<?php

// 管理ユーザー取得
$adminUser = new AdminUser();
$adminId = isset($param['adminId']) ? $param['adminId'] : null;
$mode = isset($param['mode']) ? $param['mode'] : null;
$data = null;
if ($adminId) {
	$data = $adminUser->getAdminUserById($adminId);
}
if ($mode == 'show' && $param['adminId'] != '') {
    // 詳細表示
} elseif ($mode == 'add') {
    // 新規登録（初期状態画面）
} elseif ($mode == 'update') {
    //------------------------------------------------
    // 新規登録・更新
    //------------------------------------------------  	
	$mode = $data ? 'update' : 'new';
	if ($mode === 'new' && $param['pass'] === '＊＊＊＊＊') {
		showWarning('パスワードが無効です。');
		return;
   	}
	$errMessage = $adminUser->validateAdminUserParam($param);
    if ($errMessage) {
    	showWarning($errMessage);	
		// 入力値に入替え
		$data = $adminUser->getAdminUserById($param['adminId']);
		$data['loginId'] = $param['loginId'];
		$data['name'] = $param['name'];
		$data['pass'] = $param['pass'];
		$data['authType'] = $param['authType'];			
    } else {
		// ログインID・パスの組合せで重複が存在するかチェック
		$dataNow = $adminUser->getAdminUserByLoginIdPass($param['loginId'], $param['pass']);
		if ($dataNow) {
			if ($mode === 'new') {
				// ログインID・パスの組合せで重複が存在する
				$data['loginId'] = $param['loginId'];
				$data['name'] = $param['name'];
				$data['pass'] = $param['pass'];
				$data['authType'] = $param['authType'];	
				showWarning('ログインIDまたはパスワードを変更して下さい。');
				WvSmarty::$smarty->assign('data', $data);
				return;	
			} elseif ($mode === 'update') {
				if ($dataNow['loginId']	=== $data['loginId'] && MD5($dataNow['pass']) === MD5($data['pass'])) {
					// ログインID・パスの組合せが変更前後で同じ場合エラー
					$data['loginId'] = $param['loginId'];
					$data['name'] = $param['name'];
					$data['pass'] = $param['pass'];
					$data['authType'] = $param['authType'];	
					showWarning('ログインIDまたはパスワードを変更して下さい。');
					WvSmarty::$smarty->assign('data', $data);
					return;	
				}	
			}
		}
	    $paramUpsert = array(
	          'insTime'     => array('value' => date('Y-m-d H:i:s'),   'type' => 'datetime')
	        , 'updTime'     => array('value' => date('Y-m-d H:i:s'),   'type' => 'datetime')
			, 'adminId'     => array('value' => $adminId,              'type' => PDO::PARAM_INT)
	        , 'loginId'     => array('value' => $param['loginId'],     'type' => PDO::PARAM_STR)
			, 'pass'        => array('value' => MD5($param['pass']),   'type' => PDO::PARAM_STR)
			, 'name'        => array('value' => $param['name'],        'type' => PDO::PARAM_STR)
			, 'authType'    => array('value' => $param['authType'],    'type' => PDO::PARAM_STR)
	    );
		if (!$adminId) unset($paramUpsert['id']);
		// 更新の場合、パスワード変更なしとみなす。
		if ($param['pass'] === '＊＊＊＊＊') unset($paramUpsert['pass']);
		$dbh = Db::connect();
	    $result = Db::pdoUpsert('AdminUser', $paramUpsert);
		if ($mode === 'new') {
			showWarning('データが新規登録されました。');
			$adminId = $dbh->lastInsertId();
       	} else {
       		showWarning('データが更新されました。');
		}
        // 更新後のデータ取得
		$data = $adminUser->getAdminUserById($adminId);
    }
}
if ($data) {
	WvSmarty::$smarty->assign('data', $data);
}




















