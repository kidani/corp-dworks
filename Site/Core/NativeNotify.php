<?php

/**
 *
 * ネイティブアプリユーザーへのお知らせ通知
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class NativeNotify
{

	/**
	 *
	 * お知らせ通知取得
	 *
	 */
	public function get($bind, $phAnd)
	{
		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
					  id
					, insTime
					, title
					, detail
					, linkPage
					, iosOkCnt
					, iosNgCnt
					, androidOkCnt
					, androidNgCnt					
                FROM
                    Notify
                WHERE
                    id is not null
                    $phAnd
                ORDER BY insTime DESC
                ";
			$sth = $dbh->prepare($sql);
			Db::bindValueList($sth, $bind);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data;
	}

	/**
	 *
	 * お知らせ通知取得
	 *
	 * id から取得
	 *
	 */
	public function getById($id)
	{
		$phAnd = $bind = null;

		// id
		$phAnd .= " and Notify.id = :id ";
		$bind['id']['value'] = $id;
		$bind['id']['type'] = PDO::PARAM_INT;

		$data = $this->get($bind, $phAnd);
		if ($data) {
			return $data[0];
		}
	}

	/**
	 *
	 * お知らせ通知数取得
	 *
	 */
	public function getCnt($bind, $phAnd)
	{
		$dbh = Db::connect();
		try {
			$sql = "
	            SELECT
	            	count(id) as cnt
	            FROM
	               Notify
	            WHERE
	                id is not null
	            	$phAnd
	            ";
			$sth = $dbh->prepare($sql);
			Db::bindValueList($sth, $bind);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		if ($data) return $data[0]['cnt'];
	}

	/**
	 *
	 * お知らせ通知取得（ページ単位）
	 *
	 */
	public function getPage($bind, $phAnd, $page, $sort)
	{

		// ORDER句取得						
		$phOrderBy = $this->getPhOrderBy($sort);

		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
					  id
					, insTime
					, title
					, detail
					, linkPage
					, iosOkCnt
					, iosNgCnt
					, androidOkCnt
					, androidNgCnt					
                FROM
                    Notify
	            WHERE
	            	id is not null
	                $phAnd
	            $phOrderBy
	            LIMIT :offset, :rowCount
	            ";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':offset', intval(($page['curPageNo'] - 1) * $page['countPerPage']), PDO::PARAM_INT);
			$sth->bindValue(':rowCount', intval($page['countPerPage']), PDO::PARAM_INT);
			Db::bindValueList($sth, $bind);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data;
	}

	/**
	 *
	 * ソート用のORDER句取得
	 *
	 * $sort['sortCol']        ：$sortColList のキーを指定
	 * $sort['sortOrder']    ：asc／desc
	 *
	 */
	private function getPhOrderBy(&$sort)
	{
		if (!$sort) return;

		// Columns のソート指定カラムリスト
		$sortColList = array(
			'id'    => 'Notify.id'
		, 'insTime' => 'Notify.insTime'
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
		$phOrderBy = " ORDER BY $sortCol {$sort['sortOrder']}, Notify.id ";

		return $phOrderBy;
	}

	/**
	 *
	 * お知らせ通知履歴追加
	 *
	 */
	public function add($param)
	{
		$paramInsert = array(
			'insTime'    	=> array('value' => date('Y-m-d H:i:s'), 	'type' => 'datetime'),
			'title'    	 	=> array('value' => $param['title'], 		'type' => PDO::PARAM_STR),
			'detail'       => array('value' => $param['detail'], 		'type' => PDO::PARAM_STR),
			'linkPage'     => array('value' => $param['linkPage'], 		'type' => PDO::PARAM_STR),
			'iosOkCnt'     => array('value' => $param['iosOkCnt'], 		'type' => PDO::PARAM_INT),
			'iosNgCnt'     => array('value' => $param['iosNgCnt'], 		'type' => PDO::PARAM_INT),
			'androidOkCnt' => array('value' => $param['androidOkCnt'], 	'type' => PDO::PARAM_INT),
			'androidNgCnt' => array('value' => $param['androidNgCnt'], 	'type' => PDO::PARAM_INT),
		);
		if (!Db::pdoInsert('Notify', $paramInsert)) {
			showError($paramInsert, 'Notify 追加失敗');
			return false;
		}
		return true;
	}

	/**
	 *
	 * お知らせ通知送信
	 *
	 * $detail    ：通知テキスト
	 *
	 */
	public function pushRemoteAll($sendInfo)
	{
		// 送信時間計測開始
		$starttime = microtime(true);

		// 送信結果カウント初期化
		$push = new Push();
		$result = array(			// 送信結果カウント初期化
			'iosOkCnt' 		=> 0,
			'iosNgCnt' 		=> 0,
			'androidOkCnt' 	=> 0,
			'androidNgCnt' 	=> 0);
		$push->pushRemoteAll($sendInfo, $result);

		// 送信履歴登録
		$result['title'] = $sendInfo['title'];
		$result['detail'] = $sendInfo['detail'];
		$result['linkPage'] = $sendInfo['linkPage'];
		$this->add($result);

		// 送信時間計測終了
		$interval = microtime(true) - $starttime;
		checkLog($result, "PUSH通知送信時間：{$interval}秒");
		if (WV_DEBUG) debug($result, 'プッシュ通知送信履歴追加成功');

		// システム管理元へ監視用メール送信
		$mail = new Mail();
		$detail = "\nタイトル：{$sendInfo['title']}\n";
		$detail .= "通知内容：{$sendInfo['detail']}\n";
		$detail .= "リンク先：{$sendInfo['linkPage']}\n";
		$detail .= "ios 送信結果（成功数／失敗数）：{$result['iosOkCnt']}／{$result['iosNgCnt']}\n";
		$detail .= "android 送信結果（成功数／失敗数）：{$result['androidOkCnt']}／{$result['androidNgCnt']}\n";
		$detail .= "送信にかかった時間：{$interval}秒\n";
		$subject = "プッシュ通知の一括送信が実行されました。";
		$mail->sendToSystemAdmin($subject, $detail);

		return true;
	}

	/**
	 *
	 * お知らせ通知送信（1ユーザーのみ）
	 *
	 */
	public function pushRemote($userId, $sendInfo)
	{
		$push = new Push();

		// 送信対象ユーザー取得
		$user = new User();
		$userInfo = $user->getById($userId);

		$resultUser = array(			// 送信結果カウント初期化
			'iosOkCnt' 		=> 0,
			'iosNgCnt' 		=> 0,
			'androidOkCnt' 	=> 0,
			'androidNgCnt' 	=> 0);
		if ($userInfo) {
			// PUSH通知送信
			$push->pushRemote($userInfo, $sendInfo, $resultUser);
			if (WV_DEBUG) debug($resultUser, 'Users のプッシュ通知送信完了');
		}

		// 送信対象ユーザー取得
		$usersTemp = new UsersTemp();
		$userInfoTemp = $usersTemp->getById($userId);

		$resultUserTemp = array(			// 送信結果カウント初期化
			'iosOkCnt' 		=> 0,
			'iosNgCnt' 		=> 0,
			'androidOkCnt' 	=> 0,
			'androidNgCnt' 	=> 0);
		if ($userInfoTemp) {
			// PUSH通知送信
			$push->pushRemote($userInfoTemp, $sendInfo, $resultUserTemp);
			if (WV_DEBUG) debug($resultUserTemp, 'UsersTemp のプッシュ通知送信完了');
		}

		$result['iosOkCnt'] = $resultUser['iosOkCnt'] + $resultUserTemp['iosOkCnt'];
		$result['iosNgCnt'] = $resultUser['iosNgCnt'] + $resultUserTemp['iosNgCnt'];
		$result['androidOkCnt'] = $resultUser['androidOkCnt'] + $resultUserTemp['androidOkCnt'];
		$result['androidNgCnt'] = $resultUser['androidNgCnt'] + $resultUserTemp['androidNgCnt'];

		// 送信履歴登録
		if ($userInfo || $userInfoTemp) {
			$result['title'] = $sendInfo['title'];
			$result['detail'] = $sendInfo['detail'];
			$result['linkPage'] = $sendInfo['linkPage'];
			$this->add($result);
			if (WV_DEBUG) debug($result, 'プッシュ通知送信履歴追加成功');
		} else {
			showError('指定されたユーザーはプッシュ通知送信対象外です。');
			return false;
		}
		return true;
	}

	/**
	 *
	 * バリデーション
	 *
	 */
	public function validate($input) {

		$validation = new Validation();

		// 通知タイトル
		//     20文字以内
		if (!$validation->checkStringCnt($input['title'], 1, 20)) {
			return 'タイトルは20文字以内で入力してください。';
		}

		// 通知本文
		// 		必須、200文字以内
		// 		iOS, android で送信可能なペイロードの最大サイズは4KB（4096 bytes）なので、全角文字3バイト計算で全角1365文字程度送信可能だが、
		//      android で600文字だと見切れて表示できないので200文字にしておく。
		if (!$input['detail']) {
			return '通知内容を入力してください。';
		} elseif (!$validation->checkStringCnt($input['detail'], 1, 200)) {
			return '通知内容は200文字以内で入力してください。';
		}

		// 送信対象ユーザーID
		if (!$input['sendType']) {
			return '送信対象ユーザーを選択してください。';
		} elseif ($input['sendType'] === 'one' && !$input['userId']) {
			return '送信対象ユーザーIDを指定してください。';
		}

		return null;
	}
}





































