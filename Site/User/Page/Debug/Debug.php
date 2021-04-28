<?php

/**
 *
 * テスト画面
 *
 * デバッグ用、稼働中のサイトでは閲覧不可。
 *
 *
 */

require_once (WVFW_ROOT . '/Site/User/Core/Debug.php');

// 開発環境以外無効
if (!WV_DEBUG) return;

// 削除
if (isset($param['delete'])) {
	if ($param['delete'] === 'session' || $param['delete'] === 'all') {
		// セッション削除
		if ($param['key'] === 'all') {
			resetSession();
			if (WV_DEBUG) debug('全セッションを削除しました。');
			showInfo('全セッションを削除しました。');
			// WvSmarty::$smarty->assign('message', '全セッションを削除しました。');
		} else {
			resetSession($param['key']);
			WvSmarty::$smarty->assign('message', "セッション：{$param['key']}を削除しました。");
		}
	}
	if ($param['delete'] === 'cookie' || $param['delete'] === 'all') {
		// クッキー削除
		if ($param['key'] === 'all') {
			resetCookie();
			if (WV_DEBUG) debug('全クッキーを削除しました。');
			showInfo('全クッキーを削除しました。');
		} else {
			resetCookie($param['key']);
			WvSmarty::$smarty->assign('message', "クッキー：{$param['key']}を削除しました。");
		}
	}
}

// セッション
WvSmarty::$smarty->assign('SESSION', $_SESSION);

// クッキー
WvSmarty::$smarty->assign('COOKIE', $_COOKIE);




