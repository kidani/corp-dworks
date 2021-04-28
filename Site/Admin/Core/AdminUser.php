<?php

/**
 *
 * 管理ユーザー
 * 
 */
class AdminUser {
	
	/**
	 * 管理ユーザー初期化
	 */	
	public function initAdminUser() {	
		$adminId = isset($_SESSION['adminId']) ? $_SESSION['adminId'] : null;
		if ($adminId) {			
			return $this->getAdminUserById($adminId);	
		}
	}

	/**
	 * 管理ユーザー取得
	 */	 
	public function getAdminUser($col1, $col2 = null) {
		$phAnd = null;
		if ($col2) $phAnd = " and AdminUser.{$col2['key']} = :{$col2['key']} ";
		$dbh = Db::connect();
	    try { 
	        $sql = " SELECT
	                      adminId
	                    , insTime
	                    , updTime
	                    , loginId
	                    , pass
	                    , name
	                    , authType 
	                FROM AdminUser
	                WHERE AdminUser.{$col1['key']} = :{$col1['key']}
	                $phAnd
	                ";
	        $sth = $dbh->prepare($sql);
	        $sth->bindValue("{$col1['key']}", $col1['value'], $col1['type']);
			 if ($col2)  $sth->bindValue("{$col2['key']}", $col2['value'], $col2['type']);
	        $result = $sth->execute();
	        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
	    } catch (PDOException $e) {
	        showError($e->getMessage());
	    } catch (Exception $e) {
	        showError($e->getMessage());
	    }
		if (isset($data) && $data) return $data[0];
	}
			
	/**
	 * 管理ユーザー取得（AdminUser.adminIdから）
	 */	 
	public function getAdminUserById($adminId) {
		$col['key'] = 'adminId';
		$col['value'] = $adminId;
		$col['type'] = PDO::PARAM_INT;
		return $this->getAdminUser($col);
	}

	/**
	 * 管理ユーザー取得（ログインID, パスワードから）
	 */	 
	public function getAdminUserByLoginIdPass($loginId, $pass) {
		$col1['key'] = 'loginId';
		$col1['value'] = $loginId;
		$col1['type'] = PDO::PARAM_STR;
		$col2['key'] = 'pass';
		$col2['value'] = MD5($pass);
		$col2['type'] = PDO::PARAM_STR;
		return $this->getAdminUser($col1, $col2);
	}	
	
	/**
	 * 管理ユーザーリスト取得
	 */	 
	public function getAdminUserList() {
		$dbh = Db::connect();
	    try { 
	        $sql = " SELECT
	                      AdminUser.adminId
	                    , AdminUser.loginId
	                    , AdminUser.name
	                    , AdminUser.insTime
	                    , AdminUser.updTime
	                    , AdminUser.authType
	                FROM AdminUser
	                ";
	        $sth = $dbh->prepare($sql);
	        $result = $sth->execute();
	        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
	    } catch (PDOException $e) {
	        showError($e->getMessage());
	    } catch (Exception $e) {
	        showError($e->getMessage());
	    }
		return $data;
	}
	
	/**
	 * 管理ユーザー削除
	 */	
	public function deleteAdminUser($adminId) {
		$dbh = Db::connect();
		try {
			$sql = "
			    DELETE FROM
					AdminUser
			    WHERE
			        adminId = :adminId
			";
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':adminId', $adminId, PDO::PARAM_INT);
		    $result = $sth->execute();
		} catch (PDOException $e) {
		    showError($e->getMessage());
		} catch (Exception $e) {
		    showError($e->getMessage());
		}	
	}
	
	/**
	 * 管理ユーザー登録の入力データチェック
	 */	
	public function validateAdminUserParam($param) {
		$errorMessage = array();
	    // ログインID
	    if ((strlen($param['loginId']) > 32 || strlen($param['loginId']) < 3)) {
	        $errorMessage[] = "ログインIDは3文字以上～32文字以下で入力して下さい。";
	    }
	    if (!preg_match("/^[a-zA-Z0-9]+$/", $param['loginId'])) {
	        $errorMessage[] = "ログインIDは半角英数で入力して下さい。";
	    }
	    // パスワードのチェック
		if ($param['pass'] !== '＊＊＊＊＊') {
			// 「＊＊＊＊＊」の場合変更なしとみなす
		    if ((strlen($param['pass']) > 32 || strlen($param['pass']) < 4)) {	
		        $errorMessage[] = "パスワードは4文字以上～16文字以内で入力して下さい。";
		    }
		    if (!preg_match("/^[a-zA-Z0-9]+$/", $param['pass'])) {
		        $errorMessage[] = "パスワードは半角英数で入力して下さい。";
		    }
		}
	    // 名前の最大文字数チェック
	    if ((strlen($param['name']) > 32 || strlen($param['name']) < 2)) {
	        $errorMessage[] = "名前は1文字以上～16文字以内で入力して下さい。";
	    }
	    // 権限の設定チェック
	    if (strlen($param['authType']) == '') {
	        $errorMessage[] = "権限は必須です。";
	    }
		return $errorMessage;
	}

	/**
	 *
	 * 戻りURLの保存
	 *
	 * ログイン前にアクセスしようとしたURLを保持。
	 * 常に最新の1件しか保存しない。
	 *
	 */
	public function saveBackUrl() {
		$query = getRawHttpQuery();
		if (is_array($query)) {
			$query = '?' . http_build_query($query);
		}
		$_SESSION['backUrl'] = urldecode($query) ;
	}

	/**
	 *
	 * 保持した戻りURLの取得
	 *
	 */
	public function getBackUrl() {
		$backUrl = urldecode($_SESSION['backUrl']);
		$_SESSION['backUrl'] = null;
		return $backUrl;
	}
}























