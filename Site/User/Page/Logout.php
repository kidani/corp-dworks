<?php
/**
 *
 * ログアウト画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// ネイティブの場合
//     アクセスした時点でUUIDがあればログイン状態にしている。
//     ログアウト時にUUIDを削除しても、すぐにUUIDを取得してしまう
//     仕様になっているためログアウト状態にするのは相当面倒ということで
//     とりあえず断念した。なのでネイティブは一旦ログインすると、
//     アプリをアンインストールするまで再ログインはできない仕様になる。
//     複数アカウントの所持を禁止するのであればこれで問題ないはず。
/*
if (NATIVE) {
	if (PF ==='ios') {
		$paramUpdate['iosUid'] = null;	
	} elseif (PF ==='android') {		
		$paramUpdate['androidUid'] = null;	
	}		
	$user->updateUsers(User::$userId, $paramUpdate);
	if (WV_DEBUG) debug($paramUpdate, 'UUID リセット $paramUpdate');
}
*/

// セッション変数を全て解除
//     セッション変数を全て解除しても再発行しないとログアウト後の
//     セッションIDが同じになるようなので注意。
$_SESSION = array();
session_destroy();
header("Location:" . SITE_URL);
exit;
