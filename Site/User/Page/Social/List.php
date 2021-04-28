<?php

/**
 *
 * 全プロバイダーへの投稿（未使用）
 *
 * Site/User/Batch/CheckMinutely.php で処理可能なのでこのページ自体不要かも。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Core/Sns.php');

// 管理者以外のアクセス不可
if (User::$userInfo['mailAddress'] !== 'kidani@wd-valley.com'){
	showError('アクセス不可');
	return;
}

//------------------------------------------------
// Facebook
//------------------------------------------------
// ※詳しい説明は「Social/Facebook」参照。
require_once (WVFW_ROOT . '/Lib/Social/php-graph-sdk/src/Facebook/autoload.php');
$pageAccessToken = 'EAAJFBvY6oZCwBAFCOtqCFDsjyNxZBFpyO97LYeTSNjKV8nZBhVz7Hr7qW4i8DT0jYi5pVsTtn2DjYdW5L8sFjUI8i30tNZCzSPWW7mYmKwgZA6b204CCJQUHAPMfPJzMR6QfiBXtxuQpqqeZAZCTuSZC1jI5KGj9Q4y5r7AcWFNC0RU3YfiiGLGN';
$fb = new Facebook\Facebook(array(
		'app_id'                    => '638846156579836',					// アプリID
		'app_secret'                => '913eb470663996aa2ec71f1d5b4a0c68',	// app secret
		'default_graph_version'     => 'v3.3'								// Graph API バージョン
	)
);

//------------------------------------------------
// Twitter
//------------------------------------------------
// ※詳しい説明は「Social/Twitter」参照。
require_once (WVFW_ROOT . '/Lib/Social/twitteroauth-master/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;
$twitter = new TwitterOAuth("jyij8AK2H1clbZqleftozNmIp", 							// API keys
							"2V1UpvEhyNeiDormjgoIuCNtsz9YpmCZ0LubWZoDIXce7FcFGY", 	// API secret key
							"1135126662700343296-VZE7RN9WU77mEEvek4NUJjZu9QaUii", 	// Access token
							"R56PhrHgvYMN7gLS4uaRFIeJ2iwsbXILP67CflPYzAqWF");		// Access token secret
$mode = isset($param['mode']) ? $param['mode'] : null;
$code = isset($param['code']) ? $param['code'] : null;
if($mode === 'feed') {
	if (WV_DEBUG || DEBUG_IP) trace('投稿開始');
	// 配信対象施設データ取得
	$snsPost = new SnsPost();
	$data = $snsPost->getTargetList();
	if (!$data) {
		showWarning("配信対象施設データがありません。");
		return;
	}
	$siteUrl = SITE_URL;
	foreach ($data as $key => $value) {
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の 投稿開始");
		// usleep(1000000);		// 1秒
		usleep(10000000);		// 10秒
		$schoolId = $value['schoolId'];
		$message = "保育施設情報に{$value['prefName']}{$value['cityName']}の「{$value['title']}」が追加されました。";
		$link = "{$siteUrl}?p=Review/Review&schoolId={$schoolId}";

		//------------------------------------------------
		// Facebook
		//------------------------------------------------
		$postData = array(
			'message'	=> $message, 	// メッセージ
			'link'		=> $link,		// リンク
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
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の Facebook 投稿成功");

		//------------------------------------------------
		// Twitter
		//------------------------------------------------
		$message = "{$message}\n" . "{$link}\n" . "#保活 #{$value['cityName']} #{$value['title']}";
		$postData = array(
			"status" => $message,
		);
		$result = $twitter->post(
			"statuses/update",
			$postData
		);
		$statusCode = $twitter->getLastHttpCode();
		if($statusCode !== 200) {
			$errCode = intval($result->errors[0]->code);
			$errMessage = $result->errors[0]->message;
			if($errCode === 187) {
				// Status is a duplicate.：同じ投稿を拒否するエラー
				showError($result->errors[0]->message, "同じ内容の投稿検知：{$message}");
				return;
			}
		}
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の Twitter 投稿成功");
		// sendTime 追加
		$snsPost->updateSendTime($schoolId);
	}
	if (WV_DEBUG || DEBUG_IP) trace('投稿終了');
	showInfo("Facebook・Twitter への投稿成功数：" . count($data) . "件");
	return;
}



