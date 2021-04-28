<?php

/**
 *
 * Twitter のログインと投稿
 *
 * 開発環境でも実行可能。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Core/Sns.php');
require_once (WVFW_ROOT . '/Lib/Social/twitteroauth-master/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

// 管理者以外のアクセス不可
if (User::$userInfo['mailAddress'] !== 'kidani@wd-valley.com'){
	showError('アクセス不可');
	return;
}

$consumerKey = "jyij8AK2H1clbZqleftozNmIp";									// API keys
$consumerSecret = "2V1UpvEhyNeiDormjgoIuCNtsz9YpmCZ0LubWZoDIXce7FcFGY";		// API secret key
$accessToken = "1135126662700343296-VZE7RN9WU77mEEvek4NUJjZu9QaUii";		// Access token
$accessTokenSecret = "R56PhrHgvYMN7gLS4uaRFIeJ2iwsbXILP67CflPYzAqWF";		// Access token secret

$twitter = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
$mode = isset($param['mode']) ? $param['mode'] : null;
if($mode === 'feed') {
	if (WV_DEBUG || DEBUG_IP) trace('Twitter 投稿開始');
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
		// URL、ハッシュタグがあれば自動的にリンクが付与される。
		// ハッシュタグ＃は「半角」、＃後のキーワードは日本語とアルファベット、記号は「_」のみ、前後に「スペース」または改行必須。
		$message = "保育施設情報に{$value['prefName']}{$value['cityName']}の「{$value['title']}」が追加されました。\n"
			. "{$siteUrl}?p=Review/Review&schoolId={$schoolId}\n"
			. "#保活 #{$value['cityName']} #{$value['title']}";
		$postData = array(
			"status" => $message,
		);
		// 画像（相対パス以外エラーになるので注意！）
		//$media = $twitter->upload('media/upload', ['media' => "images/school/1278/2.jpg"]);
		//if ($media) {
		//	$postData['media_ids'] = implode(',', [$media->media_id_string]);
		//}
		if (WV_DEBUG || DEBUG_IP) debug('ツイート実行');
		$result = $twitter->post(
			"statuses/update",
			$postData
		);
		// if (WV_DEBUG || DEBUG_IP) debug($result, 'ツイート結果');
		$statusCode = $twitter->getLastHttpCode();
		// if (WV_DEBUG || DEBUG_IP) debug($statusCode, '$statusCode');
		if($statusCode !== 200) {
			$errCode = intval($result->errors[0]->code);
			$errMessage = $result->errors[0]->message;
			if($errCode === 187) {
				// Status is a duplicate.：同じ投稿を拒否するエラー
				showError($result->errors[0]->message, "同じ内容の投稿検知：{$message}");
				return;
			}
		}
		if (WV_DEBUG || DEBUG_IP) trace("{$value['schoolId']}：{$value['title']} の投稿成功");
		// sendTime 追加
		$snsPost->updateSendTime($schoolId);
	}
	if (WV_DEBUG || DEBUG_IP) trace('Twitter 投稿終了');
	showInfo("Twitter への投稿成功数：" . count($data) . "件");
}
















