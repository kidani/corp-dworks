<?php

/**
 *
 * ユーザー
 * 
 * 未使用（Site/Core/User.php を使ってる。）
 * 
 */
class User {
	
	/**
	 * 有効会員数取得
	 */	 
	public function getValidUserCount() {
		// アプリ登録状態のユーザーID取得
		$dbh = Db::connect();
		try {
		    $sql = "
		    	SELECT
		              count(userId) as count
		        FROM Users
		        WHERE
		            statusLife = 'add' or statusLife = 'resume'
		        ";
		    $sth = $dbh->prepare($sql);
		    $result = $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data[0][count];
	}	
	
	/**
	 * 有効会員取得
	 */	 
	public function getValidUserList() {
		// アプリ登録状態のユーザーID取得
		$dbh = Db::connect();
		try {
		    $sql = "
		    	SELECT
		              userId
		        FROM Users
		        WHERE
		            statusLife = 'add' or statusLife = 'resume'
		        ";
		    $sth = $dbh->prepare($sql);
		    $result = $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data;
	}
	
	/**
	 * DAU数取得
	 *     ログインチケット付与履歴からDAU数を特定する。
	 */	 
	public function getDauCount($targetDay) {
		$dbh = Db::connect();
		try {
		    $sql = "
				select 
					count(userId) as count
				from UserGameStatus
				where DATE_FORMAT(lastDailyTicketTime, '%Y-%m-%d') = '$targetDay'
		        ";
		    $sth = $dbh->prepare($sql);
		    $result = $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data[0][count];
	}	

	//------------------------------------------------
	// アイテム
	//------------------------------------------------
	/**
	 * 所持アイテム数登録・更新（差分のみ反映）
	 *
	 * 1 ストーリーチケット
	 * 2 アナザーストーリーチケット
	 * 3 ゴールドガチャチケット
	 * 4 好感度UPガチャチケット
	 * 5 リトライチケット
	 * 6 モザイク消しゴム
	 * 7 アルバム
	 * 8 ガチャpt
	 * 9 好感度pt
	 *
	 */	 
	public function userItemUpsert($itemId, $diffAmount, $charaId = 0, $paramPresent = null) {	
	    // UPDATE時の既存値の利用用の追加フレーズ作成
	    if ($diffAmount < 0) {
	        $amountOption = "IF(amount is null, :amount, amount - :amount)";
	        // 正数に変更
	        $diffAmount = -$diffAmount;
	    } else {
	        $amountOption = "IF(amount is null, :amount, amount + :amount)";
	    }
	    $paramUpsert = array(
	          'insTime'          => array('value' => date('Y-m-d H:i:s'),       'type' => 'datetime')
	        , 'updTime'          => array('value' => date('Y-m-d H:i:s'),       'type' => 'datetime')
	        , 'userId'           => array('value' => $this->userId,             'type' => PDO::PARAM_STR)
	        , 'itemId'           => array('value' => $itemId,                   'type' => PDO::PARAM_INT)
	        , 'amount'           => array('value' => $diffAmount,               'type' => PDO::PARAM_INT,       'option' => $amountOption)  // 差分のみ反映
	        , 'charaId'          => array('value' => $charaId,                  'type' => PDO::PARAM_INT)
	    );
		$result = Db::pdoUpsert('UserItem', $paramUpsert);
	    if (!$result) {
	        showError('エラー：アイテム付与失敗');
	        return $result;
	    }
	    
		// プレゼント履歴追加
		if ($paramPresent) {
			$paramPresent[itemId] = $itemId;
			$paramPresent[amount] = $diffAmount;					
			$result = $this->insertUserPresentHistory($paramPresent);
		    if (!$result) {
		        showError('エラー：プレゼント履歴追加失敗');
		        return $result;
		    }		
		}
		
	    // 新着情報追加 → 同時に追加するなら
		// $routeId = 'Present';
		// $detail = '';				
		// $this->addUserNotice($routeId, $detail, $inviteFromId);	

		return $result;
	}
	
}

