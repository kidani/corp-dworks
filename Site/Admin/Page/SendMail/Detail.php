<?php

/**
 *
 * メール配信
 *
 * @author      kidani@wd-valley.com
 *
 */

// 配信登録済みデータ取得
$sendMailId = isset($param['id']) ? $param['id'] : null;
$sendMail = new SendMail();
$dataSendMail = array();
$authEdit = null;
if ($sendMailId) {
	// 既存データの場合
	if (!$dataSendMail = $sendMail->getById($sendMailId)) {
		showError($sendMailId, 'SendMail 対象データ取得失敗');
		return;
	}
	if (!$sendMail->checkAuthEdit($dataSendMail['sendStartTime'])) {
		// 変更権限なしの場合
		$authEdit = 'disabled';
	}
}

// 変更権限
WvSmarty::$smarty->assign('authEdit', $authEdit);

//------------------------------------------------
// 配信対象ユーザーの検索条件
//------------------------------------------------
$phAnd = $bind = null;

// 登録済ユーザーのみ
$phAnd .= " AND Users.status = '登録済' ";
// ダミー会員は除外（woodvydummy1@gmail.com など）
$phAnd .= " AND Users.mailAddress not like '%woodvydummy%' ";

// メールで受信設定OFFのユーザーへの配信
if (!isset($param['includeSettingOff']) || !$param['includeSettingOff']) {
	// メールでのお知らせ受信設定：OFFのユーザーは配信対象から除外
	$phAnd .= " AND (AlertSetting.mailMagazine = 1) ";
}

// PUSH通知可能ユーザーへの配信
if (!isset($param['includePushOK']) || !$param['includePushOK']) {
	// PUSH通知可能ユーザーは配信対象から除外
	$phAnd .= " AND (Users.deviceToken is null AND Users.regToken is null)";
}

//------------------------------------------------
// 配信対象ユーザー一覧取得
//------------------------------------------------
// ユーザー数取得
//     検索条件にマッチするユーザー数合計を取得
$user = new User();
$totalCnt = $user->getUserCount($bind, $phAnd);
WvSmarty::$smarty->assign('totalCnt', $totalCnt);

// ページ設定取得
$curPageNo = isset($param['curPageNo']) ? $param['curPageNo'] : 1;
$page = wvPager::getPageInfo($curPageNo, $totalCnt, $pageName, AdminConfig::$conf);

// ソート情報設定
$sort = setSortParam($param, 'insTime', 'desc');
WvSmarty::$smarty->assign('sort', $sort);

// ユーザー一覧取得
$dataUser = $user->getPage($bind, $phAnd, $page, $sort);
WvSmarty::$smarty->assign('data', $dataUser);

// ページャ取得
$pager = wvPager::getPager($totalCnt, $page, count($dataUser), $param);
WvSmarty::$smarty->assign('pager', $pager);

