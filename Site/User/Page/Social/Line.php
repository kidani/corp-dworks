<?php

/**
 *
 * Line メッセージ送信
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// メッセージ送信は無料配信数が少ないので当面は非対応。
// Official Account Manager から手動でタイムラインに投稿する。

// 管理者以外のアクセス不可
if (User::$userInfo['mailAddress'] !== 'kidani@wd-valley.com'){
	showError('アクセス不可');
	return;
}