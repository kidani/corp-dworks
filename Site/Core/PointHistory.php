<?php

/**
 * 
 * ポイント履歴
 *
 * 売上金のポイント交換、ポイントでの購入、ポイント失効履歴。
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class PointHistory {
	
	// 有効期限までの日数
	//     取引終了日から半年（180日）
	public static $expireDayCnt = 180;
	// private $expireDayCnt = 10;	// テスト用
	
	// アラート表示開始までの日数
	//     取引終了日から5ヶ月（150日 有効期限切れの30日前）
	public static $alertDayCnt = 150;
	// private $alertDayCnt = 3;  	// テスト用

    /**
     * 
	 * バリデーション
	 * 
     */
    public function validate(&$input, $curSales) {
    	
		$errMessage = array();

		// ポイント交換する金額
		if (!$input['changeSales']) {
			return 'ポイント交換する金額を入力してください。';
		} else {
			
			// 現在の所持売上金
			$curSales = intval($curSales);
			// ポイント交換する金額
			$changeSales = intval($input['changeSales']);	
			
			// フォーマット
			//     スペース削除
			$input['changeSales'] = deleteSpace($input['changeSales']);
			// 文字種チェック
			//     半角数字のみ			
		    if (!preg_match("/^[0-9]+$/", $input['changeSales'])) {
				return 'ポイント交換する金額に半角数字以外は入力しないで下さい。';
		    }

			// 現在の売上金との整合チェック
		    if ($changeSales > $curSales) {
		    	// 現在の売上がポイント交換する金額より少ない場合
				// 通常ありえないのでログ記録
				errorLog($input, 'ポイント交換する金額が現在の売上金を超えています。');
				return 'ポイント交換する金額が現在の売上金を超えています。';
		    }
			
			// TODO：上限チェック
		    if ($changeSales > 1000000) {
				// TODO：上限設置するか検討するが、とりあえず100万でチェック。
				errorLog($input, 'ポイント交換する金額が100万円を超えています。');
		    }		
		}

		return null;
    }

    /**
     * 
	 * ポイント履歴取得
	 * 
     */
    public function get($bind, $phAnd, $phLimit = null, $phOrderBy = null) {
    	if (!$phOrderBy) {
			$phOrderBy = ' order by PointHistory.insTime desc ';
		}
        $dbh = Db::connect();
        try {
            $sql = "
                SELECT
					  PointHistory.id
					, PointHistory.insTime
					, PointHistory.userId
					, PointHistory.type
					, PointHistory.point
					, PointHistory.delAmount
					, PointHistory.delEndTime
					, PointHistory.delDetail
					, Profile.nickname
					, Users.mailAddress
					, Users.status as UserStatus
					, Users.restriction
                FROM
                    PointHistory
                    JOIN Users on Users.userId = PointHistory.userId
                    LEFT JOIN Profile on Profile.userId = PointHistory.userId
                WHERE
                    PointHistory.id is not null
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
	 * ポイント履歴数取得
	 * 
	 */
	public function getCnt($bind, $phAnd){	
	    $dbh = Db::connect();
	    try {
	        $sql = "
	            SELECT
	            	count(id) as cnt
	            FROM
                    PointHistory
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
		if ($data) return $data[0]['cnt'];
	}
	
	/**
	 * 
	 * ポイント履歴取得（ページ単位）
	 * 
	 */
	public function getPage($bind, $phAnd, $page, $sort){
		
		// ORDER句取得						
		$phOrderBy = $this->getPhOrderBy($sort);
		
	    $dbh = Db::connect();
	    try {
	        $sql = "
                SELECT
					  id
					, insTime
					, userId
					, type
					, point
					, delAmount
					, delEndTime
					, delDetail
                FROM
                    PointHistory
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
     * 整形
	 * 
	 * 以下の情報を付加。
	 * 
	 * 有効期限
	 * ポイントの消費状況
	 * 失効関連情報
	 * 
     */
    private function format(&$data) {
    	
		// 履歴の有効ポイント合計
		$pointRemainTotal = 0;					
		
		// 有効期限までの日数
		$expireDayCnt = self::$expireDayCnt;
		// アラート表示開始までの日数
		$alertDayCnt = self::$alertDayCnt;
		
		foreach ($data as $key => $value) {	
			if (!strstr($value['type'], '減算')) {
				// ポイント加算履歴の場合
				
				// 未消費ポイント
				$remainPt = intval($value['point']) - intval($value['delAmount']);

				// 有効期限
				$data[$key]['expireTime'] = date("Y-m-d", strtotime("{$value['insTime']} +{$expireDayCnt} day"));
				// アラート表示開始までの日数		
				$alertTime = date("Y-m-d", strtotime("{$value['insTime']} +{$alertDayCnt} day"));
				// 今日の0時
				$today = date("Y-m-d");

				if ($remainPt === 0) {
					// 消費済みの場合
					$data[$key]['remainAmount'] = 0;
					$data[$key]['expireAlert'] = 'ポイント消費済み';
				} else {
					// 未消費ポイントありの場合
					$data[$key]['remainAmount'] = $remainPt;
					// ここまでの加算値
					$pointRemainTotal += $remainPt;
					
					if (strtotime($today) > strtotime($data[$key]['expireTime'])) {
						$data[$key]['expireAlert'] = 'ポイント失効済み';
						
					} elseif (strtotime($today) > strtotime($alertTime)) {
						$remainDay = getDayDiff($today, $data[$key]['expireTime']);
						$data[$key]['expireAlert'] = "あと{$remainDay}日でポイントが失効します！";
					}
				} 
			} else {
				// ポイント減算履歴の場合
				$data[$key]['remainAmount'] = null;
			}	
			$data[$key]['remainAmountAccum'] = $pointRemainTotal;
		}
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

		// Columns のソート指定カラムリスト
		$sortColList = array( 
			  'insTime'		=> 'PointHistory.insTime'
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
		$phOrderBy = " ORDER BY $sortCol {$sort['sortOrder']}, id ";

		return $phOrderBy;
    }

    /**
     * 
	 * ポイント履歴取得
	 * 
	 * userId から取得
	 * 
     */
    public function getByUserId($userId, $limit = null) {
    	$phAnd = $bind = null;
		
        // userId
        $phAnd .= " and PointHistory.userId = :userId ";
       $bind['userId']['value'] = $userId;
       $bind['userId']['type'] = PDO::PARAM_STR;

	  	// limit
	  	$phLimit = null;
	  	if ($limit) {
			$phLimit .= " LIMIT {$limit} ";	
		}
		
		$data = $this->get($bind, $phAnd, $phLimit);
		$this->format($data);
		
		return $data;
    }
	
    /**
     * 
	 * 消込対象ポイント履歴取得
	 * 
	 * 購入によりポイントから消込みする履歴を取得
	 * 
     */
    public function getDelHistory($userId, $amount) {

       // userId
        $phAnd = " and PointHistory.userId = :userId ";
       $bind['userId']['value'] = $userId;
       $bind['userId']['type'] = PDO::PARAM_STR;
		
        // delEndTime
        $phAnd .= " and delEndTime is null ";

		// 消込み対象履歴取得
		$phOrderBy = ' order by insTime asc ';        // 古い履歴順に注意！
		$data = $this->get($bind, $phAnd, null, $phOrderBy);
		if (!$data) {
			// 通常ありえないのでエラー
			showError($data, '消込み対象履歴取得不可');
			return false;
		}
	
		// 消込み対象履歴取得
		$amount = intval($amount);			// 削除するポイント
		$deletedTotal = 0;					// 削除済ポイント
		$deleteRemain = $amount;			// 残りの削除ポイント		
		$delHistory = array();
		foreach ($data as $key => $value) {
			// このターンで消込み可能な最大値
			$delMax = $value['point'] - $value['delAmount'];

			if ($deleteRemain < $delMax) {
				// このターンで消込み切れる場合
				
				// この履歴に対しての消込分
				$value['deleted'] = $deleteRemain;
				
				// delAmount の更新値 
				$value['delAmount'] = $value['delAmount'] + $deleteRemain;

				$delHistory[] = $value;
				
				$deletedTotal += $deleteRemain;     			
				$deleteRemain -= $deleteRemain;	 
				break;
			} else {
				// まだ消込み切れない場合
				
				// この履歴に対しての消込分
				$value['deleted'] = $value['point'] - $value['delAmount'];

				// delAmount の更新値
				$value['delAmount'] = $value['point'];   // 全て消込み
				$delHistory[] = $value;
				
				$deletedTotal += $delMax;     			
				$deleteRemain -= $delMax;
			}
		}

		if ($deletedTotal < $amount || !$delHistory) {
			// 通常ありえないのでエラー
			showError($data, '消込み対象履歴取得不可');
			return false;
		}
		
		return $delHistory;
    }
	
	/**
	 * 
	 * ポイントの消込み
	 * 
	 * 購入による消込み、または有効期限切れによる失効。
	 * delAmount, delEndTime を更新する。
	 * 
	 */
	public function updateDelInfo($data) {
	    $paramWhere = array(
			'id' 	=> array('value' => $data['id'] 	, 'type' => PDO::PARAM_INT)
	    );
		$paramUpdate = array(
			'updTime'        		=> array('value' => date('Y-m-d H:i:s')			, 'type' => 'datetime')
		);	
		if (isset($data['delAmount'])) {
			// 消込みの場合（振込申請、ポイント交換）

			// 消込み済み金額を設定
			$paramUpdate['delAmount'] = array('value' => $data['delAmount']	, 'type' => PDO::PARAM_STR);	
			
			// 全額消込対象の場合、消込時間を追加
			if (intval($data['delAmount']) === intval($data['point'])) {
				$paramUpdate['delEndTime'] = array('value' => date('Y-m-d H:i:s')	, 'type' => 'datetime');	
			}
		} else {
			// 失効の場合
			$paramUpdate['delEndTime'] = array('value' => date('Y-m-d H:i:s')	, 'type' => 'datetime');
		}
		return Db::pdoUpdate('PointHistory', $paramUpdate, $paramWhere);
	}
	
    /**
     * 
	 * ポイント履歴登録
	 * 
     */
    public function add($userId, $type, $point, $delDetail = null) {
		// ポイント履歴登録
	    $paramInsert = array(
		      'insTime'             => array('value' => date('Y-m-d H:i:s')   	, 'type' => 'datetime')
		    , 'userId'              => array('value' => $userId          		, 'type' => PDO::PARAM_STR)
		    , 'type'                => array('value' => $type           		, 'type' => PDO::PARAM_STR)
		    , 'point'         		=> array('value' => $point  				, 'type' => PDO::PARAM_INT)
	    );
		// ポイント減算時の消込対象履歴
		//     どの履歴のポイントを消込みして減算したかを記録。
		if ($delDetail) {
			$paramInsert['delDetail'] = array('value' => json_encode($delDetail), 'type' => PDO::PARAM_STR);
		}
		return Db::pdoInsert('PointHistory', $paramInsert);
    }
}






























