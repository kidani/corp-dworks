<?php

/**
 *
 * ウェブユーザー（Web）
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class UserWeb extends User {

	/**
	 *
	 * ユーザーID取得と初期化
	 *
	 */
	public function initUser($param, &$pageName) {
		//------------------------------------------------
		// セッション関連
		//------------------------------------------------
		$newSession = false;
		if (!isset($_SESSION['userId'])) {
			$newSession = true;
			// 仮のセッション：userId 発行
			$_SESSION = array();
			session_regenerate_id(true);    // 古いセッションを破棄（これ重要！）
			$_SESSION['userId'] = session_id();
		}
		// ユーザーID確定
		$userId = self::$userId = $_SESSION['userId'];

		// ユーザー情報取得
		$dataUser['userId'] = $userId;
		//$dataUser = $this->getById($userId);
		//if ($dataUser) {
		//	// DB登録済みの場合
		//	// 日時関連の更新情報セット
		//	$paramUpdate = $this->setUpdateTime($newSession, $dataUser);
		//	// Users 更新
		//	if ($paramUpdate) {
		//		$this->updateUsers($userId, $paramUpdate);
		//	}
		//} else {
		//	$dataUser['userId'] = $userId;
		//}

		// 未ログインでも必要なユーザー情報セット
		$this->setUserInfoSession($dataUser);

		// userInfo にセット
		self::$userInfo = $dataUser;

		// バージョンチェック（現状ウェブは全許可）
		//if (!$this->checkVersion()) {
		// 	// 非対応端末ならエラーページへ（機種制限する場合有効化）
		//	$pageName = 'Error/ErrorVersion';
		//}
	}

	/**
	 *
	 * バージョンチェック
	 *
	 */
	private function checkVersion() {
		if (!defined('PF') || !defined('NATIVE') || !UserConfig::$conf) {
			errorLog('未設定変数検知');
			return true;
		}
		// ウェブのOSバージョンチェック
		if (!checkVersion($_SESSION['osVersion'], UserConfig::$conf['WebOsVersion'])) {
			if (WV_DEBUG) debug($_SESSION['osVersion'], 'OSバージョンアップが必要な端末からのアクセス');
			return false;
		}
		return true;
	}
}










