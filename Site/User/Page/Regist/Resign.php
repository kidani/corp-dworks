<?php

/**
 *
 * 会員退会画面
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 * 
 */

// 退会可能かチェック
if (User::$userInfo['status'] !== '登録済') {
	showError(User::$userInfo, '会員未登録');
	return;
}

$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'update') {
	// 退会処理実行

	// Users 変更
	//     Users.status	：［登録済］→［退会済］
	//     ※再入会は、メールアドレスが同じでも別ユーザーとしての新規登録とみなす。
	$paramUpdate['status'] = '退会済';
	$user->updateUsers(User::$userId, $paramUpdate);

	// 強制ログアウト
	User::$userInfo['userLogin'] = $_SESSION['userLogin'] = null;
	User::$userId = $_SESSION['userId'] = null;

	// メッセージ
	// showDialog('退会が完了しました。');
	// 未ログインで閲覧可能なページにする必要があるので別ページにした。
	header("location:?p=Regist/ResignResult");
	exit;
}
