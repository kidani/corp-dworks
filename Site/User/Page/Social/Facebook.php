<?php

/**
 *
 * Facebook のログインと投稿
 *
 * 本番環境以外では実行できない点に注意。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Core/Sns.php');
require_once (WVFW_ROOT . '/Lib/Social/php-graph-sdk/src/Facebook/autoload.php');
//session_start();          // index.php で開始済みなので不要

// 管理者以外のアクセス不可
if (User::$userInfo['mailAddress'] !== 'kidani@wd-valley.com'){
	showError('アクセス不可');
	return;
}

// 開発（HTTPS必須なので利用不可）
// $pageAccessToken = '';
//$fb = new Facebook\Facebook(array(
//		'app_id'                    => '623673564765668',
//		'app_secret'                => '15d7b541e093fec7a4ce6ee5667c3cd2',
//		'default_graph_version'     => 'v3.3'
//	)
//);
// 本番
// ページアクセストークン：「有効期限：受け取らない」なので無期限かも。
$pageAccessToken = 'EAAJFBvY6oZCwBAFCOtqCFDsjyNxZBFpyO97LYeTSNjKV8nZBhVz7Hr7qW4i8DT0jYi5pVsTtn2DjYdW5L8sFjUI8i30tNZCzSPWW7mYmKwgZA6b204CCJQUHAPMfPJzMR6QfiBXtxuQpqqeZAZCTuSZC1jI5KGj9Q4y5r7AcWFNC0RU3YfiiGLGN';
$fb = new Facebook\Facebook(array(
		'app_id'                    => '638846156579836',					// アプリID
		'app_secret'                => '913eb470663996aa2ec71f1d5b4a0c68',	// app secret
		'default_graph_version'     => 'v3.3'								// Graph API バージョン（2019/06時点の最新）
	)
);

// リダイレクトURL
// Facebookログインの［有効なOAuthリダイレクトURI］に指定したURL
// URLは完全一致必須でパラメータは不可なので注意。パラメータを［有効なOAuthリダイレクトURI］と
// 全く同じにすれば通るが動作不安定だし、Twitter では完全にパラメータNGみたいなので使わないこと。
// $redirectUrl = "https://hokatsu-navi.com/?p=Social/Facebook";    // パラメータは不可
// $redirectUrl = "https://hokatsu-navi.com/?p=Social%2FFacebook";  // これだと通るが使わないこと！
$redirectUrl = "https://hokatsu-navi.com/Social/Facebook/";         // これならOK！.htaccess で「?p=Social/Facebook」に変換している。
$mode = isset($param['mode']) ? $param['mode'] : null;
$code = isset($param['code']) ? $param['code'] : null;

if($mode === 'login') {
	// ログイン実行の場合（◆通常は $mode === 'feed' を使えばいいので不要）

	if(ENV_ID !== 'Prd'){
		// facebook の場合 https 以外は拒否される。
		// ログイン処理なしで取得済みのページアクセストークンを使う場合は問題ない。
		showError('本番以外での facebook ログイン不可');
		return;
	}

	// 「保活なび」の facebook ページ管理アカウント：kidani39@gmail.com でログインする。
	// $loginUrl にリダイレクトするとダイアログが出て対話を進めると保活なびとFacebookがリンクされる。
	// ここを通る前にログイン済みか判定してログイン済みならそのまま（対話ダイアログなしで）
	// 投稿させてたいところだがPHPでは判定できないみたい。（JSではできるぽい）
	$helper = $fb->getRedirectLoginHelper();
	$loginUrl = $helper->getLoginUrl($redirectUrl, array('manage_pages', 'publish_pages'));
	header("Location:{$loginUrl}");
	exit;
} elseif($code) {
	// ログイン成功後のリダイレクト（◆通常は $mode === 'feed' を使えばいいので不要）
	if (WV_DEBUG || DEBUG_IP) trace('リダイレクトからの投稿処理開始');
	// ユーザーアクセストークン取得（有効期限60分の User Access Token）
	$userAccessToken = null;
	try {
		// getAccessToken の引数はなしになっているサイトが多いが、何故か［有効なOAuthリダイレクトURI］を付与しないと
		// エラー「URLを読み込めません: このURLのドメインはアプリのドメインに含まれていません。... 」になり超はまった。
		// 参考URL：https://warpbutton.com/blog/tips/259/
		// 発行されたページアクセストークンをアクセストークンデバッガーでチェックすると
		// 何故か「有効期限：受け取らない」になってる。（無期限ページアクセストークンだとそうなるらしい）
		$helper = $fb->getRedirectLoginHelper();
		// $userAccessToken = $helper->getAccessToken();                        // エラー
		$userAccessToken = $helper->getAccessToken($redirectUrl);               // OK
		if (WV_DEBUG || DEBUG_IP) debug($userAccessToken, '$userAccessToken');
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		showError($e->getMessage(), 'FacebookResponseException');
		return;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		showError($e->getMessage(), 'FacebookSDKException');
		return;
	}
	if (!$userAccessToken) {
		showError($param, 'ユーザーアクセストークン取得失敗');
		return;
	}
	// ページアクセストークン取得（Page Access Token）
	$pageAccessToken = null;
	$dataAccounts = $fb->get('/me/accounts', $userAccessToken);		// 全Facebookページの情報取得
	// if (WV_DEBUG || DEBUG_IP)debug($dataAccounts, '$dataAccounts');
	// 発行されたページアクセストークンをアクセストークンデバッガーでチェックすると
	// 「有効期限：受け取らない」になってるので無期限のページアクセストークンとしても使えるかも？
	// Graph APIエクスプローラ以外にライブラリで無期限のページアクセストークンの取得方法を記載している
	// サイトが見当たらないのでこの方法が有効化どうかは検証が必要。
	$body = $dataAccounts->getDecodedBody();
	// if (WV_DEBUG || DEBUG_IP)debug($body, '$body');
	foreach ($body['data'] as $key => $value) {
		// 何故かダイレクトに取得する方法がないのでループして特定
		if ($value['id'] === '318042512422977') {
			// 318042512422977：保活なびの facebook ページID
			// 保活なびアプリ（本番）の紐付けはライブラリからのアクセスや
			// Graph APIエクスプローラからのアクセスにより自動的にされるみたい。
			$pageAccessToken = $value['access_token'];
			break;
		}
	}
	if (!$pageAccessToken) {
		showError($param, 'ページアクセストークン取得失敗');
		return;
	}
	// 配信対象施設データ取得
	$snsPost = new SnsPost();
	$data = $snsPost->getTargetList();
	if (!$data) {
		showWarning("配信対象施設データがありません。");
		return;
	}
	$siteUrl = SITE_URL;
	foreach ($data as $key => $value) {
		// usleep(1000000);		// 1秒
		$schoolId = $value['schoolId'];
		$message = "保育施設情報に{$value['prefName']}{$value['cityName']}の「{$value['title']}」が追加されました。";
		$postData = array(
			'message'	=> $message, 											// メッセージ
			'link'		=> "{$siteUrl}?p=Review/Review&schoolId={$schoolId}",	// リンク
		);
		try{
			$fb->post('/me/feed', $postData, $pageAccessToken);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			showError($e->getMessage(), 'FacebookResponseException');
			return;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			showError($e->getMessage(), 'FacebookSDKException');
			return;
		}
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の投稿成功");
		// sendTime 追加
		$snsPost->updateSendTime($schoolId);
	}
	if (WV_DEBUG || DEBUG_IP) trace('リダイレクトからの投稿処理終了');
	showInfo("facebook への投稿成功数：" . count($data) . "件");
	return;
} elseif($mode === 'feed' && $pageAccessToken) {
	// ログイン処理なしで取得済みのページアクセストークンを使う場合（◆通常はこちらを使う）
	// 事前に Graph APIエクスプローラから、手動で無期限のページアクセストークン（Page Access Token）
	// を取得して、$pageAccessToken にセットしておく必要あり。
	// https://gist.github.com/xl1/fe779a817a9d4938193d
	// https://hirofukami.com/2017/06/30/facebook-page-access-token/
	if (WV_DEBUG || DEBUG_IP) trace('リダイレクトなしでの投稿処理開始');
	// 配信対象施設データ取得
	$snsPost = new SnsPost();
	$data = $snsPost->getTargetList();
	if (!$data) {
		showWarning("配信対象施設データがありません。");
		return;
	}
	$siteUrl = SITE_URL;
	foreach ($data as $key => $value) {
		usleep(1000000);		// 1秒
		$schoolId = $value['schoolId'];
		$message = "保育施設情報に{$value['prefName']}{$value['cityName']}の「{$value['title']}」が追加されました。";
		$postData = array(
			'message'	=> $message, 											// メッセージ
			'link'		=> "{$siteUrl}?p=Review/Review&schoolId={$schoolId}",	// リンク
			// 画像投稿（何故か「/me/feed」へPOSTだとエラーになる。「/me/photos」なら成功）
			// 'source' => $fb->fileToUpload('image/logo_500.png'),
		);
		try{
			$fb->post('/me/feed', $postData, $pageAccessToken);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			showError($e->getMessage(), 'FacebookResponseException');
			return;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			showError($e->getMessage(), 'FacebookSDKException');
			return;
		}
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の投稿成功");
		// sendTime 追加
		$snsPost->updateSendTime($schoolId);
	}
	if (WV_DEBUG || DEBUG_IP) trace('リダイレクトなしでの投稿処理終了');
	showInfo("facebook への投稿成功数：" . count($data) . "件");
	return;
}



