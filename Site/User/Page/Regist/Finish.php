<?php

/**
 *
 * メール認証完了画面
 *
 * 新規登録時の場合、メール認証により会員登録完了となる。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// トークンから認証対象のユーザー情報取得
$token = isset($param['key']) ? $param['key'] : null;
if (!$token) {
	showError($param, 'key（token）取得失敗');
	return;
}
$data = $user->getByToken($token);
if (!$data) {
	// ユーザー画面上は「メールアドレスの認証が正常に終了しませんでした。」と表示。
	// showError(setLogDetail(), '不正なメール認証リクエスト検知');
	return;
}
$userId = $data['userId'];

// 更新種別（新規登録／更新）
$updateType = $data['status'] === '仮登録' ? '新規登録' : '更新';

if ($data['status'] === '登録済' && !$data['mailAddressNew']) {
	// 既に会員登録済み
	// key（token）は会員登録完了直後に削除されるので通常通らないはず！
	WvSmarty::$smarty->assign('registResult',  'statusMember');
	return;
}
WvSmarty::$smarty->assign('data',  $data);

// 既にログイン状態の場合
if (User::$userInfo['userLogin']) {
	if ($userId !== User::$userId) {
		// メール認証対象のユーザー情報とマッチしない場合
		showError('メール認証不整合検知');
		return;
	}
}

// 認証の有効時間チェック
$makeTime = strtotime("{$data['tokenMakeTime']} +12 hour");
if ($makeTime < strtotime("now")) {
	// 12時間過ぎていたら無効
	WvSmarty::$smarty->assign('registResult', 'timeOver');
	return;
}

// 変更後のメールアドレスチェック
if (!$data['mailAddressNew']) {
	showError($token, '登録するメールアドレスなし');
	return;
}

// メールアドレス重複チェック
//     認証メール送信後に、別ユーザーが認証待ちのメールアドレスと同じアドレスを
//     登録してしまった場合に、認証エラーとする必要がある。
$checkData = $user->getUserByMailAddress($data['mailAddressNew']);
if ($checkData && $checkData['mailAddress'] === $data['mailAddressNew'] && $checkData['status'] === '登録済') {
	showWarning('認証に失敗しました。<br>別のメールアドレスで登録して下さい。');
	errorLog($checkData, 'メールアドレス重複エラー発生');
	return;
}

// ユーザー情報更新
$paramUpdate['mailAddress'] = $data['mailAddressNew'];
$paramUpdate['mailAddressNew'] = '';
$paramUpdate['status'] = '登録済';
// $paramUpdate['token'] = null;
$paramUpdate['tokenMakeTime'] = '';
if (!$user->updateUsers($userId, $paramUpdate)) {
	showError('ユーザー情報更新失敗');
	return;
}

// 新規登録・更新の結果フラグ
WvSmarty::$smarty->assign('registResult',  'success');
// 新規登録フラグ（広告コンバージョン測定用）
WvSmarty::$smarty->assign('updateType',  '$updateType');

// 新規登録時処理
if ($updateType === '新規登録') {
	//------------------------------------------------
	// ユーザー設定の事前追加
	//------------------------------------------------
	// 既に登録済みかチェック
	$alertSetting = new AlertSetting();
	$dataAlert = $alertSetting->getByUserId($userId);
	if ($dataAlert) {
		// 仮登録で新規登録を再実行した場合に通るルート
		// 既に登録済みなので何もしない。
	} else {
		if (!$alertSetting->add($userId)) {
			showError('お知らせ通知設定登録失敗');
			return;
		}
	}
}

// 強制ログアウト
//     変更前のメールアドレスでログインしたままだとまずいので
//     変更後のメールアドレスで再ログインさせる。
User::$userInfo['userLogin'] = $_SESSION['userLogin'] = null;
User::$userId = $_SESSION['userId'] = null;

