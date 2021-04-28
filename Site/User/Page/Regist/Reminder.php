<?php

/**
 *
 * パスワードリマインダ画面
 *
 * @author      kidani@wd-valley.com
 *
 */

// メールアドレス
$mailAddress = isset($param['mailAddress']) ? $param['mailAddress'] : null;
WvSmarty::$smarty->assign('mailAddress', $mailAddress);

// テンプレート表示制御
$mode = isset($param['mode']) ? $param['mode'] : null;
WvSmarty::$smarty->assign('mode', $mode);

if (!$mode) {
	// 初期入力
 	return;
} elseif ($mode === 'finish') {
	// 送信完了時リロード対策
	WvSmarty::$smarty->assign('mode', 'finish');
	return;	
} elseif ($mode !== 'result') {
	return;	
}

// mode：result の場合のみ以下の処理を実行
WvSmarty::$smarty->assign('mode', 'result');

// メールアドレス入力チェック
if (!$mailAddress) {
	showWarning('メールアドレスが未入力です。');
	return;
}

// ユーザー情報取得
$data = $user->getUserByMailAddress($mailAddress);
if ($data) {
	
	// パスワード再発行
	//     MD5をパスワード解読はできないので、再発行 → 任意に変更してもらう流れ。
	$paramUpdate['pass'] = makeRandString(8);
	if (!$user->updateUsers($data['userId'], $paramUpdate)) {
	    showError('Users 更新失敗');
	    return;
	}

	//------------------------------------------------
	// パスワード再発行通知メール送信
	//------------------------------------------------
	if (SiteConfig::$conf['Debug']['sendMail']) {
		$mailData = null;
		$mail = new Mail();
		$mail->setBaseInfo($mailData, SiteConfig::$conf);
		$subject = "［{$mailData['siteName']}］パスワード再発行通知";
		$mailData['mailAddress'] = $mailAddress;
		$mailData['password'] = $paramUpdate['pass'];
		$mailData['loginUrl'] = "{$mailData['siteUrl']}?p=Login";
		$template = WVFW_ROOT . "/Site/Doc/Mail/PasswordReminder.txt";
		if (!$mail->sendMail($mailData, $mailData['fromAddress'], $mailData['mailAddress'], $template, $subject)) {
		    showError('会員登録確認メール送信失敗。');
		    return;
		}
	}
} else {
	// ユーザー登録なし
	showWarning('未登録のメールアドレスです。');
	return;
}

// リロード対策
header("location:?p=Regist/Reminder&mailAddress={$mailAddress}&mode=finish");
exit;










































