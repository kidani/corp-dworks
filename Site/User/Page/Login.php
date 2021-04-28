<?php
/**
 *
 * ログイン画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// 既にログイン済みの場合
if (isset($_SESSION['userId']) && $_SESSION['userId'] !== ''
	&& isset($_SESSION['userLogin']) && $_SESSION['userLogin'] === 1) {
	// トップへ
	header("Location:" . SITE_URL);
    exit;
}

// クエリ保持（検索フォーム入力値引継ぎ用）
if (!isset($param['mailAddress'])) {
	// 初期値セット
	$param['mailAddress'] = null;
	$param['pass'] = null;
}
WvSmarty::$smarty->assign('data', $param);

$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'login') {

	if (!$param['mailAddress'] || !$param['pass']) {
		showWarning('メールアドレスまたはパスワードが未入力です。');	
		return;	
	}

	// ユーザー情報取得
	$data = $user->getUserByMailAddress($param['mailAddress']);
	if ($data) {
		if ($data['status'] === '登録済') {
			if ($data['pass'] === MD5($param['pass'])) {
				// ログイン認証完了
				$_SESSION['userId'] = $data['userId'];
				$_SESSION['userLogin'] = 1;
				// ログイン前の仮ユーザーIDを保持
				$tempUserId = User::$userId;
				// ユーザーIDを更新
				User::$userId = $data['userId'];
			} else {
				showWarning('メールアドレスまたはパスワードが正しくありません。');
				if (WV_DEBUG) showWarning('パスワード不一致');
				return;
			}
		} elseif ($data['status'] === '仮登録') {
			showWarning('このメールアドレスは仮登録状態です。<br>受信したメールの認証を完了して下さい。');
			return;
		} else {
			// status === '退会済'
			//     ここは getUserByMailAddress 時点で除外するようにしたので通らない。
			showWarning('メールアドレスまたはパスワードが正しくありません。');
			if (WV_DEBUG) showWarning('メールアドレス退会済');
			return;
		} 
	} else {
		// 敢えてエラー内容を曖昧にする。
		showWarning('メールアドレスまたはパスワードが正しくありません。');
		if (WV_DEBUG) showWarning('メールアドレス未登録');
		return;
	}

	// ここまで来たらログインOK
	//    最終ログイン日時、最終セッション開始日時更新
	$paramUpsert = array(
	      'userId'            => array('value' => User::$userId        , 'type' => PDO::PARAM_STR)
		, 'lastLoginTime'     => array('value' => date('Y-m-d H:i:s')  , 'type' => 'datetime')
	    , 'lastSessStartTime' => array('value' => date('Y-m-d H:i:s')  , 'type' => 'datetime')
	);
	if (!Db::pdoUpsert('Users', $paramUpsert)) {
	    showError('Users更新失敗');
	    return;
	}
	
	// 保持した戻りURL取得 
	$backUrl = $user->getBackUrl($tempUserId);
	if ($backUrl) {
		header("Location:{$backUrl['url']}");
		exit;
	} else {
		// トップへ
		header("Location:" . SITE_URL);
		exit;
	}	
}



































