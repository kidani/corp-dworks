<?php

/**
 *
 * ユーザー
 * 
 * 未使用（Site/Core/User.php を使ってる。）
 * 
 */
class UserAdmin {
	
//	/**
//	 * 有効会員数取得
//	 */
//	public function getValidUserCount() {
//		// アプリ登録状態のユーザーID取得
//		$dbh = Db::connect();
//		try {
//		    $sql = "
//		    	SELECT
//		              count(userId) as count
//		        FROM Users
//		        WHERE
//		            statusLife = 'add' or statusLife = 'resume'
//		        ";
//		    $sth = $dbh->prepare($sql);
//		    $result = $sth->execute();
//		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
//		} catch (PDOException $e) {
//		    showError($e);
//		    throw $e;
//		}
//		return $data[0][count];
//	}
//
//	/**
//	 * 有効会員取得
//	 */
//	public function getValidUserList() {
//		// アプリ登録状態のユーザーID取得
//		$dbh = Db::connect();
//		try {
//		    $sql = "
//		    	SELECT
//		              userId
//		        FROM Users
//		        WHERE
//		            statusLife = 'add' or statusLife = 'resume'
//		        ";
//		    $sth = $dbh->prepare($sql);
//		    $result = $sth->execute();
//		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
//		} catch (PDOException $e) {
//		    showError($e);
//		    throw $e;
//		}
//		return $data;
//	}
//
//	/**
//	 * DAU数取得
//	 *     ログインチケット付与履歴からDAU数を特定する。
//	 */
//	public function getDauCount($targetDay) {
//		$dbh = Db::connect();
//		try {
//		    $sql = "
//				select
//					count(userId) as count
//				from UserGameStatus
//				where DATE_FORMAT(lastDailyTicketTime, '%Y-%m-%d') = '$targetDay'
//		        ";
//		    $sth = $dbh->prepare($sql);
//		    $result = $sth->execute();
//		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
//		} catch (PDOException $e) {
//		    showError($e);
//		    throw $e;
//		}
//		return $data[0][count];
//	}
	
}

