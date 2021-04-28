<?php

/**
 *
 * ユーザー
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

/**
 * ユーザー
 */
class User {

    /**
     * ユーザーID
     */
	public static $userId = null;

    /**
     * ユーザー情報
     */
	public static $userInfo = array();

    /**
     * プロフィール画像配置ディレクトリ
     */
	const PROF_IMG_DIR = 'images/user';

	/**
	 * サンプル画像配置ディレクトリ
	 */
	const PROF_SAMPLE_IMG_DIR = 'images/sample';

	/**
	 * プロフィールアイコン画像ファイル名
	 */
	public static $profIconName = 'profIcon.png';

	/**
	 * プロフィールカバー画像ファイル名
	 */
	public static $profCoverName = 'profCover.jpg';

	/**
	 *
	 * 未ログインでも必要なユーザー情報セット
	 *
	 * 未ログイン状態でも保持が必要なユーザーデータを
	 * セッションデータ、ユーザーデータに保持。
	 *
	 */
	protected function setUserInfoSession(&$userData) {
		// ログイン状態
		//     DB未登録の場合でも userLogin のみは保持する。
		$userData['userLogin'] = isset($_SESSION['userLogin']) ? $_SESSION['userLogin'] : null;

		// タイムスタンプ
		//     画像などのキャッシュ対策用。
		//     画像ファイル名末尾にパラメータとして付加。
		if (!isset($_SESSION['timestamp'])) {
			$_SESSION['timestamp'] = date('YmdHis');	// 秒まで
		}
		$userData['timestamp'] = $_SESSION['timestamp'];

		// 初期パラメータ（広告トラッキング用）
		if (!isset($_SESSION['initQuery'])) {
			$_SESSION['initQuery'] = SITE_URL_QUERY;
		}
		$userData['initQuery'] = $_SESSION['initQuery'];

		// 初期ヘッダ情報（各種調査用）
		if (!isset($_SESSION['initHeader'])) {
			// Logout でのログアウト直後のアクセス、Regist/Finish での強制ログアウト直後のアクセスも通る。
			if (WV_DEBUG) trace('$_SESSION[initHeader] 新規登録またはログアウト直後のアクセス発生');
			$_SESSION['initHeader'] = json_encode($_SERVER);;
		}
		$userData['initHeader'] = $_SESSION['initHeader'];
	}

	/**
	 *
	 * 日時関連の更新情報セット
	 *
	 * 更新対象
	 * lastSessStartTime：最終セッション開始日時
	 * accDailyCount：デイリーアクセス数
	 *
	 */
	protected function setUpdateTime($newSession, &$dataUser) {
		// 現在日時
		$curTime = date('Y-m-d H:i:s');
		// 本日の00:00分のタイムスタンプ
		$timeStampToday = strtotime(date("Y/m/d",strtotime("now")));
		// 前回のセッション開始時のタイムスタンプ
		$timeStampLast = strtotime($dataUser['lastSessStartTime']);
		// 更新する場合のアクセス数
		$accDailyCountNew = intval($dataUser['accDailyCount']) + 1;

		// デイリーアクセス数の更新
		$newAccessDaily = false;
		$paramUpdate = array();
		if ($timeStampToday > $timeStampLast) {
			// 前回のセッション開始日から日付が変わった場合、accDailyCount をカウントアップ
			// ※但し、Users 登録済みのユーザーが未ログイン状態の場合、
			//   未ログイン状態で発行された $_SESSION['userId'] はDB登録された userId と異なる。
			//   なのでその場合、lastSessStartTime, accDailyCount は正確な値にならない点に注意。
			//   アプリの場合は、常に UsersTemp に登録済みなので正確なはず。
			$paramUpdate['accDailyCount'] = $accDailyCountNew;
			$paramUpdate['lastSessStartTime'] = $curTime;
			$dataUser['accDailyCount'] = $accDailyCountNew;
			$dataUser['lastSessStartTime'] = $curTime;
			$newAccessDaily = true;
		}
		if ($newSession) {
			// ログイン後のセッションリスタートの場合
			if (!$newAccessDaily) {
				// セッション開始日時のみ更新
				$paramUpdate['lastSessStartTime'] = $curTime;
				$dataUser['lastSessStartTime'] = $curTime;
			}
		}
		return $paramUpdate;
	}


	/**
	 * 
	 * ユーザー情報取得
	 * 
	 * userId から取得する。
	 * ユーザー情報は、アクセス時にDB登録するので、必ず存在する前提。
	 * 
	 */
	public function getById($userId = null) {
		if (!$userId) {
			$userId = self::$userId;
			if (self::$userInfo) {
				return self::$userInfo;
			}
		}

        // userId
        $phAnd = " and Users.userId = :userId ";
       	$bind['userId']['value'] = $userId;
       	$bind['userId']['type'] = PDO::PARAM_STR;

		$data = $this->get($bind, $phAnd);
		if ($data) {
			$data = $data[0];
			// プロフィール画像セット
			$this->setProfImgPath($data);
			return $data;
		}
	}

