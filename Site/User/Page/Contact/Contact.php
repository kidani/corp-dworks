<?php

/**
 *
 * 問合せフォーム画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Core/Contact.php');

// モード
$mode = isset($param['mode']) ? $param['mode'] : null;
WvSmarty::$smarty->assign('mode', $mode);

if ($mode === 'finish') {
	return;
} elseif ($mode === 'send') {
	// 送信実行

	// パラメータ上書き
	WvSmarty::$smarty->assign('data', $param);

	// 入力チェック
	$contact = new Contact();
	if ($errMessage = $contact->validate($param)) {
		showWarning(array($errMessage));
		return;
	}

	// セキュリティーチェック（IP判定）
	// IP判定による除外 → イタチごっこなのでコメント
	//if (isset($SITE_CONFIG['Security']['ipBlock'])
	//	&& in_array($_SERVER['REMOTE_ADDR'], $SITE_CONFIG['Security']['ipBlock'])) {
	//	checkLog($_SERVER['REMOTE_ADDR'], '不正IPからの問合せ検知');
	//	header("HTTP/1.0 404 Not Found");
	//	exit;
	//}

	// セキュリティーチェック（言語判定）
	// 英語の迷惑問合せが多いので、半角英数のみなら除外する。
	//$detail = $param['detail'];
	//$len = mb_strlen($detail, "UTF-8");				// バイト長取得
	//$wdt = mb_strwidth($detail, "UTF-8");			// 文字幅取得
	//if($len === $wdt) {
	//	// 半角のみの場合
	//	if (WV_DEBUG) debug($_SERVER['REMOTE_ADDR'] . "\n" . $param['detail'], '半角英数記号のみの問合せ検知');
	//	checkLog($_SERVER['REMOTE_ADDR'] . "\n" . $param['detail'], '半角英数記号のみの問合せ検知');
	//	header("HTTP/1.0 404 Not Found");
	//	exit;
	//}

	// セキュリティーチェック（Google reCAPTCHA）
	$recaptcha = $param["g-recaptcha-response"];
	if (!isset($recaptcha) || !$recaptcha){
		// showError(setLogDetail(), 'Google reCAPTCHA 取得失敗検知');
		trace("{$param['namae']} {$param['mailAddress']} Google reCAPTCHA 認証エラー検知", CHECK_LOG_FILE);
		header("HTTP/1.0 404 Not Found");
		exit;
	}

	$secretKey = "6LdatK0aAAAAAMiucrYw_XXZ64yhA0wj_nUKxCj3";
	$result = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptcha}");
	if (WV_DEBUG) debug($result, 'Google reCAPTCHA レスポンス');
	$result = json_decode($result, true);
	if (intval($result["success"]) !== 1) {
		// 認証失敗の場合
		errorLog('Google reCAPTCHA レスポンスエラー');
		showWarning("認証エラー");
		return;
	}

	//------------------------------------------------
	// メール送信
	//------------------------------------------------
	$mailData = array();
	$mail = new Mail();
	$mail->setBaseInfo($mailData, SiteConfig::$conf, User::$userInfo);
	$subject = "［{$mailData['siteName']}］へのお問い合わせ";
	$mailData = array_merge($mailData, $param);	// 入力フォーム値をセット

	// システム管理元へ送信
	//$template = WVFW_ROOT . "/Site/Doc/Mail/Contact/ToSystemAdmin.txt";
	//if (!$mail->sendMail($mailData, $mailData['fromAddress'], SiteConfig::$conf['Mail']['toSystemAdmin'], $template, $subject)) {
	//	showError('システム管理元への問合せメール送信失敗');
	//	return;
	//}

	// 運用元へ送信
	$template = WVFW_ROOT . "/Site/Doc/Mail/Contact/ToAdmin.txt";
	if (!$mail->sendMail($mailData, $mailData['fromAddress'], SiteConfig::$conf['Mail']['toAdmin'], $template, $subject)) {
		showError('運用元への問合せメール送信失敗');
		return;
	}

	// ユーザーへ送信
	$template = WVFW_ROOT . "/Site/Doc/Mail/Contact/ToUser.txt";
	if (!$mail->sendMail($mailData, $mailData['fromAddress'], $param['mailAddress'], $template, $subject)) {
		showError('ユーザーへの問合せメール送信失敗');
		return;
	}

	// 重複更新ガード
	header("location:?p=Contact/Contact&mode=finish");
	exit;
} else {
	// 初期表示
	$data['namae'] = null;
	$data['detail'] = null;
	$data['mailAddress'] = null;
	if (User::$userInfo['userLogin']) {
		// ログイン済みならメールアドレスをセット
		$data['mailAddress'] = User::$userInfo['mailAddress'];
	}
	WvSmarty::$smarty->assign('data', $data);
}

