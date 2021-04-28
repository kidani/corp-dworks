<?php

/**
 *
 * ニュース詳細画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// ニュース取得
$news = new News();
$dataNews = array();
$newsId = isset($param['id']) ? $param['id'] : null;
if ($newsId) {
	if (!$dataNews = $news->getById($newsId)) {
		// 念のためデータ存在確認
		showError($newsId, 'News 対象データ取得失敗');
		return;
	}
}

// モード別処理
$mode = isset($param['mode']) ? $param['mode'] : null;
if (!$mode) {
	// データ参照・登録の場合
	if (!$newsId) {
		// 新規登録の場合は初期値セット
		$dataNews['title'] = null;
		$dataNews['detail'] = null;
		$dataNews['openTime'] = date("Y-m-d") . ' 00:00';
		$dataNews['closeTime'] = null;
	}
	WvSmarty::$smarty->assign('dataForm', $dataNews);
} elseif ($mode === 'finish') {
	if (isset($param['message']) && $param['message']) {
		showInfo($param['message']);
	}
	WvSmarty::$smarty->assign('dataForm', $dataNews);
} elseif ($mode === 'update') {
	// 新規登録・更新の場合

	// 登録種別
	$updateType = ($newsId ? '更新' : '新規登録');

	// フォーム入力値上書き
	WvSmarty::$smarty->assign('dataForm', $param);

	// 入力値チェック
	if ($result = $news->validate($param)) {
		showWarning($result);
		return;
	}

	// DB登録
	if (!$news->upsert($newsId, $param)) {
		showError("News {$updateType}失敗");
		return;
	}

	if ($updateType === '新規登録') {
		// リロード更新回避
		header("Location:?p=News/Detail&id={$newsId}&mode=finish&message=新規登録完了");
		exit;
	} else {
		// フォーム入力値上書き
		$dataNews = $news->getById($newsId);
		WvSmarty::$smarty->assign('dataForm', $dataNews);
		showInfo("News 更新完了");
	}
}