	/**
	 *
	 * token から取得
	 *
	 */
	public function getByToken($token) {

		// token
		$phAnd = " and Users.token = :token ";
		$bind['token']['value'] = $token;
		$bind['token']['type'] = PDO::PARAM_STR;

		$data = $this->get($bind, $phAnd);
		if ($data) return $data[0];
	}

	/**
	 *
	 * deviceToken から取得
	 *
	 */
	public function getByDeviceToken($deviceToken) {

		// token
		$phAnd = " and Users.deviceToken = :deviceToken ";
		$bind['deviceToken']['value'] = $deviceToken;
		$bind['deviceToken']['type'] = PDO::PARAM_STR;

		$data = $this->get($bind, $phAnd);
		if ($data) return $data[0];
	}

	/**
	 *
	 * regToken から取得
	 *
	 */
	public function getByRegToken($regToken) {

		// token
		$phAnd = " and Users.regToken = :regToken ";
		$bind['regToken']['value'] = $regToken;
		$bind['regToken']['type'] = PDO::PARAM_STR;

		$data = $this->get($bind, $phAnd);
		if ($data) return $data[0];
	}

    /**
     * 
	 * ユーザー情報取得
	 * 
     */
    protected function get($bind, $phAnd) {
        $dbh = Db::connect();
        try {
            $sql = "
	            SELECT
					  Users.userId
					, Users.insTime
					, Users.updTime
					, Users.lastSessStartTime
					, Users.lastLoginTime
					, Users.pass
					, Users.mailAddress
					, Users.mailAddressNew
					, Users.accDailyCount
					, Users.showAppReviewCount
					, Users.status
					, Users.token
					, Users.tokenMakeTime
					, Profile.nickname
					, Profile.profile
					, Profile.searchPoint										
					, Users.userDir
					, Users.alertCnt
					, Users.taskCnt
					, Users.point
					, Users.pointTotal
					, Users.iosUid
					, Users.deviceToken
					, Users.iosInfo
					, Users.androidUid
					, Users.androidInfo
					, Users.regToken
					, Users.restriction
					, Users.memo
					, Users.fakeUser
					, AlertSetting.reviewUpdate
					, AlertSetting.reviewUpdatePush
					, AlertSetting.qaUpdate
					, AlertSetting.qaUpdatePush
					, AlertSetting.followUpdate
					, AlertSetting.followUpdatePush
	            FROM
	                Users 
	                LEFT JOIN AlertSetting ON AlertSetting.userId = Users.userId
	                LEFT JOIN Profile ON Profile.userId = Users.userId
	            WHERE
	                Users.userId is not null
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
       if ($data) {
        	$this->format($data);
	   }
		return $data;
    }

	/**
	 *
	 * ユーザー数取得
	 *
	 */
	protected function getCnt($bind, $phAnd) {
		$dbh = Db::connect();
		try {
			$sql = "
	            SELECT
					count(Users.userId) as cnt
	            FROM
	                Users 
	                -- LEFT JOIN AlertSetting ON AlertSetting.userId = Users.userId
	                -- LEFT JOIN Profile ON Profile.userId = Users.userId
	            WHERE
	                Users.userId is not null
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
		if ($data) {
			return $data[0]['cnt'];
		}
	}

	/**
	 *
	 * プロフィール画像パス取得
	 *
	 * images/user/Tvir1xMDFKF1PNBtSagO/profIcon.png	：プロフィールアイコン
	 * images/user/Tvir1xMDFKF1PNBtSagO/profConver.png	：カバーイラスト
	 *
	 */
    public static function getImagePath($userDir, $fileName, $showSample = true) {
		$sampleDir = self::PROF_SAMPLE_IMG_DIR;
		if (!$userDir) {
			$imgPath = "{$sampleDir}/{$fileName}";
		} else {
			if ($fileName !== User::$profIconName && $fileName !== User::$profCoverName) {
				// プロフィールアイコン、カバーイラスト以外の場合
				if (!strstr($fileName, 'school_')) {
					// 施設画像以外の場合
					showError(func_get_args(), '不正ファイル名検知');
				}
			}
			$imgDir = self::PROF_IMG_DIR . '/' . $userDir;
			$imgPath = "{$imgDir}/{$fileName}";
			if (!file_exists($imgPath) && $showSample) {
				// サンプル画像を表示
				$imgPath = "{$sampleDir}/{$fileName}";
			}
		}
	    return $imgPath;
    }

	/**
	 *
	 * プロフィールアイコンのパスセット
	 * 
	 * $data ：引数として userDir のみ必要。
	 *
	 */
    public static function setProfImgPath(&$data) {
		$imgPath = self::getImagePath($data['userDir'], User::$profIconName);
		// filemtime から取得
		// $_SESSION['timestamp'] だとサーバからのアクセスの場合にワーニングが出るので使わない。
		$timeStamp = date('YmdHis', filemtime($imgPath));
		$data['profImg'] = "{$imgPath}?upd={$timeStamp}";
	}

	/**
	 *
	 * カバーイラストのパスセット
	 *
	 */
	public static function setCoverImgPath(&$data) {
		$imgPath = self::getImagePath($data['userDir'], User::$profCoverName);
		// filemtime から取得
		// $_SESSION['timestamp'] だとサーバからのアクセスの場合にワーニングが出るので使わない。
		$timeStamp = date('YmdHis', filemtime($imgPath));
		$data['coverImg'] = "{$imgPath}?upd={$timeStamp}";
	}

	/**
	 *
	 * ユーザー情報取得（メールアドレスから）
	 *
	 */
	public function getUserByMailAddress($mailAddress) {
	    $dbh = Db::connect();
	    try {
	        $sql = "
	            SELECT
	                  userId
	                , updTime
	                , pass
	                , mailAddress
	                , status
	            FROM
	                Users
	            WHERE
	                mailAddress = :mailAddress
	                AND status != '退会済'   		-- 再入会可能
	                -- AND status != '強制退会済'	-- 再入会不可！
	            ";
	        $sth = $dbh->prepare($sql);
	        $sth->bindValue(':mailAddress', $mailAddress, PDO::PARAM_STR);
	        $sth->execute();
	        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
	    } catch (PDOException $e) {
	        showError($e);
	        throw $e;
	    }
		if ($data) {
			if (count($data) > 1) {
				showError($data, 'メールアドレスに重複があります。');
				return;
			}
			return $data[0];
		}
	}

	/**
	 * 
	 * ユーザー情報取得（電話番号から）
	 *
	 */
	private function getUserByTelNo($telNo) {
	    $dbh = Db::connect();
	    try {
	        $sql = "
	            SELECT
	                  userId
	                , updTime
	                , mailAddress
	                , tel
	                , status
	            FROM
	                Users
	            WHERE
	                tel = :telNo
	            ";
	        $sth = $dbh->prepare($sql);
	        $sth->bindValue(':telNo', $telNo, PDO::PARAM_STR);
	        $sth->execute();
	        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
	    } catch (PDOException $e) {
	        showError($e);
	        throw $e;
	    }
		if ($data) {
			if (count($data) > 1) {
				showError($data, '電話番号に重複があります。');
				return;
			}
			return $data[0];
		}
	}

	/**
	 *
	 * ユーザー情報新規登録
	 *
	 */
	public function add($param) {
		if ($dataUser = $this->getById(User::$userId)) {
			// Regist.php の時点で除外してるはずなのでDB登録ありならエラー
			showError(setLogDetail($dataUser, $param), 'DB登録済みユーザーの新規登録検知');
			return false;
		}
		// 専用ディレクトリ作成
		//     プロフ画像の配置等に利用。
		if (!$dirName = $this->createUserDir()) {
			showError($param, 'ユーザー専用ディレクトリ作成失敗');
			return false;
		}
		$paramUpsert = array(
			'insTime'           => array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
			'updTime'           => array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
			'lastSessStartTime' => array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
			'pass' 				=> array('value' => MD5($param['pass'])				, 'type' => PDO::PARAM_STR),
			// メアド認証前なので仮登録
			'status' 			=> array('value' => '仮登録'						, 'type' => PDO::PARAM_STR),
			'userDir' 			=> array('value' => $dirName						, 'type' => PDO::PARAM_STR),
			'mailAddressNew' 	=> array('value' => $param['mailAddress']			, 'type' => PDO::PARAM_STR),
			'token' 			=> array('value' => $param['token']					, 'type' => PDO::PARAM_STR),
			'tokenMakeTime' 	=> array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
			// 'ipAddress' 		=> array('value' => $_SERVER['REMOTE_ADDR']			, 'type' => PDO::PARAM_STR),
			'userAgent' 		=> array('value' => $_SERVER['HTTP_USER_AGENT']		, 'type' => PDO::PARAM_STR),
			'initHeader' 		=> array('value' => json_encode($_SERVER)			, 'type' => PDO::PARAM_STR),
			// 初回アクセス時パラメータ保存（経路特定用）
			'initQuery' 		=> array('value' => User::$userInfo['initQuery']	, 'type' => PDO::PARAM_STR),
			'accDailyCount' 	=> array('value' => 1								, 'type' => PDO::PARAM_INT),
			'showAppReviewCount'=> array('value' => User::$userInfo['showAppReviewCount'], 'type' => PDO::PARAM_INT),
		);

		// iOS
		if (PF === 'ios') {
			// ios の場合
			// UsersTemp からコピー
			$paramIos = array(
				'userId' 			=> array('value' => User::$userId					, 'type' => PDO::PARAM_STR),
				'appInsTime'        => array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
				'iosUid' 			=> array('value' => User::$userInfo['iosUid']		, 'type' => PDO::PARAM_STR),
				'deviceToken' 		=> array('value' => User::$userInfo['deviceToken']	, 'type' => PDO::PARAM_STR),
				'iosInfo' 			=> array('value' => User::$userInfo['iosInfo']		, 'type' => PDO::PARAM_STR),
			);
			$paramUpsert = array_merge($paramUpsert, $paramIos);
		} elseif (PF === 'android') {
			// android の場合
			// UsersTemp からコピー
			$paramAndroid = array(
				'userId' 			=> array('value' => User::$userId					, 'type' => PDO::PARAM_STR),
				'appInsTime'        => array('value' => date('Y-m-d H:i:s')				, 'type' => 'datetime'),
				'androidUid' 		=> array('value' => User::$userInfo['androidUid']	, 'type' => PDO::PARAM_STR),
				'regToken' 			=> array('value' => User::$userInfo['regToken']		, 'type' => PDO::PARAM_STR),
				'androidInfo' 		=> array('value' => User::$userInfo['androidInfo']	, 'type' => PDO::PARAM_STR),
			);
			$paramUpsert = array_merge($paramUpsert, $paramAndroid);
		} else {
			// WEBの場合
			// 2020/01/22 kidani Del ---> 廃止 同じユーザーの行が複数できるのを防ぐ
			// → userId の重複登録エラーにならないよう、未ログインで仮登録状態ならこの関数自体を通らず
			//    updateUsers で行を上書きするようにした。
			//
			// ユーザーID再発行
			//     登録直後にログアウトして別アカウントを新規登録すると、
			//     直前に登録したユーザーID（＝セッションID）と同じになる可能性があり、
			//     userId の重複登録エラーになるので、別のセッションIDを再発行する。
			// $userIdOld = $_SESSION['userId'];
			// $session_regenerate_id(true);    // 古いセッションを破棄（これ重要！）
			// $User::$userId = $_SESSION['userId'] = session_id();
			// $if (WV_DEBUG) debug("{$userIdOld} → {$_SESSION['userId']}", 'WEBユーザー新規登録用の userId 発行');
			// 2020/01/22 kidani Del <--- 廃止 同じユーザーの行が複数できるのを防ぐ
			$paramUpsert['userId'] = array('value' => User::$userId, 'type' => PDO::PARAM_STR);
		}
		return Db::pdoUpsert('Users', $paramUpsert);
	}

	/**
	 *
	 * ユーザー情報更新
	 *
	 */
	public function updateUsers($userId, $param) {
		if (!$this->getById($userId)) {
			showError($userId, 'Users に更新対象のユーザー情報未登録');
			return false;
		}
		$paramUpsert = array(
		      'userId'            => array('value' => $userId,             'type' => PDO::PARAM_STR)
			, 'updTime'           => array('value' => date('Y-m-d H:i:s'), 'type' => 'datetime')
		);
		// lastSessStartTime
		if (isset($param['lastSessStartTime']))
			$paramUpsert['lastSessStartTime'] = array('value' => $param['lastSessStartTime'], 'type' => 'datetime');
		// パスワード
		if (isset($param['pass']) && $param['pass']) {
			$paramUpsert['pass'] = array('value' => MD5($param['pass']), 'type' => PDO::PARAM_STR);
		}
		// mailAddress
		if (isset($param['mailAddress']))
			$paramUpsert['mailAddress'] = array('value' => $param['mailAddress'], 'type' => PDO::PARAM_STR);
		// mailAddressNew
		if (isset($param['mailAddressNew']))
			$paramUpsert['mailAddressNew'] = array('value' => $param['mailAddressNew'], 'type' => PDO::PARAM_STR);
		// token
		if (isset($param['token']))
			$paramUpsert['token'] = array('value' => $param['token'], 'type' => PDO::PARAM_STR);
		// token
		if (isset($param['tokenMakeTime']))
			$paramUpsert['tokenMakeTime'] = array('value' => $param['tokenMakeTime'], 'type' => PDO::PARAM_STR);
		// 会員ステータス
    	if (isset($param['status']))
    		$paramUpsert['status'] = array('value' => $param['status'], 'type' => PDO::PARAM_STR);
		// iosUid
		if (isset($param['iosUid']))
			$paramUpsert['iosUid'] = array('value' => $param['iosUid'], 'type' => PDO::PARAM_STR);
		// deviceToken
		if (isset($param['deviceToken']))
			$paramUpsert['deviceToken'] = array('value' => $param['deviceToken'], 'type' => PDO::PARAM_STR);
		// iosInfo
		if (isset($param['iosInfo']))
			$paramUpsert['iosInfo'] = array('value' => $param['iosInfo'], 'type' => PDO::PARAM_STR);
		// androidUid
    	if (isset($param['androidUid']))
    		$paramUpsert['androidUid'] = array('value' => $param['androidUid'], 'type' => PDO::PARAM_STR);
		// regToken
    	if (isset($param['regToken']))
    		$paramUpsert['regToken'] = array('value' => $param['regToken'], 'type' => PDO::PARAM_STR);
		// androidInfo
		if (isset($param['androidInfo']))
			$paramUpsert['androidInfo'] = array('value' => $param['androidInfo'], 'type' => PDO::PARAM_STR);
		// memo
		if (isset($param['memo'])) {
			$param['memo'] = ($param['memo'] ? $param['memo'] : null);
			$paramUpsert['memo'] = array('value' => $param['memo'], 'type' => PDO::PARAM_STR);
		}
		// restriction
		if (isset($param['restriction'])) {
			$param['restriction'] = ($param['restriction'] ? $param['restriction'] : null);
			$paramUpsert['restriction'] = array('value' => $param['restriction'], 'type' => PDO::PARAM_STR);
		}
		// accDailyCount
		if (isset($param['accDailyCount']))
			$paramUpsert['accDailyCount'] = array('value' => $param['accDailyCount'], 'type' => PDO::PARAM_INT);
		// showAppReviewCount
		if (array_key_exists('showAppReviewCount', $param)){	// ◆NULL更新ありなら array_key_exists にすること！
			$paramUpsert['showAppReviewCount'] = array('value' => $param['showAppReviewCount'], 'type' => PDO::PARAM_INT);
		}
		return Db::pdoUpsert('Users', $paramUpsert);
	}

	/**
	 *
	 * 売上金更新
	 * 
	 * 
	 * 現在の所持売上金、売上金総計を更新する。
	 *
	 */
	public function updateSales($userId, $addAmount) {
		$paramUpsert = array(
		      'userId'            => array('value' => $userId,             'type' => PDO::PARAM_STR)
			, 'updTime'           => array('value' => date('Y-m-d H:i:s'), 'type' => 'datetime')
			, 'sales'            => array('value' => $addAmount,            'type' => PDO::PARAM_INT, 'option' => " sales + :sales ")
		);
		if ($addAmount >= 0) {
			$paramUpsert['salesTotal'] = array('value' => $addAmount, 'type' => PDO::PARAM_INT, 'option' => " salesTotal + :salesTotal ");
		}
		return Db::pdoUpsert('Users', $paramUpsert);
	}
	
	/**
	 *
	 * ポイント更新
	 *
	 * 現在の所持ポイント、ポイント総計を更新する。
	 * 
	 */
	public function updatePoint($userId, $addPoint) {
		$paramUpsert = array(
		      'userId'		=> array('value' => $userId,             'type' => PDO::PARAM_STR)
			, 'updTime'		=> array('value' => date('Y-m-d H:i:s'), 'type' => 'datetime')
			, 'point'    	=> array('value' => $addPoint,            'type' => PDO::PARAM_INT, 'option' => " point + :point ")
		);
		if ($addPoint >= 0) {
			$paramUpsert['pointTotal'] = array('value' => $addPoint, 'type' => PDO::PARAM_INT, 'option' => " pointTotal + :pointTotal ");
		}
		return Db::pdoUpsert('Users', $paramUpsert);
	}
	
    /**
     * ユーザー削除（ID指定）
     */
	public function deleteUser($userId) {
	    $dbh = Db::connect();
	    try {
	         $sql = "DELETE FROM Users WHERE userId = :userId";
	        $sth = $dbh->prepare($sql);
	        $sth->bindValue(':userId', $userId, PDO::PARAM_STR);
	        $sth->execute();
			 $rowCount = $sth->rowCount();
	    } catch (PDOException $e) {
	        showError($e);
	       throw $e;
			return false;
	    }
		return $rowCount;
	}
	
	/**
	 * ユーザー数取得
	 */
	public function getUserCount($bind, $phAnd) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  count(Users.userId) as count
		        FROM
		            Users
		            left join Profile on Profile.userId = Users.userId
		            left join AlertSetting on AlertSetting.userId = Users.userId
	        	WHERE
	        		Users.userId is not null
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
		if ($data) return $data[0]['count'];
	}

	/**
	 * 
	 * ユーザー一覧取得
	 *
	 * 検索条件にマッチしたユーザー一覧を取得
	 *
	 */
	public function getPage($bind, $phAnd, $page, $sort) {
		// ソート列指定パラメータチェック
		//     bindValueは不可なのでホワイトリストチェックする。
		$arrSortCol = array(
			  'userId'
			, 'insTime'
			, 'lastLoginTime'
			, 'mailAddress'
			, 'status'
			, 'nickname'
		);
		if(!in_array($sort['sortCol'], $arrSortCol)){
			return array('error' => 'ソート列の指定パラメータが不正です。');
		}
		$phOrderBy = array(
			  'userId'				=> 'Users.userId'
			, 'insTime'				=> 'Users.insTime'
			, 'lastLoginTime'		=> 'Users.lastLoginTime'
			, 'nickname'			=> 'Profile.nickname'
		);
		$targetOrderBy = $phOrderBy[$sort['sortCol']];

		// ソート順指定パラメータチェック
		if(!in_array($sort['sortOrder'], array('desc', 'asc'))){
			return array('error' => 'ソート順の指定パラメータが不正です。');
		}

		// ユーザーデータ取得
		$dbh = Db::connect();
		try {
		    $sql = "
				select
					  Users.userId
					, Users.insTime
					, Users.lastLoginTime
					, Users.mailAddress
					, Users.status
					, Profile.nickname
					, Users.iosUid
					, Users.androidUid
					-- , Users.fakeUser
					, Users.restriction
				from 
					Users
					left join Profile on Profile.userId = Users.userId
					left join AlertSetting on AlertSetting.userId = Users.userId
				where
					Users.userId is not null
					$phAnd
					group by Users.userId
				order by $targetOrderBy {$sort['sortOrder']}
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
		$this->format($data);
		return $data;
	}

	/**
	 *
	 * トークン一覧取得
	 *
	 */
	public function getPageTokenList($bind, $phAnd, $page, $pf) {
		// トークン名
		if ($pf === 'android') {
			$tokenName = "regToken";
		} elseif ($pf === 'ios') {
			$tokenName = "deviceToken";
		}
		$dbh = Db::connect();
		try {
			$sql = "
				select
					Users.{$tokenName}
				from
					Users
				where
					Users.userId is not null
					$phAnd
				order by Users.insTime desc
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
		$tokenList = null;
		foreach ($data as $key => $value) {
			$tokenList[] = $value[$tokenName];
		}
		return $tokenList;
	}

	/**
	 *
	 * ユーザー一覧の整形
	 *
	 * 利用環境を付加情報追加している。
	 * 実際には全てのPFに所属することが可能。
	 *
	 */
	private function format(&$data) {
		if (!$data) return;
		foreach ($data as $key => $value) {
			// 利用環境
			$data[$key]['pf'] = 'web';
			if ($value['iosUid']) {
				// ios, android 双方で利用している場合、ios で識別している点に注意！
				$data[$key]['pf'] = 'ios';
			} elseif ($value['androidUid']) {
				$data[$key]['pf'] = 'and';
			}
			// 自宅地点
			if (isset($value['searchPoint'])) {
				$searchPoint = json_decode($value['searchPoint']);
				$data[$key]['searchPoint'] = (array)$searchPoint;
			}
		}
	}

	/**
	 *
	 * 戻りURLの保存
	 *
	 * 事前設定が必要なページに未設定の状態でアクセスして来た場合に、
	 * 設定完了時に対象ページに遷移できるよう、戻りURLを保存しておく。
	 * 
	 * ・ユーザー情報編集、購入履歴などログインが必要な全ページ
	 *     ログイン後の戻りURLを保存。  
	 *
	 * ・施設購入ページ
	 *     住所登録後の戻りURLを保存。
	 *     決済情報登録の戻りURLを保存。
	 *     電話番号認証後の戻りURLを保存。
	 * 
	 * 常に最新の1件しか保存しない。
	 * 
	 * $query	：保存するURL、またはURLの構成パラメータ。
	 * $title	：戻りURLリンクに表示する文言。
	 * 
	 * 
	 */
	public function saveBackUrl($userId, $query, $title = null) {
		if (is_array($query)) {
			// $query = SITE_URL . '?' . http_build_query($query);	
			$query = '?' . http_build_query($query);
		}
		$paramUpsert = array(
		      'userId'            => array('value' => $userId,             'type' => PDO::PARAM_STR)
			, 'updTime'           => array('value' => date('Y-m-d H:i:s'), 'type' => 'datetime')
			, 'url'               => array('value' => $query,              'type' => PDO::PARAM_STR)
			, 'title'             => array('value' => $title,              'type' => PDO::PARAM_STR)
		);
		return Db::pdoUpsert('BackUrl', $paramUpsert);
	}

	/**
	 *
	 * 保持した戻りURLの取得
	 *
	 */
	public function getBackUrl($userId) {
		$dbh = Db::connect();
	    try {
	        $sql = "
	            SELECT
					  userId
					, updTime
					, url
					, title
	            FROM
	                BackUrl
	            WHERE
	                userId = :userId
	            ";
	        $sth = $dbh->prepare($sql);
	        $sth->bindValue(':userId', $userId, PDO::PARAM_STR);
	        $sth->execute();
	        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
	    } catch (PDOException $e) {
	        showError($e);
	        throw $e;
	    }

		// 取得直後に保存データは不要になるので削除しておく。
		if (!$this->deleteBackUrl($userId)) {
			showError($userId, 'BackUrl データの削除に失敗しました。');
		}
		if ($data) {
			$data = $data[0];
			if (checkElapsedTime($data['updTime'], 1)) {
				// 10分以上経過していたら除外
				return;
			} else {
				$data['url'] = urldecode($data['url']);
				return $data;	
			}
		}
	}

	/**
	 *
	 * 戻りURLの削除
	 * 
	 */
	private function deleteBackUrl($userId) {
		$paramWhere = array(
			'userId' => array('value' => $userId, 'type' => PDO::PARAM_STR)
		);
		return Db::pdoDelete('BackUrl', $paramWhere);
	}
	
	/**
	 *
	 * メールアドレス重複チェック
	 *
	 */
	private function checkMailAddress($addressNew, $addressOld) {

		// 変更なしの場合
		if ($addressNew === $addressOld) {
			return true;
		}
		
		// 変更ありの場合
		$data = $this->getUserByMailAddress($addressNew);
		if ($data && $data['status'] === '登録済') {
			// 既に登録済みの場合
			return false;	
		}

		return true;
	}
	
	/**
	 *
	 * 電話番号重複チェック
	 * 
	 * 退会済み含め同じ電話番号は登録不可。
	 * 仮登録、退会済ユーザー含めて全体からチェック。
	 *
	 */
	public function checkTelNumber($telNo) {	
		if (self::$userInfo['tel'] !== $telNo) {
			// 自分の番号ではない場合
			if ($this->getUserByTelNo($telNo)) {
				// 既に登録済みの場合
				return false;	
			}
		}
		return true;
	}
	
	/**
	 *
	 * ユーザー専用ディレクトリ作成
	 * 
	 * プロフ画像の配置等に利用。
	 *
	 */
	public function createUserDir() {

		// ディレクトリ名生成
		$dirName = makeRandString(20);

		// ディレクトリ作成
		$dirPath = "images/user/{$dirName}";
		mkdir("{$dirPath}", 0777);
	    if (!file_exists($dirPath)) {
	    	showError($dirPath, 'ディレクトリ作成失敗');
			return false;
	    }
	    if (!chmod($dirPath, 0777)) {
	    	showError($dirPath, 'ディレクトリのパーミッション変更失敗');
			return false;   
	    }	
		return $dirName;
	}

    /**
     * 
	 * 会員登録バリデーション
	 * 
	 * 会員ID（メールアドレス）
	 * パスワード
	 * 
     */
    public function validate($input, $passCurrent) {

		// パスワード
		//     必須
		//     6～20文字（既存は無制限）
		//     半角英数字のみ
		if (!$passCurrent) {
			// 新規登録の場合
			if (!$input['pass']) {
				return 'パスワードを入力してください。';
			} elseif (!preg_match("/^[a-zA-Z0-9]+$/", $input['pass'])) {
				return 'パスワードは半角英数字のみで入力してください。';
			} elseif (strlen(bin2hex($input['pass'])) < 12 || strlen(bin2hex($input['pass'])) > 40) {
				// 半角1文字2バイト
				return 'パスワードは6以上～20文字以内で入力してください。';
			}
	
			// パスワード確認
			//     必須
			//     パスワードと合致
			if (!$input['passConfirm']) {
				return 'パスワードを入力してください。';
			} elseif ($input['pass'] !== $input['passConfirm']) {
				return 'パスワード確認とパスワードが一致しません。';
			}
		// } elseif ($input['pass'] || $input['passConfirm'] || $input['passCurrent']) {
		} elseif (isset($input['passChageCheck'])) {
			// 登録更新でパスワード変更ありの場合

			// 変更後のパスワード
			if (!preg_match("/^[a-zA-Z0-9]+$/", $input['pass'])) {
				return '変更後のパスワードは半角英数字のみで入力してください。';
			} elseif (strlen(bin2hex($input['pass'])) < 12 || strlen(bin2hex($input['pass'])) > 40) {
				// 半角1文字2バイト
				return '変更後のパスワードは6以上～20文字以内で入力してください。';
			}

			// 変更後のパスワード確認
			//     必須
			//     パスワードと合致
			if (!$input['passConfirm']) {
				return '変更後のパスワード確認を入力してください。';
			} elseif ($input['pass'] !== $input['passConfirm']) {
				return '変更後のパスワード確認と変更後のパスワードが一致しません。';
			}
			
			// 現在のパスワード
			//     DBのパスワードと合致
			//     変更後のパスワードと相違
			if (!$input['passCurrent']) {
				return '現在のパスワードを入力してください。';
			} elseif (md5($input['passCurrent']) !== $passCurrent) {
				return '現在のパスワードに誤りがあります。';
			} elseif (md5($input['passCurrent']) === md5($input['pass'])) {
				return '現在のパスワードと同じです。';
			}
			
			// パスワード変更チェック
			if ($input['passCurrent'] === $input['pass'] ) {
				// パスワード変更なしの場合
				return 'パスワードが同じです。';
			}
		}

		// メールアドレス
		//     必須
		//     書式
		$validation = new Validation();
		if (!$input['mailAddress']) {
			return 'メールアドレスを入力してください。';
		} elseif (!$validation->checkMail($input['mailAddress'])) {
			return 'メールアドレスの書式に間違いがあります。';
		}

		// メールアドレス重複登録チェック
		$curMailAddress = isset(self::$userInfo['mailAddress']) ? self::$userInfo['mailAddress'] : null;
		if (!$this->checkMailAddress($input['mailAddress'], $curMailAddress)) {
			return 'このメールアドレスは既に登録済みです。';
		}

		return null;
    }

	/**
	 *
	 * 認証チェック
	 * 
	 * SMS認証実行時処理。
	 *
	 */
	public function authCheck($token) {
	
		// 認証データなし
	    if (!self::$userInfo['token'] || !self::$userInfo['tokenMakeTime']) {
			return '認証が無効です。';
		}

		// 認証コード
	    if (self::$userInfo['token'] !== $token) {
			return '認証コードに誤りがあります。';
		}
		
		// 認証の有効時間チェック
		$tokenMakeTime = self::$userInfo['tokenMakeTime'];
		$makeTime = strtotime("{$tokenMakeTime} +6 hour");
		if ($makeTime < strtotime("now")) {
			// 6時間過ぎていたら無効
			return '認証コード発行から6時間経過したため認証は無効です。';
		}
		return null;
	}

	/**
	 *
	 * 運営専用のフェイクユーザーアカウントの userId 取得
	 *
	 */
	public function getFakeUserIdList() {
		// fakeUser
		$phAnd = " and Users.fakeUser = 1 ";
		$data = $this->get(null, $phAnd);

		$idlist = array();
		foreach ($data as $key => $value) {
			$idlist[] = $value['userId'];
		}
		return $idlist;
	}

	/**
	 *
	 * PUSH通知有効会員数取得
	 *
	 */
	public function getValidPushUserCnt($pf = null) {
		$phAnd = $bind = null;

		// PF, トークン
		if ($pf) {
			if ($pf === 'android') {
				// android
				// UUIDあり かつ 登録トークンあり かつ トークン長がある程度あり（通常は152文字ぐらい）
				$phAnd .= " AND (Users.androidUid is not null AND Users.regToken is not null AND CHAR_LENGTH(Users.regToken) > 120) ";
			} elseif ($pf === 'ios') {
				// iOS
				// iOS UUIDあり かつ デバイストークンあり かつ トークン長がある程度あり（通常は64文字ぐらい）
				$phAnd .= " AND (Users.iosUid is not null AND Users.deviceToken is not null AND CHAR_LENGTH(Users.deviceToken) > 50) ";
			}
		} else {
			$phAnd .= " AND ( (Users.androidUid is not null AND Users.regToken is not null AND CHAR_LENGTH(Users.regToken) > 120) "
				. " OR (Users.iosUid is not null AND Users.deviceToken is not null AND CHAR_LENGTH(Users.deviceToken) > 50) ) ";
		}

		// 会員ステータス
		// $phAnd .= " AND Users.status = '登録済' ";

		// 操作制限
		$phAnd .= " AND Users.restriction is null ";

		return $this->getCnt($bind, $phAnd);
	}
	
	/**
	 *
	 * PUSH通知有効会員取得
	 *
	 */
	public function getValidPushTokenList($pf, $countPerPage, $curPageNo) {
		$phAnd = $bind = null;

		// PF, トークン
		if ($pf) {
			if ($pf === 'android') {
				// android
				// UUIDあり かつ 登録トークンあり かつ トークン長がある程度あり（通常は152文字ぐらい）
				$phAnd .= " AND (Users.androidUid is not null AND Users.regToken is not null AND CHAR_LENGTH(Users.regToken) > 120) ";
			} elseif ($pf === 'ios') {
				// iOS
				// iOS UUIDあり かつ デバイストークンあり かつ トークン長がある程度あり（通常は64文字ぐらい）
				$phAnd .= " AND (Users.iosUid is not null AND Users.deviceToken is not null AND CHAR_LENGTH(Users.deviceToken) > 50) ";
			}
		} else {
			$phAnd .= " AND ( (Users.androidUid is not null AND Users.regToken is not null AND CHAR_LENGTH(Users.regToken) > 120) "
				. " OR (Users.iosUid is not null AND Users.deviceToken is not null AND CHAR_LENGTH(Users.deviceToken) > 50) ) ";
		}

		// 会員ステータス
		$phAnd .= " AND Users.status = '登録済' ";

		// 操作制限
		$phAnd .= " AND Users.restriction is null ";

		// ページ設定取得
		$page['countPerPage'] = $countPerPage;
		$page['curPageNo'] = $curPageNo;

		// トークン一覧取得
		return $this->getPageTokenList($bind, $phAnd, $page, $pf);
	}

	/**
	 *
	 * プラットフォーム取得
	 *
	 * web／android／ios
	 *
	 */
	public static function getPfType() {
		// userAgent によりプラットフォームを判定している。
		// アプリの userAgent には意図的に nativeAndroid／nativeIos を追加している。
		// ウェブではブラウザの設定か HTTP_USER_AGENT がない場合があるので注意！
		// アプリでは恐らくないと思うが、もしあったら対策必要！
		$pf = 'web';
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		if (strstr($agent, 'nativeAndroid')) {
			$pf = 'android';
		} elseif (strstr($agent, 'nativeIos')) {
			$pf = 'ios';
		}
		return $pf;
	}

	/**
	 *
	 * サンプル画像パス取得
	 *
	 */
	public static function getSamplePath() {
		$sampleDir = self::PROF_SAMPLE_IMG_DIR;
		$profIconName = self::$profIconName;
		$profCoverName = self::$profCoverName;
		$sampleImgPath['prof'] = "{$sampleDir}/{$profIconName}";
		$sampleImgPath['cover'] = "{$sampleDir}/{$profCoverName}";
		return $sampleImgPath;
	}

	/**
	 *
	 * メール配信向けリスト取得
	 *
	 */
	public function getListForMail($bind, $phAnd) {
		$dbh = Db::connect();
		try {
			$sql = "
			SELECT
				  Users.mailAddress
        		, Profile.nickname
        		, Users.userId
              FROM
                  Users
                  JOIN Profile ON Profile.userId = Users.userId
                  LEFT JOIN AlertSetting ON AlertSetting.userId = Users.userId
              WHERE
                  Users.userId is not null
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
		return $data;
	}
}





























