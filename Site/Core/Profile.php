<?php

/**
 *
 * プロフィール
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Profile {

	/**
	 *
	 * 取得
	 *
	 */
	private function get($bind, $phAnd) {
		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
					  Profile.userId
					, Profile.insTime
					, Profile.updTime
					, Profile.nickname
					, Profile.profile
					, Profile.tel
					, Profile.authCheck
					, Profile.authCheckCnt
					, Profile.sex
					, Profile.birthday
					, Profile.searchPoint
					, Users.userDir
                FROM
                    Profile
                    JOIN Users ON Users.userId = Profile.userId
                WHERE
                    Profile.userId is not null
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

	/**
	 *
	 * ユーザーID から取得
	 *
	 */
	public function getByUserId($userId) {
		$phAnd = $bind = null;

		// userId
		$phAnd .= " and Profile.userId = :userId ";
		$bind['userId']['value'] = $userId;
		$bind['userId']['type'] = PDO::PARAM_STR;

		$data = $this->get($bind, $phAnd);
		if ($data) {
			$this->format($data);
			return $data[0];
		}
	}

	/**
	 *
	 * 整形
	 *
	 */
	public function format(&$data) {
		foreach ($data as $key => $value) {
			// 整形済み生年月日を追加
			$birthday = getDateInfo($value['birthday']);
			$data[$key]['birthdaySlash'] = $birthday['dateSlash'];
			// 年齢計算
			$data[$key]['age'] = getAgeByBirthday($value['birthday']);
			// プロフィール画像セット
			User::setProfImgPath($data[$key]);
			// カバー画像セット
			User::setCoverImgPath($data[$key]);
			// 自宅地点（保育園探しの拠点）
			$searchPoint = json_decode($value['searchPoint']);
			$data[$key]['searchPoint'] = (array)$searchPoint;
		}
	}

	/**
	 *
	 * 入力チェック
	 *
	 * ニックネーム（必須）
	 * 自己紹介
	 * 性別（必須）
	 * 生年月日（必須）
	 * 自宅地点（保育園探しの拠点）
	 *
	 */
	public function validate($input) {

		$errMessage = null;

		// ニックネーム
		//     必須
		//     全角20文字以内
		$validation = new Validation();
		if (!$validation->checkStringCnt($input['nickname'], 1, 20)) {
			return 'ニックネームは1文字以上～20文字以内で入力してください。';
		}

		// 自己紹介
		//     全角500文字以内
		if (!$validation->checkStringCnt($input['profile'], 0, 500)) {
			return 'プロフィールは500文字以内で入力してください。';
		}

		// 性別
		//     必須
		if (!isset($input['sex']) || !$input['sex']) {
			return '性別を選択してください。';
		}

		// 生年月日
		//     必須
		//     正しい日付（西暦 YYYYMMDD）
		if (!$input['birthday']) {
			return '生年月日を入力してください。';
		} elseif ($errMessage = $validation->checkDate($input['birthday'])) {
			return $errMessage;
		}

		// 自宅地点（保育園探しの拠点）
		if ($input['address'] && !$input['latitude']) {
			// 住所入力済みで緯度・経度なしの場合
			return '地図の位置を確定してください。';
		}

		// 電話番号
		/*
		if (!$input['tel']) {
			return '電話番号を入力してください。';
		} elseif ($errMessage = $validation->checkTel($input['tel'])) {
			return $errMessage;
		}*/

		// 規約への同意
		// 初回登録時のみ表示フォームに表示される。
		if (isset($input['agreeTerms']) && !$input['agreeTerms']) {
			return '利用規約への同意がありません。';
		}

		return $errMessage;
	}

	/**
	 *
	 * プロフィール登録・更新
	 *
	 */
	public function upsert($param, $userId) {
		$paramUpsert = array(
			'userId'            => array('value' => $userId               		, 'type' => PDO::PARAM_STR),
			'insTime'           => array('value' => date('Y-m-d H:i:s')         , 'type' => 'datetime'),
			'updTime'           => array('value' => date('Y-m-d H:i:s')         , 'type' => 'datetime'),
			'nickname'          => array('value' => $param['nickname']          , 'type' => PDO::PARAM_STR),
			'profile'           => array('value' => $param['profile']           , 'type' => PDO::PARAM_STR),
			'sex'               => array('value' => $param['sex']               , 'type' => PDO::PARAM_STR),
			'birthday'          => array('value' => $param['birthday']          , 'type' => PDO::PARAM_STR),
			'searchPoint'       => array('value' => $param['searchPoint']       , 'type' => PDO::PARAM_STR),
		);
		return Db::pdoUpsert('Profile', $paramUpsert);
	}
}





























