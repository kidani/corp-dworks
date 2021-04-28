<?php

/**
 *
 * ネイティブユーザー（Android）
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class UserAnd extends User {

	/**
	 *
	 * ユーザーID取得と初期化
	 *
	 */
	public function initUser($param, &$pageName) {
		//------------------------------------------------
		// セッション関連
		//------------------------------------------------
		if (isset($param['androidUid'])) {
			// UUID（androidUid）ありの場合
			if (!isset($_SESSION['androidUid'])) {
				// セッションなしの場合
				//     アプリ起動時、またはセッション切れによるリダイレクトで通る。
				// 全セッション初期化
				$_SESSION = array();
				$_SESSION['androidUid'] = $param['androidUid'];    			// UUID設定
				if (WV_DEBUG) debug($_SESSION, 'アプリ起動時の androidUid 取得成功');
			} else {
				// セッションありの場合
				// アプリを再起動したが、セッションが残留している場合、
				// ディープリンク経由で来た場合などにここを通る。
				// android では、アプリ再起動してもほとんどセッションが残留しているみたい。
				// $_SESSION['androidUid'] = $param['androidUid'];   // androidUid が変更されることはありえない。
				if (WV_DEBUG) debug($_SESSION, 'androidUid セッション取得済み');
			}
			// セッションの有無に関係なく更新
			// osVersion、versionCode はパラメータがあれば毎回更新する。
			// セッションなしの場合だけ更新すると、アプリバージョンアップ後のアクセスで
			// 起動前のセッションが残留していた場合、非対応バージョンと判定されてしまうので更新必須。
			$_SESSION['osVersion'] = $param['osVersion'];		// OSバージョン
			$_SESSION['versionCode'] = $param['versionCode'];	// アプリバージョンコード
		} else {
			if (!isset($_SESSION['androidUid'])) {
				// UUID（androidUid）なしの場合
				//     セッション切れで通るルート

				// リダイレクトループ防止
				if (!isset($_SESSION['getUuidRedirectCnt'])) {
					// リダイレクしてUUIDを取得
					$_SESSION['getUuidRedirectCnt'] = 1;
					unset($param['getUuid']);
					if (ACCESS === 'USER') {
						$query = '&' . http_build_query($param);
					} else {
						// TODO：Ajax ページはリダイレクト指定できないのでトップへ
						// 通常ありえないはずだが発生したので応急処置。
						$query = '';
					}
					$url = SITE_URL . "?getUuid=1{$query}";
					if (WV_DEBUG) errorLog(setLogDetail($url, $_SESSION), 'セッション切れによる androidUid 取得実行リダイレクト発生');
					header("location:{$url}");
					exit;
				} else {
					if ($_SESSION['getUuidRedirectCnt'] >= 1) {
						// 初回で取得できなければエラー
						errorLog($_SESSION, 'リダイレクトループ発生');
						unset($_SESSION['getUuidRedirectCnt']);
						$pageName = 'Error/Error';
						// exit;
					}
				}
			}
		}

		// androidUid 確定
		$androidUid = $_SESSION['androidUid'];

		//------------------------------------------------
		// ユーザー情報
		//------------------------------------------------
		$dataUser = null;
		$newSession = false;
		if (!isset($_SESSION['userId'])) {
			$newSession = true;

			// UUIDからユーザー情報取得
			if ($dataUser = $this->getByUuid($androidUid)) {
				// ユーザー情報とUUIDの紐付け済みの場合
				$_SESSION['userId'] = $dataUser['userId'];
				if ($dataUser['status'] === '登録済') {
					// アクセスした時点でログイン済みに変更
					$_SESSION['userLogin'] = true;
				}
			} else {
				// ユーザー情報未登録、またはユーザー情報とUUIDの紐付けがまだの場合
				// 仮のセッション：userId 発行
				session_regenerate_id(true);     		// 古いセッションを破棄（これ重要！）
				$_SESSION['userId'] = session_id();
				if (WV_DEBUG) trace("仮のセッション：userId 発行：{$_SESSION['userId']}");
			}
		}

		// ユーザーID確定
		// UsersTemp に登録されていた場合は上書きありなので注意！
		$userId = self::$userId = $_SESSION['userId'];

		// ユーザー情報取得
		if (!$dataUser) {
			$dataUser = $this->getById($userId);
		}

		if ($dataUser) {
			// 会員登録済みの場合

			// 日時関連の更新情報セット
			$paramUpdate = $this->setUpdateTime($newSession, $dataUser);

			// UUID更新
			//     アプリ再インストール後に以前からのアカウントでログインされた際に更新必要。
			if (!$dataUser['androidUid'] || $dataUser['androidUid'] !== $androidUid) {
				$paramUpdate['androidUid'] = $androidUid;
				$dataUser['androidUid'] = $androidUid;
				if (WV_DEBUG) trace("既存アカウントの androidUid 更新：{$androidUid}");
				// UsersTemp に新しい $androidUid があるはずなので、デーバイストークン取得して削除
				$usersTemp = new UsersTemp();
				$dataUserTemp = $usersTemp->getByUuid($androidUid);
				if ($dataUserTemp) {
					// アプリ側のデータ移行
					if ($dataUserTemp['regToken']) {
						$paramUpdate['regToken'] = $dataUserTemp['regToken'];
					}
					if ($dataUserTemp['androidInfo']) {
						$paramUpdate['androidInfo'] = $dataUserTemp['androidInfo'];
					}
					if ($dataUserTemp['showAppReviewCount']) {
						$paramUpdate['showAppReviewCount'] = $dataUserTemp['showAppReviewCount'];
					}
					$usersTemp->deleteByUserId($dataUserTemp['userId']);
				} else {
					errorLog($dataUser, 'UsersTemp に対応データなし');
				}
			}

			if ($newSession) {
				// androidInfo 更新
				$androidInfo = json_decode($dataUser['androidInfo']);
				if ($androidInfo->osVersion !== $_SESSION['osVersion']
					|| $androidInfo->versionCode !== $_SESSION['versionCode']) {
					$paramUpdate['androidInfo'] = json_encode(
						array('osVersion' => $_SESSION['osVersion'],
							  'versionCode' => $_SESSION['versionCode']
						));
					if (WV_DEBUG) trace('バージョン更新発生');
				}
			}

			// Users 更新
			if ($paramUpdate) {
				$this->updateUsers($userId, $paramUpdate);
			}
		} else {
			// 会員未登録の場合
			$usersTemp = new UsersTemp();
			$dataUser = $usersTemp->getByUuid($androidUid);
			if ($dataUser) {
				// UsersTemp 登録済みの場合
				if ($dataUser['userId'] !== $userId) {
					// UsersTemp.userId と セッション：userId が相違する場合
					// UsersTemp 登録済みの userId で上書き
					$_SESSION['userId'] = $userId = $dataUser['userId'];
					if (WV_DEBUG) trace("セッション：userId を UsersTemp.userId：{$userId} で上書き");
					// 日時関連の更新情報セット
					$paramUpdate = $this->setUpdateTime($newSession, $dataUser);
					if ($paramUpdate) {
						if (!$usersTemp->upsert($userId, $paramUpdate)) {
							errorLog($paramUpdate, 'initUser - UsersTemp 更新失敗');
						} else {
							if (WV_DEBUG) trace('initUser - UsersTemp 更新成功');
						}
					}
				}
			} else {
				// UsersTemp 未登録の場合
				if (isset($_SESSION['versionCode']) && $_SESSION['versionCode']) {
					// UsersTemp 新規追加
					$paramAdd['userId'] = $userId;
					$paramAdd['androidUid'] = $androidUid;
					$paramAdd['initQuery'] = SITE_URL_QUERY;
					$paramAdd['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
					$paramAdd['initHeader'] = json_encode($_SERVER);
					$paramAdd['lastSessStartTime'] = date('Y-m-d H:i:s');
					$paramAdd['accDailyCount'] = 1;
					$paramAdd['showAppReviewCount'] = 5;		// 初期設定：5（デイリーユニークで5日アクセスしたら表示）
					$paramAdd['androidInfo'] = json_encode(
						array('osVersion' => $_SESSION['osVersion'],
							  'versionCode' => $_SESSION['versionCode']
						));
					$dataUser = $paramAdd;
					if (!$usersTemp->upsert($userId, $paramAdd)) {
						errorLog($paramAdd, 'initUser - UsersTemp 新規登録失敗');
					} else {
						if (WV_DEBUG) trace('initUser - UsersTemp 新規登録成功');
					}
				} else {
					// アプリの versionCode を取得しない旧バージョンは除外
					if (WV_DEBUG) trace('versionCode 未取得により UsersTemp 新規登録対象外検知');
				}
			}
		}

		// 未ログインでも必要なユーザー情報セット
		$this->setUserInfoSession($dataUser);

		// userInfo にセット
		self::$userInfo = $dataUser;

		// バージョンチェック
		if (!$this->checkVersion()) {
			$pageName = 'Error/ErrorVersion';
		}

		// アプリ内レビュー誘導ダイアログ表示
		// 実装を間違えると無限ループになるので注意！
		// パラメータ：showReviewAlert がある場合除外
		// 評価処理ページ：Regist/NativeReviewは除外
		if (!isset($param['showReviewAlert']) && $dataUser['showAppReviewCount'] && $pageName !== 'Regist/NativeReview'
			&& ($dataUser['accDailyCount'] >= $dataUser['showAppReviewCount'])) {
			$url = SITE_URL . "?showReviewAlert=1";
			header("location:{$url}");
			if (WV_DEBUG) debug($url, 'アプリ内レビュー誘導ダイアログ表示によるリダイレクト発生');
			exit;
		}
	}

	/**
	 *
	 * バージョンチェック
	 *
	 */
	private function checkVersion() {
		// アプリのOSバージョンチェック
		if (!isset($_SESSION['osVersion'])) {
			errorLog($_SESSION, 'osVersion 取得失敗');
			return false;
		} else {
			if (!checkVersion($_SESSION['osVersion'], UserConfig::$conf['AppOsVersion'][PF])) {
				if (WV_DEBUG) debug($_SESSION['osVersion'], 'OSバージョンアップが必要な端末からのアクセス');
				return false;
			}
		}
		// アプリのビルドバージョンチェック（versionCode）
		// ブラウザ側のバージョンアップと同期が必要な場合にチェック必須。
		if (!isset($_SESSION['versionCode'])) {
			// 旧バージョンの場合取得不可
			// errorLog($_SESSION, 'versionCode 取得失敗');
			return false;
		} else {
			if (!checkVersion($_SESSION['versionCode'], UserConfig::$conf['AppVersionCode'][PF])) {
				if (WV_DEBUG) debug($_SESSION['versionCode'], 'アプリバージョンアップが必要な端末からのアクセス');
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * ユーザー情報取得
	 *
	 * UUIDから取得する。
	 *
	 */
	public function getByUuid($uuid) {
		// androidUid
		$phAnd = " and Users.androidUid = :uuid ";

		$bind['uuid']['value'] = $uuid;
		$bind['uuid']['type'] = PDO::PARAM_STR;

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
	 * 登録トークン削除
	 *
	 * 無効な登録トークンが検知されたら削除する。
	 *
	 */
	public function deleteRegToken($token) {
		// Users から検索
		$target = array();
		if ($data = $this->getByRegToken($token)) {
			$userDb = 'Users';
			$target['table'] = $userDb;
			$target['userData'] = $data;
		} else {
			// UsersTemp から検索
			$userDb = 'UsersTemp';
			$usersTemp = new UsersTemp();
			$data = $usersTemp->getByRegToken($token);
			$target['table'] = $userDb;
			$target['userData'] = $data;
		}
		if (!$data) {
			showError($token, '削除対象の regToken なし');
			return;
		}
		$paramUpdate = array(
			'updTime' => array('value' => date('Y-m-d H:i:s'), 'type' => 'datetime'),
			'regToken' => array('value' => null, 'type' => PDO::PARAM_STR)
		);
		$paramWhere = array(
			'regToken' => array('value' => $token, 'type' => PDO::PARAM_STR)
		);
		if (!Db::pdoUpdate($userDb, $paramUpdate, $paramWhere)) {
			showError($target, 'エラー：登録トークン削除失敗');
		} else {
			if (WV_DEBUG) debug($target, '登録トークン削除成功');
		}
	}

	/**
	 *
	 * 端末登録トークン更新
	 *
	 */
	public function updateRegToken($uuid, $param) {

		$userId = null;
		$dataUser = $this->getByUuid($uuid);

		// 端末登録トークンが有効かチェック
		$regToken = null;
		if ($param['regToken']) {
			$regToken = $param['regToken'];
		}
		if ($dataUser) {
			$userId = $dataUser['userId'];
			if ($regToken && $dataUser['regToken'] !== $regToken) {
				// 相違ありなら更新
				$updateParam['regToken'] = $regToken;
				$this->updateUsers($dataUser['userId'], $updateParam);
				if (WV_DEBUG) debug($dataUser['regToken'] . ' → ' . $regToken, 'Users 端末登録トークン更新発生');
			} else {
				if (WV_DEBUG) trace('Users 端末登録トークン更新なし');
			}
		} else {
			$usersTemp = new UsersTemp();
			$dataUser = $usersTemp->getByUuid($uuid);
			if ($dataUser) {
				$userId = $dataUser['userId'];
				if ($regToken && $dataUser['regToken'] !== $regToken) {
					// 相違ありなら更新
					$updateParam['regToken'] = $regToken;
					$usersTemp->upsert($dataUser['userId'], $updateParam);
					if (WV_DEBUG) debug($dataUser['regToken'] . ' → ' . $regToken, 'UsersTemp 端末登録トークン更新発生');
				} else {
					if (WV_DEBUG) trace('UsersTemp 端末登録トークン更新なし');
				}
			} else {
				// UsersTemp 未登録の場合
				// 端末登録トークン通知の方がアプリ起動時のウェブビューロードからのアクセスより
				// 早くなった場合に発生するが、トークン通知処理にスリープを入れたので発生しなくなったはず！
				// errorLog(setLogDetail($param, $uuid), '端末登録トークン更新対象のUUID未登録検知');		// TODO：頻発するので当分停止
			}
		}
		return $userId;
	}
}

