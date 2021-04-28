<?php

// $userId = '001vn82ibcv6vcaiict6rcq583';   // kidani@wd-valley.com
// $userId = 'ic6e0m99usbqigci8p1om31hq3';   // kidani39@gmail.com
// $targetInfo = $user->getById($userId);

//$message = '';
//$targetInfo = User::$userInfo;

//if (isset($param['targetOs'])) {
//
//	if ($param['targetOs'] === 'ios') {
//		if (!User::$userInfo['deviceToken']) {
//			showInfo('宛先の deviceToken 未設定');
//			return;
//		}
//		$targetInfo['deviceToken']	= User::$userInfo['deviceToken'];
//		$targetInfo['regToken'] = null;
//	} elseif ($param['targetOs'] === 'android') {
//		if (!User::$userInfo['regToken']) {
//			showInfo('宛先の regToken 未設定');
//			return;
//		}
//		$targetInfo['regToken']	= User::$userInfo['regToken'];
//		$targetInfo['deviceToken'] = null;
//	}
//
//	if ($targetInfo['deviceToken'] || $targetInfo['regToken']) {
//
//		$addCnt = 0;     // テスト用にカウントアップ
//		$push = new Push();
//		$badgeNumber = $targetInfo['taskCnt'] + $targetInfo['alertCnt'] + $addCnt;
//
//		// リモート通知
//		$message = 'テストPUSHメッセージ';
//		$push->pushRemote($targetInfo, $message, intval($badgeNumber));
//
//		// サイレント通知
//		// $push->pushRemote($targetInfo, '', 2);
//	}
//}
//WvSmarty::$smarty->assign('pushMessage', $message);
