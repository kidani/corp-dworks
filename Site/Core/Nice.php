<?php

/**
 *
 * お気に入り
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Nice {

	/**
	 * 
	 * お気に入り取得
	 * 
	 */
	private function get($bind, $phAnd) {
	    $dbh = Db::connect();
	    try {
	        $sql = "
	            SELECT
					  id
					, insTime
					, userId
					, schoolId
	            FROM
	                Nice
	            WHERE
	                id is not null
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
		return $data;
	}

    /**
     * 
	 * お気に入り取得
	 * 
	 * id から取得
	 * 
     */
    public function getById($id, $userId = null) {
    	$phAnd = $bind = null;
		
        // userId
        $phAnd .= " and userId = :userId ";
       $bind['userId']['value'] = $userId;
       $bind['userId']['type'] = PDO::PARAM_STR;
		
        // id
        $phAnd .= " and id = :id ";
		$bind['id']['value'] = $id;
		$bind['id']['type'] = PDO::PARAM_INT;
	
		$data = $this->get($bind, $phAnd);
		if ($data) return $data[0];	
    }

    /**
     * 
	 * お気に入り取得
	 * 
	 * userId から取得
	 * 
     */
    public function getByUserId($userId) {
    	$phAnd = $bind = null;
		
        // userId
        $phAnd .= " and userId = :userId ";
       $bind['userId']['value'] = $userId;
       $bind['userId']['type'] = PDO::PARAM_STR;
	   
	   	// 新しい順に取得
		$phAnd .= " order by insTime desc ";

		return $this->get($bind, $phAnd);
    }
	
    /**
     * 
	 * 施設がお気に入り登録された数を取得
	 * 
	 * schoolId から取得
	 * 
     */
    public function getCntBySchoolId($schoolId, $userId = null) {
    	$phAnd = $bind = null;
		
		// schoolId
        $phAnd .= " and schoolId = :schoolId ";
       $bind['schoolId']['value'] = $schoolId;
       $bind['schoolId']['type'] = PDO::PARAM_INT;

       // userId
       if ($userId) {
	        $phAnd .= " and userId = :userId ";
	       $bind['userId']['value'] = $userId;
	       $bind['userId']['type'] = PDO::PARAM_STR;	
	   }
		return count($this->get($bind, $phAnd));
    }

	/**
	 *
	 * 登録
	 *
	 */
	public function add($schoolId, $userId) {
		$paramInsert = array(
			'insTime'   => array('value' => date('Y-m-d H:i:s')     , 'type' => 'datetime'),
			'schoolId'  => array('value' => $schoolId    			, 'type' => PDO::PARAM_INT),
			'userId'   	=> array('value' => $userId   				, 'type' => PDO::PARAM_STR),
		);
		return Db::pdoInsert('Nice', $paramInsert);
	}

	/**
	 *
	 * 削除
	 *
	 */
	public function delete($schoolId, $userId) {
		$paramWhere = array(
			'schoolId'  	=> array('value' => $schoolId    			, 'type' => PDO::PARAM_INT),
			'userId'   	=> array('value' => $userId   			, 'type' => PDO::PARAM_STR),
		);
		return Db::pdoDelete('Nice', $paramWhere);
	}

    /**
     * 
	 * 重複チェック
	 * 
	 * 同じユーザーが同じ schoolId を重複して登録していないかチェック。
	 * 「お気に入り追加」ボタン押下時に呼ばれるので、通常なら重複は存在しないはずだが
	 * 直URLでのリクエストなら通るので念のためチェックしておく。
	 * 
	 * $data  ：特定ユーザーの全お気に入りデータ
	 * 
	 * 
     */
    public function checkOverlap($data, $schoolId) {
		$findCnt = 0;
		foreach ($data as $key => $value) {
			if ($value['schoolId'] === $schoolId) {
				$findCnt++;	
			}
		}
		if ($findCnt > 0) {
			if ($findCnt > 1) {
				// 2つ以上なら相当な不正かバグなので念のため。
				showError(func_get_args(), '同じ schoolId に対していのお気に入りが2つ以上重複しています。');
			}
			return false;
		}	
		return true;
    }

	/**
	 *
	 * 既に登録済みの施設かチェック
	 *
	 */
	public function check($schoolId, $userId) {
		$phAnd = $bind = null;

		// Nice.schoolId
		$phAnd .= " and Nice.schoolId = :schoolId ";
		$bind['schoolId']['value'] = $schoolId;
		$bind['schoolId']['type'] = PDO::PARAM_INT;

		// Nice.userId
		$phAnd .= " and Nice.userId = :userId ";
		$bind['userId']['value'] = $userId;
		$bind['userId']['type'] = PDO::PARAM_STR;

		if ($this->get($bind, $phAnd)) {
			return true;
		} else {
			return false;
		}
	}

}








































