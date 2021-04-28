<?php

/**
 *
 * ユーザー詳細
 *
 * @author      kidani@wd-valley.com
 *
 */

// ユーザー情報
$userId = isset($param['userId']) ? $param['userId'] : null;
if (!$userId) {
	showError($param, 'エラー：$userId が取得できません。');
	return;
}
$user = new User();
$data = $user->getById($userId);
if (!$data) {
	showError($param, 'ユーザー情報なし');
	return;
}
WvSmarty::$smarty->assign('data', $data);

// 送付先住所
//require_once (WVFW_ROOT . '/Site/Core/Address.php');
//$address = new Address();
//$address = $address->getByUserId($userId);
//WvSmarty::$smarty->assign('address',  $address);

$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode === 'finish') {
	showInfo($param['message']);
	return;
} elseif ($mode === 'update') {
	// 更新の場合
	if (isset($param['memo']) && $data['memo'] !== $param['memo']) {
		// メモ
		$paramUpdate['memo'] = $param['memo'];
		$user->updateUsers($userId, $paramUpdate);
		$message = 'メモを登録しました。';
	} elseif (isset($param['restriction'])) {
		// 操作制限（不正対策）
		// メモ
		if (!$data['memo']) {
			// 強制退会済にする前にメモ保存必須。
			$message = '操作制限を変更する前に、操作制限した経緯などについてメモを保存して下さい。<br>'
				. 'メモは後からでも修正できます。';
			showWarning($message);
			return;
		}
		$paramUpdate['restriction'] = $param['restriction'];
		$user->updateUsers($userId, $paramUpdate);
		$message = '操作制限を登録しました。';
	} elseif (isset($param['status']) && $param['status']) {
		// 会員ステータス

		// 入力チェック
		if (!isset($param['status']) || $param['status'] !== '強制退会済') {
			// 現状「強制退会済」以外へのステータス変更なし。
			showError($param, '更新対象データ不正');
			return;
		}

		// メモ
		if (!$data['memo']) {
			// 強制退会済にする前にメモ保存必須。
			$message = '強制退会済にする前に、強制退会済した経緯などについてメモを保存して下さい。<br>'
				. 'メモは後からでも修正できます。';
			showWarning($message);
			return;
		}

		// 会員ステータスを「強制退会済」に変更。
		$paramUpdate['status'] = $param['status'];
		$result = $user->updateUsers($userId, $paramUpdate);
		$message = 'ユーザーの会員ステータスを「強制退会済」に、出品した施設で「売切れ」以外の販売状態を「強制削除済」に変更しました。';
	}

	// 更新データ取得
	$data = $user->getById($userId);
	WvSmarty::$smarty->assign('data', $data);  		// 上書き

	// 重複更新ガード
	header("location:?p=Support/User&userId={$userId}&mode=finish&message={$message}");
	exit;
}



















