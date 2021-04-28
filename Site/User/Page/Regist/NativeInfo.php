<?php

/**
 *
 * ネイティブアプリ関連情報登録
 *
 * アプリからの直接通信で呼ばれる。
 * 通常のブラウザ間通信と違うので注意！
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// ユーザーがDB登録される前にこちらに来る場合もあるが、逆もあるので注意！
// 通常のブラウザ間通信と違うのでセッションは使わないこと！（使えないのは確認済み）
// ウェブビューからのアクセスとアプリからの直接通信アクセスがあり
// セッションを使った場合、別セッションになると思われる。
// 通常のサーバ間通信と同様の扱いが必要？

// ユーザー情報更新
// 現状はデバイストークン更新のみだが、
// UsersTemp 未登録の場合、新規登録も行っている点に注意！

if ($PF === 'ios') {
	if (WV_DEBUG) debug($param, 'iOS デバイストークン取得発生');
	$user = new UserIos();
	$userId = $user->updateDeviceToken($param['iosUid'], $param);
	echo json_encode(array('result' => 'success'));
} elseif ($PF === 'android') {
	if (WV_DEBUG) debug($param, 'Android 端末登録トークン取得発生');
	$user = new UserAnd();
	$userId = $user->updateRegToken($param['androidUid'], $param);
	$result = $userId ? 'success' : 'error';
	$response = array("userId" => $userId, "result" => $result);
	$response = json_encode($response);
	if (WV_DEBUG) debug($response, '$response');
	echo ($response);
} else {
	if (WV_DEBUG) debug($param, 'web からの NativeInfo アクセス検知');
	echo json_encode(array('result' => 'web からの NativeInfo アクセス検知'));
}
exit;

