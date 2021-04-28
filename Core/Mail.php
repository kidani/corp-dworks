<?php

/**
 *
 * メール関連
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Mail {

	/**
	 *
	 * メールの送信（テンプレート利用）
	 *
	 * テンプレートファイル配置場所は DocumentRoot/doc/
	 *
	 */
	public function sendMail($data, $fromAddress, $toAddress, $template, $subject) {

		if (!$data || !$fromAddress || !$toAddress || !$subject) {
			showError(func_get_args(), '引数不正検知');
			return false;
		}

		// テンプレートファイルの存在確認
		if (!file_exists($template)) {
			showError($template, 'メールテンプレートファイルが存在しません。');
			return false;
		}
		// テンプレート取得
		$body = file_get_contents($template);
		// テンプレートの変数展開（ここで $data の値をセット）
		$body = eval("return <<<EVAL\n{$body}EVAL;\n");
		// メールの文字化け回避（uniなら不要）
		// $body = mb_convert_encoding($body,"ISO-2022-JP-ms", "UTF-8");

		if (strstr($toAddress, 'woodvydummy')) {
			// woodvydummy1@gmail.com～woodvydummy8@gmail.com は存在しないダミーアドレスなのでメール送信せずに終了
			if (WV_DEBUG) debug($subject, "メール送信先：{$toAddress}はダミーアドレスなのでメール送信なし");
			return true;
		}

		mb_language("uni");
		mb_internal_encoding("UTF-8");
		if (!mb_send_mail($toAddress, $subject, $body ,"From:{$fromAddress}")) {
			showError("メール送信エラー：mailTo：{$toAddress}, subject：{$subject}");
			return false;
		} else {
			if (WV_DEBUG) debug($subject, "{$toAddress}へメール送信完了");
		}
		return true;
	}

	/**
	 *
	 * メールの送信（テンプレートなし）
	 *
	 */
	public function sendMailSimple($fromAddress, $toAddress, $subject, $body) {
		if (strstr($toAddress, 'woodvydummy')) {
			// woodvydummy1@gmail.com～woodvydummy8@gmail.com は存在しないダミーアドレスなのでメール送信せずに終了
			if (WV_DEBUG) debug($subject, "メール送信先：{$toAddress}はダミーアドレスなのでメール送信なし");
			return true;
		}
		mb_language("uni");
		mb_internal_encoding("UTF-8");
		if (!mb_send_mail($toAddress, $subject, $body, "From:{$fromAddress}")) {
			showError("メール送信エラー：mailTo：{$toAddress}, subject：{$subject}");
			return false;
		} else {
			// if (WV_DEBUG) debug($subject, "{$toAddress}へメール送信完了");
			if (WV_DEBUG) trace("{$toAddress} へのメール送信完了");
		}
		return true;
	}

	/**
	 *
	 * メールの送信情報取得
	 *
	 * テンプレートファイル配置場所は DocumentRoot/doc/
	 *
	 */
	public function getBody($data, $template) {

		// テンプレートファイルの存在確認
		if (!file_exists($template)) {
			showError($template, 'メールテンプレートファイルが存在しません。');
			return false;
		}
		// テンプレート取得
		$body = file_get_contents($template);
		// テンプレートの変数展開（ここで $data の値をセット）
		$body = eval("return <<<EVAL\n{$body}EVAL;\n");

		return $body;
	}

	/**
	 *
	 * メール一斉送信（PHPMailer）
	 *
	 * メルマガ等に利用。
	 *
	 */
	public function sendBulkMail($from, $toList, $subject, $body, $attach) {
		$filePHPMailer = WVFW_ROOT . '/Lib/PHPMailer/class.phpmailer.php';
		if (!file_exists($filePHPMailer)) {
			showError($filePHPMailer, 'PHPMailerライブラリ読み込み失敗');
			return false;
		}
		require_once ($filePHPMailer);
		mb_language("uni");
		mb_internal_encoding("UTF-8");
		$mail = new PHPMailer();
		// $mail->CharSet = "iso-2022-jp";  // ⌘等のShift-JIS非対応文字は化けるので却下。
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "7bit";
		$mail->From = $from;
		$mail->FromName = mb_encode_mimeheader(mb_convert_encoding(SITE_NAME, "JIS", "UTF-8"));
		$mail->Subject = mb_encode_mimeheader(mb_convert_encoding($subject, "JIS", "UTF-8"));

		if ($body['html']) {
			// HTMLメールの場合
			// if (WV_DEBUG) debug($body['html'], 'HTMLメール送信');
			// $mail->Body = mb_convert_encoding($body['html'], "JIS", "UTF-8");  // これだと文字化け
			$mail->Body = $body['html'];

			// 代替テキスト本文（HTMLメール非対応端末用）
			$mail->AltBody = $body['text'];
			$mail->isHTML(true);

			//添付ファイル追加
			foreach ($attach as $key => $value) {
				if (file_exists($value['path'])) {
					// $mail->AddAttachment($value['path']);
					$mail->AddEmbeddedImage($value['path'], $value['cid']);
				}
			}
		} else {
			// if (WV_DEBUG) debug($body['text'], 'プレーンテキスト送信');
			$mail->Body = $body['text'];
		}

		// 一定間隔でメール送信
		$errCnt = 0;
		foreach ($toList as $key => $value) {
			usleep(500000);   // 0.5秒スリープ
			$mail->ClearAddresses();
			$mail->AddAddress($value['mailAddress']);
			if (!$mail->Send()) {
				// 送信失敗したアドレスを記録
				//     TODO：該当ユーザーをメルマガ配信無効にするなどの処理必要か。
				//           対応するとしたら maillog をパースして連携するしかないかも。
				$errCnt++;
				errorLog($mail->ErrorInfo, "メール送信失敗：{$value['mailAddress']}");
			}
		}
		// イベントログ追加（メルマガ送信結果）
		eventLog("件名：{$subject}", 'メルマガ送信件数：'
			. count($toList) . " エラー件数：{$errCnt}", BATCH_EVENT_LOG_FILE);
		return true;
	}

	/**
	 *
	 * メールデータの解析（Pear::Mail_MimeDecode）
	 *
	 * @param    $rawData    ：生のメールデータ
	 * @param    $imgDir     ：添付画像保存先
	 *
	 * @return
	 *     from		：
	 *     to		：
	 *     subject	：
	 *     body
	 *         text	：プレーンテキスト本文
	 *         html ：HTML形式のテキスト本文
	 *     attach	：添付ファイルリスト
	 *
	 *
	 *
	 */
	// 例
	// Array
	// (
	//     [from] =>  <fvainp3@ezweb.ne.jp>
	//     [to] =>  <mailmaga@dev-tenteko.wv-fw.com>
	//     [subject] => test
	//     [body] => Array
	//         (
	//             [text] => Test
	//             [html] => <HTML><HEAD><meta http-equiv="Content-Type" ...
	//         )
	//     [attach] => Array
	//         (
	//             [0] => Array
	//                 (
	//                     [cid] => C87B849DC1CE321C3FF0306D609B4B17-00@decoMailer
	//                     [path] => ../Tmp/1.gif
	//                     [ext] => gif
	//                     [name] =>
	//                 )
	//         )
	// )
	//
	// ※HTML本文中に以下のようなタグでインライン画像が指定されている。
	//     <img src="cid:CBD3E7EDDF8ECCA8167F73E8EE9DC415-00@decoMailer">
	public function parseMailData($rawData, $imgDir = "../Tmp") {

		require_once ('Mail/mimeDecode.php');

		if (!$rawData) {
			showError(null, 'メールデータなし');
			return false;
		}

		if (!file_exists($imgDir)) {
			showError($imgDir, '画像の保存先に存在しないディレクトリを指定');
			return false;
		}

		// debug(func_get_args(), 'func_get_args');
		$result = null;
		$source['include_bodies'] = true;
		$source['decode_bodies']  = true;
		$source['decode_headers'] = true;

		// $source['crlf'] = "\r\n";
		$decoder = new Mail_mimeDecode($rawData);
		$structure = $decoder->decode($source);
		// if (WV_DEBUG) debug($structure, '$structure');

		$result['from'] = $structure->headers['from'];
		$result['to'] = $structure->headers['to'];
		$result['subject'] = mb_convert_encoding($structure->headers['subject'], 'UTF-8', 'auto');

		// エスケープ処理
		// $result['from'] = addslashes($result['from']);
		// $result['from'] = str_replace('"','',$result['from']);
		// 署名付きの処理
		//preg_match("/<.*>/", $result['from'], $str);
		//if ($str[0]!= "" ) {
		//    $str = substr($str[0], 1, strlen($str[0]) -2);
		//    $result['from'] = $str;
		//}

		$body['html'] = $body['text'] = null;
		$attach = array();

		$ctype1 = strtolower($structure->ctype_primary);
		if ($ctype1 === 'text') {
			// プレーンテキストのみの場合
			$body['text'] = $structure->body;
		} elseif ($ctype1 === 'multipart') {
			// HTMLメールまたは添付ファイルありの場合
			foreach ($structure->parts as $key1 => $part1) {
				$ctype2 = strtolower($part1->ctype_primary);
				if ($ctype2 === 'image') {
					// 画像を保存（Tmp配下に作成）
					$path = "{$imgDir}/{$key1}";
					$this->getMailImage($part1, $path, $attach);
				} elseif ($ctype2 === 'multipart') {
					// multipart の場合
					//     もう1階層処理
					foreach ($part1->parts as $key2 => $part2) {
						$ctype3 = strtolower($part2->ctype_primary);
						if ($ctype3 === 'image') {
							// 画像を保存
							$path = "{$imgDir}/{$key2}";
							$this->getMailImage($part2, $path, $attach);
						} elseif ($ctype3 === 'multipart') {
							// デコメーラーで送信したメールの場合、この階層まで走査が必要。
							// メーラーにより階層が深い場合があるみたい。
							foreach ($part2->parts as $part3) {
								$this->getMailBody($part3, $body);
							}
						} elseif ($ctype3 === 'text') {
							// 通常はこの階層にテキスト本文が配置されているはず。
							$this->getMailBody($part2, $body);
						}
					}
				} elseif ($ctype2 === 'text') {
					// iPhoneの標準メーラーでインライン画像なしのHTMLメールの場合
					// この階層にテキスト本文が配置されるみたい。
					$this->getMailBody($part1, $body);
				}
			}
		}
		$result['body'] = $body;
		$result['attach'] = $attach;
		return $result;
	}

	/**
	 *
	 * メール画像取得
	 *
	 */
	private function getMailImage(&$part, $path, &$attach) {
		// 添付ファイルの場合
		$ext = strtolower($part->ctype_secondary);
		if ($ext !== 'jpeg' && $ext !== 'jpg' && $ext !== 'gif' && $ext !== 'png') {
			return;
		}
		// 画像を保存（Tmp配下に作成）
		$path = "{$path}.{$ext}";
		$fp = fopen($path, 'w');
		$length = strlen($part->body);
		fwrite($fp, $part->body, $length);
		fclose($fp);

		// メーラーにより x-attachment-id は付加されていないみたいなので、
		// content-id からカッコを除去したものを使う。
		// $img['cid'] = $part->headers['x-attachment-id'];
		$img['cid'] = $part->headers['content-id'];
		$img['cid'] = ltrim($img['cid'], '<');
		$img['cid'] = rtrim($img['cid'], '>');

		$img['path'] = $path;
		$img['ext'] = $ext;
		$img['name'] = '';
		$attach[] = $img;
	}

	/**
	 *
	 * メール本文取得
	 *
	 */
	private function getMailBody(&$part, &$body) {
		if ($part->ctype_secondary === 'plain') {
			// テキストメール
			$body['text'] = $part->body;
			$body['text'] = mb_convert_encoding($body['text'], 'UTF-8', 'auto');
		} elseif ($part->ctype_secondary === 'html') {
			// HTMLメール
			$body['html'] = $part->body;
			$body['html'] = mb_convert_encoding($body['html'], 'UTF-8', 'auto');
		}
	}

	/**
	 *
	 * 基本情報取得
	 *
	 */
	public function setBaseInfo(&$mailData, $config, $userInfo = null) {
		$mailData['siteUrl'] = SITE_URL;
		$mailData['siteName'] = SITE_NAME;
		$mailData['siteCatch'] = $config['Base']['SITE_CATCH'];
		$mailData['fromAddress'] = $config['Mail']['fromSite'];
		$mailData['userLogin'] = null;
		if ($userInfo) {
			if (isset($userInfo['userLogin']) && $userInfo['userLogin'])
				$mailData['userLogin'] = 1;
			if (isset($userInfo['userId']))
				$mailData['userId'] = $userInfo['userId'];
				if (!$mailData['userLogin']) {
					$mailData['userId'] .= "（未ログイン）";
				}
			if (isset($userInfo['nickname']))
				$mailData['nickname'] = $userInfo['nickname'];
			if (isset($userInfo['mailAddress']))
				$mailData['mailAddress'] = $userInfo['mailAddress'];
			if (isset($userInfo['namae']))
				$mailData['namae'] = $userInfo['namae'];
		} else {
			$mailData['userId'] = '不明';
		}
	}

	/**
	 *
	 * システム管理者へのメール送信実行
	 *
	 * ＜例＞
	 * $mail = new Mail();
	 * $detail = "通知内容：○○○○○\n";
	 * $detail .= "注意事項：○○○○○\n";
	 * $mail->sendToSystemAdmin('○○○○○のお知らせ', $detail);
	 *
	 */
	public function sendToSystemAdmin($subject, $detail) {
		$this->setBaseInfo($mailData, SiteConfig::$conf);
		$template = WVFW_ROOT . "/Site/Doc/Mail/ToSystemAdmin.txt";
		$mailData['detail'] = $detail;
		if (!$this->sendMail($mailData, $mailData['fromAddress']
			, SiteConfig::$conf['Mail']['toSystemAdmin'], $template, "［{$mailData['siteName']}］{$subject}")) {
			errorLog($mailData, 'システム管理者へのメール送信失敗');
		}
	}
}










