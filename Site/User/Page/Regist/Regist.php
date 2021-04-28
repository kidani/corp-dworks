<?php

/**
 *
 * 会員登録画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// ユーザー情報
if (User::$userInfo['userLogin']) {
	// ログイン済みの場合、既存値をセット
	$info = User::$userInfo;
	$info['pass'] = null;    // 初期画面ではパス非表示にするためクリア。
	WvSmarty::$smarty->assign('data', $info);
} else {
	$info['mailAddress'] = null;
	$info['passCurrent'] = null;
	$info['pass'] = null;
	$info['passConfirm'] = null;
	WvSmarty::$smarty->assign('data', $info);
}

// メアド更新フラグ
$mailUpdateFlg = false;

// モード
$mode = isset($param['mode']) ? $param['mode'] : null;
if ($mode && !User::$userInfo['userLogin']) {
	// 未ログインなら新規登録
	$mode = 'add';
}
if ($mode === 'update') {
	// 更新の場合

	// 入力パラメータで上書き
	WvSmarty::$smarty->assign('data', $param);

	// 入力チェック
	$currentPass = isset(User::$userInfo['pass']) ? User::$userInfo['pass'] : null;

	if (!$currentPass) {
		// 現在のパスワードなければ新規登録
		showError(User::$userInfo, 'ユーザー情報更新で現在のパスワードなし検知');
		return;
	}
	if ($errMessage = $user->validate($param, $currentPass)) {
		showWarning($errMessage);
		return;
	}
	$paramDb = $param;

	// メールアドレス、トークン
	$mailAddressNew = isset($param['mailAddress']) ? $param['mailAddress'] : null;
	$mailAddressCur = User::$userInfo['mailAddress'];
	if ($mailAddressNew && $mailAddressNew !== $mailAddressCur) {
		// mailAddressNew に仮設定しておいて、後で認証が成立したら mailAddress として登録する。
		$mailUpdateFlg = true;
		unset($paramDb['mailAddress']);    // 現在のパスワードはまだ更新しない！
		$paramDb['mailAddressNew'] = $mailAddressNew;
		$paramDb['token'] = Token::generate();
		$paramDb['tokenMakeTime'] = date('Y-m-d H:i:s');
	}

	// パスワード更新フラグ
	$passUpdateFlg = isset($param['passChageCheck']) ? true : false;
	if (!$passUpdateFlg) {
		// パスワード更新なしの場合
		if (isset($paramDb['pass'])) unset($paramDb['pass']);
		if (isset($paramDb['passCurrent'])) unset($paramDb['passCurrent']);
		if (isset($paramDb['passConfirm'])) unset($paramDb['passConfirm']);
	}

	// ユーザー情報更新
	if ($mailUpdateFlg || $passUpdateFlg) {
		if (!$user->updateUsers(User::$userId, $paramDb)) {
			showError($paramDb, 'ユーザー情報更新失敗');
			return;
		}
		if ($passUpdateFlg) {
			// 登録結果メッセージ
			showInfo('パスワードを更新しました。');
		}
		if ($mailUpdateFlg) {
			// メールは認証後に会員情報の更新が確定する。
			showInfo('認証メールを送信しました。');
		}
	} else {
		showInfo('更新はありません。');
	}

} elseif ($mode === 'add') {
	// 新規登録の場合

	// 入力パラメータで上書き
	WvSmarty::$smarty->assign('data', $param);

	// 入力チェック
	if ($errMessage = $user->validate($param, null)) {
		showWarning($errMessage);
		return;
	}

	// メールアドレス新規登録
	$mailUpdateFlg = true;
	// メールアドレス認証用トークン発行
	$token = Token::generate();

	// 会員ステータス
	$status = isset(User::$userInfo['status']) ? User::$userInfo['status'] : null;
	if ($status === '登録済') {
		// ユーザーにエラーメッセージを表示する。
		showError(null, '既に登録済みです。ログインしてご利用下さい。', false, false);
		return;
	} elseif ($status === '仮登録') {
		// 既に仮録済みの場合
		// メールアドレス、パスワード、トークンのみ更新
		$paramDb['mailAddressNew'] = $param['mailAddress'];
		$paramDb['pass'] = $param['pass'];

		if (ENV_ID === 'Prd') {
			// FIXME：myfishing の Finish.php で「不正なメール認証リクエスト検知」が頻発
			// $paramDb['token'] = $token;
			$paramDb['token'] = User::$userInfo['token'];		// エラー回避のため同じもので上書き
			$paramDb['tokenMakeTime'] = date('Y-m-d H:i:s');	// 12時間制限を回避するために更新
		} else {
			// 開発ではトークンを変更して調査継続中
			if (WV_DEBUG) trace("再発行したトークンをセット：{$token}");
			$paramDb['token'] = $token;
			$paramDb['tokenMakeTime'] = date('Y-m-d H:i:s');	// 12時間制限を回避するために更新
		}

		if (!$user->updateUsers(User::$userId, $paramDb)) {
			showError($paramDb, '仮登録済みユーザーの仮登録情報更新失敗');
			return;
		}
	} else {
		// ユーザー情報新規登録
		$paramDb = $param;
		$paramDb['token'] = $token;
		if (!$user->add($paramDb)) {
			showError($paramDb, 'ユーザー情報新規登録失敗');
			return;
		}
	}

	// 登録結果メッセージ
	showInfo(array('会員情報の仮登録を完了しました。'));
}

// メアド更新
WvSmarty::$smarty->assign('mailUpdate', $mailUpdateFlg);

//------------------------------------------------
// 認証メール送信
//------------------------------------------------
if (SiteConfig::$conf['Debug']['sendMail'] && $mailUpdateFlg) {
	// 本文に渡す変数
	$mail = new Mail();
	$mail->setBaseInfo($mailData, SiteConfig::$conf);
	$subject = "［{$mailData['siteName']}］登録メールアドレスの確認";
	$mailData['mailAddress'] = $param['mailAddress'];
	$mailData['finishUrl'] = "{$mailData['siteUrl']}?p=Regist/Finish&key={$paramDb['token']}";
	$template = WVFW_ROOT . "/Site/Doc/Mail/MailRegAuth.txt";
	if (!$mail->sendMail($mailData, $mailData['fromAddress'], $mailData['mailAddress'], $template, $subject)) {
		showError($mailData, '会員登録確認メール送信失敗');
		return;
	}
}

