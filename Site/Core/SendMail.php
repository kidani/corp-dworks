<?php

/**
 *
 * メールでのお知らせ（メルマガ）
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class SendMail {

	/**
	 *
	 * 取得
	 *
	 */
	public function get($bind, $phAnd, $limit = null, $phOrderBy = null) {
		$phLimit = null;
		if ($limit) {
			$phLimit = " LIMIT {$limit} ";
		}
		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
					  SendMail.id
					, SendMail.insTime
					, SendMail.updTime
					, SendMail.sendStartTime
					, SendMail.sendEndTime
					, SendMail.subject
					, SendMail.body
					, SendMail.includeSettingOff
					, SendMail.includePushOK
					, SendMail.sendCnt
					, SendMail.okCnt
					, SendMail.ngCnt
                FROM
                    SendMail
                WHERE
                    SendMail.id is not null
                    $phAnd
                    $phOrderBy
                    $phLimit
                ";
			$sth = $dbh->prepare($sql);
			Db::bindValueList($sth, $bind);
			$result = $sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		$this->format($data);
		return $data;
	}

	/**
	 *
	 * id から取得
	 *
	 */
	public function getById($id) {
		$phAnd = $bind = null;
		// id
		$phAnd .= " and SendMail.id = :id ";
		$bind['id']['value'] = $id;
		$bind['id']['type'] = PDO::PARAM_INT;
		$data = $this->get($bind, $phAnd);
		if ($data) {
			return $data[0];
		}
	}

	/**
	 *
	 * データ数取得
	 *
	 */
	public function getCnt($bind, $phAnd){
		$dbh = Db::connect();
		try {
			$sql = "
	            SELECT
	            	count(SendMail.id) as cnt
	            FROM
	               SendMail
	            WHERE
					SendMail.id is not null
	            	$phAnd
	            ";
			$sth = $dbh->prepare($sql);
			Db::bindValueList($sth, $bind);
			$result = $sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		if ($data) return $data[0]['cnt'];
	}

	/**
	 *
	 * データ取得（ページ単位）
	 *
	 */
	public function getPage($bind, $phAnd, $page, $sort){

		// ORDER句取得
		$phOrderBy = $this->getPhOrderBy($sort);

		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
					  SendMail.id
					, SendMail.insTime
					, SendMail.updTime
					, SendMail.sendStartTime
					, SendMail.sendEndTime
					, SendMail.subject
					, SendMail.body
					, SendMail.includeSettingOff
					, SendMail.includePushOK
					, SendMail.sendCnt
					, SendMail.okCnt
					, SendMail.ngCnt
                FROM
                    SendMail
	            WHERE
	            	SendMail.id is not null
	                $phAnd
	            $phOrderBy
	            LIMIT :offset, :rowCount
	            ";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':offset', intval(($page['curPageNo'] - 1) * $page['countPerPage']), PDO::PARAM_INT);
			$sth->bindValue(':rowCount', intval($page['countPerPage']), PDO::PARAM_INT);
			Db::bindValueList($sth, $bind);
			$result = $sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		$this->format($data);
		return $data;
	}

	/**
	 *
	 * ソート用のORDER句取得
	 *
	 * $sort['sortCol']		：$sortColList のキーを指定
	 * $sort['sortOrder']	：asc／desc
	 *
	 */
	private function getPhOrderBy(&$sort) {
		if (!$sort) return;

		// SendMail のソート指定カラムリスト
		$sortColList = array(
			'id' 		    => 'SendMail.id',
			'insTime'		=> 'SendMail.insTime',
			'sendStartTime'	=> 'SendMail.sendStartTime',
		);

		// ソート列チェック
		//     PDO での ORDER BY 句に指定カラムの bindValue は不可なので
		//     ホワイトリストチェックする。
		$sortCol = $sortColList[$sort['sortCol']];
		if (!$sortCol) {
			showError($sortCol, 'ソート列パラメータが不正です。');
			return;
		}
		// ソート順チェック
		if (isset($sort['sortOrder'])) {
			if ($sort['sortOrder'] !== 'asc' && $sort['sortOrder'] !== 'desc') {
				showError($sortCol, 'ソート列パラメータが不正です。');
				return;
			}
		} else {
			$sort['sortOrder'] = 'asc';
		}

		// ORDER句設定
		//     指定した値が全て同じ場合、別ページに同じデータが表示される場合があるので注意！
		$phOrderBy = " ORDER BY $sortCol {$sort['sortOrder']}, SendMail.id ";

		return $phOrderBy;
	}

	/**
	 *
	 * データ整形
	 *
	 */
	private function format(&$data) {
		foreach($data as $key => $value) {
			// 配信状態追加
			$data[$key]['status'] = $this->getSendStatus($value['sendStartTime'], $value['sendEndTime']);
			// 配信所要時間
			$data[$key]['duration'] = null;
			if ($value['sendStartTime'] && $value['sendEndTime']) {
				$sec = strtotime($value['sendEndTime']) - strtotime($value['sendStartTime']);
				$duration = getFormatDiffTime($sec, '分');
				$data[$key]['duration'] = $duration['show'];
			}
		}
	}

	/**
	 *
	 * 配信ステータス取得
	 *
	 */
	public function getSendStatus($sendStartTime, $sendEndTime) {


		if (strtotime($sendStartTime) <= strtotime('now')) {
			// 配信開始時間を過ぎていた場合
			if ($sendEndTime) {
				$sendStatus = '配信済';
			} else {
				$sendStatus = '配信中';
			}
		} else {
			$sendStatus = '配信待ち';
			if ($sendEndTime) {
				showError(func_get_args(), '配信ステータス不正検知');
			}
		}
		return $sendStatus;
	}

	/**
	 *
	 * メール配信入力チェック
	 *
	 * メールでのお知らせ／メルマガ配信。
	 *
	 */
	public function validate($input) {
		$validation = new Validation();

		// 件名
		//     必須
		//     最大30文字以内
		if (!isset($input['subject']) || !$input['subject']) {
			return '件名を入力してください。';
		} elseif (!$validation->checkStringCnt($input['subject'], 1, 30)) {
			return '件名は30文字以内で入力してください。';
		}

		// 本文
		//     必須
		if (!isset($input['body']) || !$input['body']) {
			return '本文を入力してください。';
		}

		// 配信開始日時
		//     必須
		//     現在日時の10分後以前は設定不可。（バッチ処理が10分間隔なので）
		if (!$input['sendStartTime']) {
			return '配信開始日時を指定してください。';
		} elseif (!$validation->checkDateTime($input['sendStartTime'])) {
			return '配信開始日時は「YYYY/MM/DD hh:mm」で入力して下さい。';
		} elseif (!$this->IsSendStartTimeValid($input['sendStartTime'])) {
			return '配信開始日時は現在から10分後以降を設定して下さい。';
		}
		return null;
	}

	/**
	 *
	 * 登録した配信内容の変更許可確認
	 *
	 */
	private function IsSendStartTimeValid($sendStartTime) {
		if (strtotime($sendStartTime) <= strtotime('now +10 minute')) {
			// 現在日時の10分後以前は設定不可。（バッチ処理が10分間隔なので）
			return false;
		}
		return true;
	}

	/**
	 *
	 * 登録内容を変更可能か確認
	 *
	 */
	public function checkAuthEdit($sendStartTime) {
		if (strtotime($sendStartTime) <= strtotime('now')) {
			// 配信開始時間を過ぎていたら変更不可。
			return false;
		}
		// 変更権限あり
		return true;
	}

	/**
	 *
	 * 配信メール登録・更新
	 *
	 */
	public function upsert(&$sendMailId, $param) {

		$paramUpsert = array(
			'id'           			=> array('value' => $sendMailId        				, 'type' => PDO::PARAM_INT),
			'insTime'         		=> array('value' => date('Y-m-d H:i:s')    			, 'type' => 'datetime'),
			'updTime'         		=> array('value' => date('Y-m-d H:i:s')    			, 'type' => 'datetime'),
			// 'sendStartTime'   	=> array('value' => $param['sendStartTime'] 		, 'type' => 'datetime'),
			// 'sendEndTime'     	=> array('value' => $param['sendEndTime']   		, 'type' => 'datetime'),
			//'subject'           	=> array('value' => $param['subject']       		, 'type' => PDO::PARAM_STR),
			//'body'           		=> array('value' => $param['body']        			, 'type' => PDO::PARAM_STR),
			//'includeSettingOff'	=> array('value' => $param['includeSettingOff'] 	, 'type' => PDO::PARAM_INT),
			//'includePushOK'     	=> array('value' => $param['includePushOK']     	, 'type' => PDO::PARAM_INT),
			//'sendCnt'           	=> array('value' => $param['namae']        			, 'type' => PDO::PARAM_INT),
			//'okCnt'           	=> array('value' => $param['okCnt']        			, 'type' => PDO::PARAM_INT),
			//'ngCnt'           	=> array('value' => $param['ngCnt']        			, 'type' => PDO::PARAM_INT),
		);

		if (array_key_exists('subject', $param))
			$paramUpsert['subject'] = array('value' => $param['subject'], 'type' => PDO::PARAM_STR);
		if (array_key_exists('body', $param))
			$paramUpsert['body'] = array('value' => $param['body'], 'type' => PDO::PARAM_STR);
		if (array_key_exists('includeSettingOff', $param))
			$paramUpsert['includeSettingOff'] = array('value' => $param['includeSettingOff'], 'type' => PDO::PARAM_INT);
		if (array_key_exists('includePushOK', $param))
			$paramUpsert['includePushOK'] = array('value' => $param['includePushOK'], 'type' => PDO::PARAM_INT);
		if (array_key_exists('sendStartTime', $param))
			$paramUpsert['sendStartTime'] = array('value' => $param['sendStartTime'], 'type' => 'datetime');
		if (array_key_exists('sendEndTime', $param))
			$paramUpsert['sendEndTime'] = array('value' => $param['sendEndTime'], 'type' => 'datetime');
		if (array_key_exists('sendCnt', $param))
			$paramUpsert['sendCnt'] = array('value' => $param['sendCnt'], 'type' => PDO::PARAM_INT);

		$dbh = Db::connect();
		if (!Db::pdoUpsert('SendMail', $paramUpsert)) {
			showError('SendMail 登録・更新失敗');
			return false;
		}

		// id 確定
		if (!$sendMailId) {
			// 新規登録の場合
			$sendMailId = $dbh->lastInsertId();
		}

		return true;
	}

	/**
	 *
	 * メール配信実行
	 *
	 */
	public function send($sendMailId) {

		// 配信メール登録取得
		$data = $this->getById($sendMailId);

		// 配信対象ユーザーリスト取得

		// 登録済ユーザーのみ
		$phAnd = $bind = null;
		$phAnd .= " AND Users.status = '登録済' ";
		// ダミー会員は除外（woodvydummy1@gmail.com など）
		$phAnd .= " AND Users.mailAddress not like '%woodvydummy%' ";

		// メールで受信設定OFFのユーザーへの配信
		if (!$data['includeSettingOff']) {
			// 受信許可ユーザーのみ
			$phAnd .= " AND (AlertSetting.mailMagazine = 1) ";
		}

		// PUSH通知可能ユーザーへの配信
		if (!$data['includePushOK']) {
			// PUSH通知可能ユーザーは配信対象から除外
			$phAnd .= " AND (Users.deviceToken is null AND Users.regToken is null)";
		}

		//  検索条件にマッチするユーザーを取得
		$user = new User();
		$userData = $user->getListForMail($bind, $phAnd);
		if (!$userData) {
			showError($sendMailId, "メールでのお知らせ送信対象ユーザーなし検知");
			return;
		}
		// 送信件数
		$sendCount = count($userData);

		// 配信開始日時セット（実際に配信開始した日時で更新する。）
		$paramDb = array();
		$paramDb['sendStartTime'] = date('Y-m-d H:i:s');
		$this->upsert($sendMailId, $paramDb);

		// メール本文を加工して送信
		if (WV_DEBUG) trace("メールでのお知らせ送信開始 SendMail.id：{$sendMailId} 送信件数：{$sendCount}");
		foreach ($userData as $key => $value) {

			// 本文の特殊文字置換
			$body = $data['body'];
			// ユーザー名
			$userName = isset($value['nickname']) ? $value['nickname'] : $value['mailAddress'];
			$this->changeExTag($body, $userName);

			// メール送信
			$mail = new Mail();
			$fromAddress = SiteConfig::$conf['Mail']['fromSite'];
			if (!$mail->sendMailSimple($fromAddress, $value['mailAddress'], $data['subject'], $body)) {
				showError($mailData, "{$data['subject']}：メール送信失敗");
				return;
			}
			// ◆0.5秒スリープ
			usleep(500000);
		}
		if (WV_DEBUG) trace("メールでのお知らせ送信終了 SendMail.id：{$sendMailId}");

		// 配信終了日時と配信数セット
		$paramDb = array();
		$paramDb['sendCnt'] = $sendCount;
		$paramDb['sendEndTime'] = date('Y-m-d H:i:s');
		$this->upsert($sendMailId, $paramDb);
	}

	/**
	 *
	 * メール配信実行
	 *
	 */
	public function sendTest($param, $sendTestUserId) {
		// userId のチェック
		$user = new User();
		$userInfo = $user->getById($sendTestUserId);
		if (!$userInfo) {
			return ("指定したユーザーIDは存在しません。");
		}
		if (!$userInfo['mailAddress']) {
			return ("指定したユーザーIDはメールアドレス未登録です。");
		}

		// 本文の特殊文字（名前）置換
		$body = $param['body'];
		// ユーザー名
		$userName = isset($userInfo['nickname']) ? $userInfo['nickname'] : $userInfo['mailAddress'];
		$this->changeExTag($body, $userName);

		// メール送信
		$mail = new Mail();
		$fromAddress = SiteConfig::$conf['Mail']['fromSite'];
		if (!$mail->sendMailSimple($fromAddress, $userInfo['mailAddress'], $param['subject'], $body)) {
			showError($mailData, "{$param['subject']}：メール送信失敗");
			return;
		}
	}

	/**
	 *
	 * 本文のサンプル取得
	 *
	 */
	public function getBodySample() {
		$siteName = SITE_NAME;
		$siteUrl = SITE_URL;
		$siteCatch = SiteConfig::$conf['Base']['SITE_CATCH'];
		$body = "
##NAME##さま\n
{$siteName}からのお知らせです。

保活コラムに「保活体験談 - ○○○区」が追加されました。
{$siteUrl}?p=Columns/Detail&columnId=○○

ぜひ今後の保活にお役立て下さいませ。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
*** {$siteName} ***
{$siteCatch}

メールでのお知らせ配信停止はこちら
{$siteUrl}?p=Regist/Alert
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
";
		return $body;
	}

	/**
	 *
	 * 指定時間以内に配信開始予定のメール取得
	 *
	 */
	public function getScheduled($min) {
		$phAnd = $bind = null;
		$phAnd .= " and SendMail.subject is not null and SendMail.body is not null ";
		$phAnd .= " and SendMail.sendEndTime is null ";
		$phAnd .= " and (SendMail.sendStartTime > CURRENT_TIMESTAMP - INTERVAL {$min} MINUTE and SendMail.sendStartTime < CURRENT_TIMESTAMP) ";
		$data = $this->get($bind, $phAnd);
		if ($data) {
			// 複数は運用上ありえない前提なので注意！
			return $data[0];
		}
	}

	/**
	 *
	 * 特殊タグ変更
	 *
	 */
	private function changeExTag(&$text, $userName) {
		$text = preg_replace("/##NAME##/um", "{$userName}", $text);
		// サイトURL
		$text = preg_replace("/##SITE_URL##/um", SITE_URL, $text);
		// サイト名
		$text = preg_replace("/##SITE_NAME##/um", SITE_NAME, $text);
	}

}










