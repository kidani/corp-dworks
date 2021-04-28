<?php
/**
 *
 * ソーシャルプロバイダへの自動投稿
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// twitter
//require_once (WVFW_ROOT . '/Lib/Social/twitteroauth-master/autoload.php');
//use Abraham\TwitterOAuth\TwitterOAuth;
$mode = isset($param['mode']) ? $param['mode'] : null;

// facebook
require_once (WVFW_ROOT . '/Lib/Social/php-graph-sdk/src/Facebook/autoload.php');

//if ($mode === 'twitter') {
//
//	$consumerKey = "your consumer key";
//	$consumerSecret = "your consumer secret";
//	$accessToken = "your access token";
//	$accessTokenSecret = "your access token secret";
//
//	$twitter = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
//
//	$result = $twitter->post(
//		"statuses/update",
//		array("status" => "本日ハ晴天ナリ")
//	);
//
//	if($twitter->getLastHttpCode() == 200) {
//		// ツイート成功
//		print "tweeted\n";
//	} else {
//		// ツイート失敗
//		print "tweet failed\n";
//	}
//} elseif ($mode === 'facebook') {

	// 開発
	define('MY_APP_ID', '623673564765668');
	define('MY_APP_SECRET', '15d7b541e093fec7a4ce6ee5667c3cd2');
	define('MY_APP_TOKEN', '623673564765668|C6MNFO0NxBCOPZuzntT5Hti7srs');// ★これは不要かも
	define('PAGE_ACCESS_TOKEN', 'EAAI3OlFIneQBAK3nCReYYKZAveJsfZBjHQ8j1z21oM4tI6BjX1aWBgl5ZAHQPqSm1Ysiw2MSzBRYZC6UZCWZAXlOoZCCntLLzga6T5t6B6o9R2Un1cYTMUpBACiosszgITwXNwSZBNyCt8ZCbi52AVqAJCEHFuVxo0c2hhhPARGqVKSI3S4sYdLLK4PL6isiGxtpuzEd5ZBTX5QQZDZD');

	// 本番
	//	define('MY_APP_ID', '638846156579836');
	//	define('MY_APP_SECRET', '913eb470663996aa2ec71f1d5b4a0c68');
	define('PAGE_ACCESS_TOKEN', 'EAAI3OlFIneQBAK3nCReYYKZAveJsfZBjHQ8j1z21oM4tI6BjX1aWBgl5ZAHQPqSm1Ysiw2MSzBRYZC6UZCWZAXlOoZCCntLLzga6T5t6B6o9R2Un1cYTMUpBACiosszgITwXNwSZBNyCt8ZCbi52AVqAJCEHFuVxo0c2hhhPARGqVKSI3S4sYdLLK4PL6isiGxtpuzEd5ZBTX5QQZDZD');

	$fb = new Facebook\Facebook(array(
		'app_id' 				=> MY_APP_ID,
		'app_secret' 			=> MY_APP_SECRET,
		'default_graph_version' => 'v2.10')
	);

//	$helper = $fb->getRedirectLoginHelper();
//	if(isset($_GET['code'])){
//		try {
//			$access_token = $helper->getAccessToken();
//		} catch(Facebook\Exceptions\FacebookResponseException $e) {
//			echo 'Graph returned an error: ' . $e->getMessage();
//			exit;
//		} catch(Facebook\Exceptions\FacebookSDKException $e) {
//			echo 'Facebook SDK returned an error: ' . $e->getMessage();
//			exit;
//		}
//		if (isset($access_token)) {
//			debug($access_token, '$access_token');
//			debug($fb->get('/me/accounts', $access_token), 'accounts 取得？');
//		}
//	} else {
//		$this_url = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	//		$permissions = array('manage_pages', 'publish_pages');
//		$login_url = $helper->getLoginUrl($this_url, $permissions);
//		echo '<a href="' . $login_url . '">Log in with Facebook!</a>';
//		return;
//	}

	$post_data = array(
		'name' 			=> 'リンク名',
		'link' 			=> 'http://dev-hoikuen.wv-fw.com/?p=Review/Review&schoolId=1278',
		'picture' 		=> 'http://dev-hoikuen.wv-fw.com/images/school/1278/1.jpg?upd=20190527113413',
		'caption' 		=> '説明文',
		'description' 	=> '詳細文',
		'message' 		=> '投稿メッセージ本文',
	);

	try{
		// $fb->post('/me/feed', $post_data, MY_APP_TOKEN);
		$fb->post('/me/feed', $post_data, PAGE_ACCESS_TOKEN);
	//} catch(FacebookApiException $e) {
	//	echo 'API returned an error: ' . $e->getMessage();
	//	exit;
	//}
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		echo 'Graph returned an error: ' . $e->getMessage();
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

//echo "<pre>";print_r ($mode);echo "</pre>"; exit;

//} elseif ($mode === 'line') {
//
//
//
//}

