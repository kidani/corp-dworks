<?php

/**
 *
 * ニュース詳細画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

$newsId = isset($param['id']) ? $param['id'] : null;
if (!$newsId) {
	$newsId = isset($param['newsId']) ? $param['newsId'] : null;
	if ($newsId) {
		// URL変更を通知
		header("Location:?p=News/Detail&id={$newsId}", true, 301);
		exit;
	}
	// 確実に悪質BOTなので404扱い
	//showError(setLogDetail(), 'newsId 取得失敗');
	//return;
	header("HTTP/1.0 404 Not Found");
	exit;
}
$news = new News();
$data = $news->getById($newsId);
if (!$data) {
	showError($param, 'ニュースデータ取得失敗');
	return;
}
WvSmarty::$smarty->assign('data', $data);

