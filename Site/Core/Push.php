<?php

/**
 *
 * プッシュ通知
 *
 * ネイティブアプリのプッシュ通知（リモート通知）
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Push
{
	// チームID
	const TEAM_ID = '23A3V99VD5';
	// バンドル
	const BUNDLE_ID = 'com.wd-valley.hokatsu';
	// APNs認証キー（開発・本番兼用）
	const PUSH_SECRET_KEY_PATH = WVFW_ROOT . '/Site/Doc/Certificates/AuthKey_PXU965H9GF.p8';
	const PUSH_SECRET_KEY_ID = 'PXU965H9GF';
	// PUSH通知送信先URL
	const PUSH_URL_DEV = 'https://api.development.push.apple.com/3/device';		// 開発用
	const PUSH_URL_PRD = 'https://api.push.apple.com/3/device';					// 本番用

	// Android
	//     FireBase サーバーキー
	const PUSH_SEVER_KEY = 'AAAARvbJyTU:APA91bGYTtRFOZdToUkyFtIWoegoTKQ64T5qdQmkONvfHM6XChCJ04AqqZFe-5LjgvaXE2a4koGN2o1bsbsFZmibv4PGy37y0DzQ6hSXMoCd1TQArJVFaZGMUxo-qvCWJdfntLugR9BL';

	/**
	 *
	 * プッシュ通知（リモート通知）
	 *
	 * 1ユーザーのみ送信
	 *
	 */
    public function pushRemote($userInfo, $sendInfo, &$result = null)
    {
		if ($userInfo['deviceToken']) {
			// iOS 端末に送信
			// 通知内容なしならバッジ数のみリセット
			$this->pushIos(array($userInfo['deviceToken']), $sendInfo, $result);
		}
		if ($userInfo['regToken']) {
			if (!$sendInfo['detail']) {
				// 通知内容なしなら送らない。
				return true;
			}
			// Android 端末に送信
			$this->pushAndroid(array($userInfo['regToken']), $sendInfo, $result);
		}
		return true;
	}

	/**
	 *
	 * プッシュ通知一括送信（リモート通知）
	 *
	 * 管理画面からの一括送信用。
	 *
	 * $userList 	：既にPUSH通知送信可能なリストのみに絞られてる前提。
	 *
	 */
	public function pushRemoteAll($sendInfo, &$result)
	{
		// 一括送信回数：ApnsPHPは最大値不明なので500件にしておく。
		$maxSendCnt = 500;

		// ios（Users）
		$this->pushRemoteByPf('User', 'ios', $maxSendCnt, $sendInfo, $result);

		// ios（UsersTemp）
		$this->pushRemoteByPf('UsersTemp', 'ios', $maxSendCnt, $sendInfo, $result);

		// 一括送信回数：firebase は最大1000件単位で送信可能
		$maxSendCnt = 1000;

		// android（Users）
		$this->pushRemoteByPf('User', 'android', $maxSendCnt, $sendInfo, $result);

		// android（UsersTemp）
		$this->pushRemoteByPf('UsersTemp', 'android', $maxSendCnt, $sendInfo, $result);
	}

	/**
	 *
	 * プッシュ通知一括送信（リモート通知）
	 *
	 * テーブル・PF毎に一括送信する。
	 *
	 */
	public function pushRemoteByPf($className, $pf, $maxSendCnt, $sendInfo, &$result)
	{
		$instance = new $className();
		$sendCntTotal = $instance->getValidPushUserCnt($pf);
		if ($sendCntTotal) {
			$pageCnt = intval(ceil($sendCntTotal/$maxSendCnt));
			// if (WV_DEBUG) trace('$pageCnt ' . $pageCnt);
			for($i = 1; $i <= $pageCnt; $i++) {
				// 未配信件数
				$remainCnt = $sendCntTotal - ($maxSendCnt * ($i - 1));
				// if (WV_DEBUG) trace('$remainCnt ' . $remainCnt);
				// このターンで配信する件数
				if ($remainCnt > $maxSendCnt) {
					$sendCnt = $maxSendCnt;
				} else {
					$sendCnt = $remainCnt;
				}
				$regTokenList = $instance->getValidPushTokenList($pf, $maxSendCnt, $i);
				// インスタンスメソッド取得（$this->pushIos／$this->pushAndroid）
				$methodName = "push" . ucfirst($pf);
				$this->$methodName($regTokenList, $sendInfo, $result);
			}
			if (WV_DEBUG) trace("{$pf} {$className} のプッシュ通知送信完了");
		}
		return true;
	}

	/**
	 *
	 * プッシュ通知実行（iOS ApnsPHPライブラリ）
	 *
	 * 一括送信（マルチキャスト）について
	 * 最大何件まで送信可能という制限ではなく、1回の通信で全パケットが
	 * 5000～7000バイトを超えるとAPNSから切断される？
	 *
	 */
	private function pushIos($deviceTokenList, $sendInfo, &$result = null)
	{
		if (!defined('CURL_HTTP_VERSION_2_0')) {
			define('CURL_HTTP_VERSION_2_0', CURL_HTTP_VERSION_1_1 + 1);
		}

		if ($deviceTokenList === '(null)') {
			// 何故か文字列「(null)」がセットされている場合があるのでログ調査で出力
			errorLog(setLogDetail($deviceTokenList), 'deviceToken の値が不正');
			return false;
		}

		$teamId = self::TEAM_ID;
		$keyPath = self::PUSH_SECRET_KEY_PATH;
		$keyId = self::PUSH_SECRET_KEY_ID;
		$bundleId = self::BUNDLE_ID;
		$apiUrl = (ENV_ID === 'Prd' ? self::PUSH_URL_PRD : self::PUSH_URL_DEV);

		$header = ['alg'=>'ES256', 'kid'=>$keyId];
		$headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
		$claims = ['iss'=>$teamId, 'iat'=>time()];
		$claimsEncoded = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
		$privateKey = openssl_pkey_get_private("file://{$keyPath}");		// 秘密鍵取得

		// JWT（JSON Web Token）生成（作成後1時間有効らしい）
		$signature = '';
		openssl_sign($headerEncoded . '.' . $claimsEncoded, $signature, $privateKey, 'sha256');
		$jwt = "{$headerEncoded}.{$claimsEncoded}." . base64_encode($signature);

		// タイトル
		$title = $sendInfo['title'] ? $sendInfo['title'] : SITE_NAME . 'からのお知らせ';

		// バッジナンバー
		if (isset($sendInfo['badgeNumber'])&& $sendInfo['badgeNumber'] ) {
			$badgeNumber = intval($sendInfo['badgeNumber']);
		} else {
			$badgeNumber = 0;
		}

		$mh = curl_multi_init();
		$ch = array();
		foreach($deviceTokenList as $key => $deviceToken) {
			$url = "{$apiUrl}/{$deviceToken}";
			$message = array(
				'aps' => array(
					'alert' => array(
						'title'  			=> $title,
						// 'subtitle'  		=> 'サブタイトル',
						'body'  			=> $sendInfo['detail'],
						// 'launch-image'  	=> 'https://hokatsu-navi.com/images/marker_finger.png',
					),
					'category' 	=> $sendInfo['linkPage'],		// 遷移先の振り分け用
					'sound' 	=> 'default',
					'badge' 	=> $badgeNumber
				),
				// 'hoge' => 'fuga'					// カスタムキー
			);
			$ch[$key] = curl_init();
			curl_setopt($ch[$key], CURLOPT_URL, $url);
			curl_setopt($ch[$key], CURLOPT_POSTFIELDS, json_encode($message));
			curl_setopt($ch[$key], CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
			curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch[$key], CURLOPT_HTTPHEADER, array("apns-topic: {$bundleId}", "authorization: bearer $jwt"));
			curl_setopt($ch[$key], CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch[$key], CURLOPT_TIMEOUT, 15);
			curl_multi_add_handle($mh, $ch[$key]);
		}

		// プッシュ通知送信実行
		// if (WV_DEBUG) debug($message, "iOSプッシュ通知送信開始");
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);

		// プッシュ通知結果取得（恐らく非同期）
		$errorReason = array();
		foreach($ch as $curl)
		{
			$response = curl_multi_getcontent($curl);
			$res = json_decode($response, true);
			$curlInfo = curl_getinfo($curl);
			if ($res) {
				// 送信失敗
				$result['iosNgCnt']++;		// 送信失敗数を追加
				$errorNo = curl_errno($curl);
				if (WV_DEBUG) debug(setLogDetail($res, $curlInfo, $errorNo, func_get_args()), 'iOS プッシュ通知送信失敗');
				if ($res['reason'] == "BadDeviceToken" || $res['reason'] == "Unregistered" ) {
					// 送信失敗理由が判明している場合
					// https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/handling_notification_responses_from_apns
					// 400 BadDeviceToken	：デバイストークンが不正（そもそも存在しない）
					// 410 Unregistered		：？
					// DBから削除
					$deviceToken = preg_replace('|.*/3/device/(.*)$|', '$1', $curlInfo['url']);
					if (WV_DEBUG) debug(setLogDetail($deviceToken, $res['reason']), 'デバイストークン削除');
					$userIos = new UserIos();
					$userIos->deleteDeviceToken($deviceToken);
				} else {
					// その他の理由で送信失敗（当分監視しておく！）
					// 413 PayloadTooLarge など運用側が原因の場合もあるのでデバイストークンを削除しないこと！
					// checkLog($res['reason'], 'iOS プッシュ通知送信失敗理由');
					errorLog($res['reason'], 'iOS プッシュ通知送信失敗理由');
				}
			} elseif ($curlInfo['http_code'] !== 200) {
				// $res が空で、http_code が 200 以外の場合
				$result['iosNgCnt']++;		// 送信失敗数を追加
				errorLog($curlInfo, 'iOS プッシュ通知送信 curl_multi_exec 失敗');
			} else {
				// $res が空で、http_code が 200 の場合
				// if (WV_DEBUG) trace('iOS プッシュ通知送信 curl_multi_exec 成功');
				$result['iosOkCnt']++;
			}
		}
		curl_multi_close($mh);
		return true;
	}

	/**
	 *
	 * プッシュ通知実行（Android）
	 *
	 */
	private function pushAndroid($tokenList, $sendInfo, &$result = null)
	{
		if (WV_DEBUG) trace(date('Y-m-d H:i:s') . " pushAndroid 開始");

		// タイトル
		$title = $sendInfo['title'] ? $sendInfo['title'] : SITE_NAME . 'からのお知らせ';

		try {
			$url = 'https://fcm.googleapis.com/fcm/send';
			$httpOptions = array('timeout' => '10');
			$request = new HTTP_Request2($url, HTTP_Request2::METHOD_POST, $httpOptions);
			$request->setAdapter('curl');  // 本番は必須なので注意！超はまった。

			// ヘッダ
			$request->setHeader('Content-type', 'application/json');
			$request->setHeader('Authorization', 'key=' . self::PUSH_SEVER_KEY);

			// 送信先とデータ
			if (is_array($tokenList)) {
				// 一括送信（マルチキャスト）
				// 最大1000件まで送信可能
				$arr = array(
					"registration_ids" => $tokenList,
					"data" => array('title' => $title, 'body' => $sendInfo['detail'], 'category' => $sendInfo['linkPage']),
				);
			} else {
				// 単体送信
				$arr = array(
					"to" => $tokenList,
					"notification" => array('title' => $title, 'body' => $sendInfo['detail']),
					"data" => array('title' => $title, 'body' => $sendInfo['detail'], 'category' => $sendInfo['linkPage']),
				);
			}
			if (WV_DEBUG) debug($arr, " 送信先とデータ");
			$request->setBody(json_encode($arr));
			// リクエスト送信
			$response = $request->send();
			// if (WV_DEBUG) debug($response, '$response');
			// レスポンス受信に2秒程度かかる。
			$body = json_decode($response->getBody());
			// if (WV_DEBUG) debug($body, 'pushAndroid $body');
			if (WV_DEBUG) trace(date('Y-m-d H:i:s') . " pushAndroid レスポンス受信成功");
			// 無効なトークンをDBから削除
			// https://firebase.google.com/docs/cloud-messaging/http-server-ref?hl=ja
			if (intval($body->failure) > 0) {
				// エラーありの場合（androidでは頻繁に発生するので抑止）
				// errorLog($body, 'PUSH通知エラー検知');
				// errorLog(func_get_args(), 'PUSH通知エラー詳細');
				foreach($body->results as $key => $info) {
					if(isset($info->error)) {
						// if ($info->error == "InvalidRegistration"				// 登録トークンが不正
						// 	|| $info->error == "XXXXXXXXXX"
						// ) {
						// とりあえず失敗したら全て除外
						$result['androidNgCnt']++;		// 送信失敗数を追加
						$invalidToken = $tokenList[$key];
						if (WV_DEBUG) debug($info->error, 'android プッシュ通知送信失敗理由');
						// DBから削除
						$userAndroid = new UserAnd();
						$userAndroid->deleteRegToken($invalidToken);
						// }
					} else {
						$result['androidOkCnt']++;		// 送信成功数を追加
					}
				}
			} else {
				$result['androidOkCnt'] += count($tokenList);
			}
		} catch( HTTP_Request2_Exception $e ){
			errorLog($e, 'PUSH通知エラー HTTP_Request2_Exception');
			return false;
		} catch (Exception $e){
			errorLog($e, 'PUSH通知エラー Exception');
			return false;
		}
		return true;
	}

}