// モード別処理
$mode = isset($param['mode']) ? $param['mode'] : null;
if (!$mode) {
	// データ参照・登録の場合
	if (!$sendMailId) {
		// 新規登録の場合は初期値セット
		$dataSendMail['subject'] = "保活体験談追加のお知らせ";
		$dataSendMail['body'] = $sendMail->getBodySample();
	}
	// コピーして作成
	$copyId = isset($param['copyId']) ? $param['copyId'] : null;
	if ($copyId) {
		if (!$dataCopy = $sendMail->getById($copyId)) {
			showError($sendMailId, 'SendMail コピー対象データ取得失敗');
			return;
		}
		$dataSendMail['subject'] = $dataCopy['subject'];
		$dataSendMail['body'] = $dataCopy['body'];
		$dataSendMail['includeSettingOff'] = $dataCopy['includeSettingOff'];
		$dataSendMail['includePushOK'] = $dataCopy['includePushOK'];
	}
	WvSmarty::$smarty->assign('dataForm', $dataSendMail);
} elseif ($mode === 'finish') {
	if (isset($param['message']) && $param['message']) {
		showInfo($param['message']);
	}
	WvSmarty::$smarty->assign('dataForm', $dataSendMail);
} elseif ($mode === 'updateSearch') {
	// 絞り込み実行の場合

	// フォーム入力値上書き
	WvSmarty::$smarty->assign('dataForm', $param);

} elseif ($mode === 'update' || $mode === 'sendNow') {
	// 新規登録・更新の場合

	// 登録種別
	$updateType = ($sendMailId ? '更新' : '新規登録');

	// フォーム入力値上書き
	WvSmarty::$smarty->assign('dataForm', $param);

	// 入力値チェック
	$sendMail = new SendMail();
	if ($result = $sendMail->validate($param)) {
		showWarning($result);
		return;
	}

	// DB登録
	$param['body'] = $_POST['body'];	// HTMLタグなので $_POST で変換回避
	if (!$sendMail->upsert($sendMailId, $param)) {
		showError("SendMail {$updateType}失敗");
		return;
	}

	if ($mode === 'sendNow') {
		// 今すぐ配信の場合
		$sendMail->send($sendMailId);
		$message = "ID:{$sendMailId}の配信を完了しました。";
	} else {
		if ($updateType === '新規登録') {
			$message = '配信設定の新規登録完了';
		} else {
			$message = '配信設定の更新完了';
		}
	}

	// フォーム入力値上書き
	//$dataSendMail = $sendMail->getById($sendMailId);
	//WvSmarty::$smarty->assign('dataForm', $dataSendMail);

	// リロード更新回避
	header("Location:?p=SendMail/Detail&id={$sendMailId}&mode=finish&message={$message}");
	exit;

} elseif ($mode === 'sendTest') {
	// テスト配信実行の場合

	// フォーム入力値上書き
	WvSmarty::$smarty->assign('dataForm', $param);

	// 入力値チェック
	$sendMail = new SendMail();
	if ($result = $sendMail->validate($param)) {
		showWarning($result);
		return;
	}

	// 送信対象ユーザーID
	$phAnd = $bind = null;
	$sendTestUserId = isset($param['sendTestUserId']) ? $param['sendTestUserId'] : null;
	if (!$sendTestUserId) {
		showWarning("テスト配信対象となるユーザーIDを指定して下さい。");
		return;
	}

	// テスト配信実行
	$param['body'] = $_POST['body'];	// HTMLタグなので $_POST で変換回避
	$sendMail->sendTest($param, $sendTestUserId);
	showInfo("テスト配信を完了しました。");

} elseif ($mode === 'csvDownload') {
	// CSVダウンロードの場合

	// メール配信対象リスト取得（全件）
	$dataUser = $user->getListForMail($bind, $phAnd);
	if (!$dataUser) return;

	// ヘッダ
	// 以下はMailPublisher のアップロード用CSVのフォーマット
	// 文字コードUTF-8、ヘッダ必須なので注意！
	$header = array(
		'email',		// メールアドレス
		'nickname',		// ニックネーム
		'userId'		// ユーザーID
	);
	// ファイル出力
	$fileName = '../Tmp/temp.csv';
	$output = fopen($fileName, 'w+');
	if ($output){
		fputcsv($output, $header);
		foreach ($dataUser as $key => $value) {
			fputcsv($output, $value);
		}
	}

	// 改行変換（LF → CRLF）
	rewind($output);
	$buf = str_replace("\n", "\r\n", stream_get_contents($output));
	// 文字コード変換（UTF-8 → SJIS）
	$buf = mb_convert_encoding($buf, 'SJIS', 'UTF-8');

	fclose($output);

	$fp = fopen($fileName, 'w');
	fwrite($fp, $buf);
	fclose($fp);

	// ダウンロード
	header('Content-Type:application/octet-stream');
	header('Content-Disposition:attachment; filename=UserMailList.csv');
	header('Content-Transfer-Encoding:binary');
	header('Content-Length:' . filesize($fileName));
	readfile($fileName);

	exit;
}

