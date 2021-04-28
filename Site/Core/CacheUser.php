<?php

/**
 *
 * キャッシュ
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class CacheUser {

    /**
     *
     * 取得
     *
     */
    public function get($bind, $phAnd) {
        $dbh = Db::connect();
        try {
            $sql = "
                SELECT
                      userId
                    , updTime
                    , recent
                FROM
                    CacheUser
                WHERE
                    CacheUser.userId is not null
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
     * キャッシュデータ取得
	 * 
	 * userId から取得
     *
     */
    public function getByUserId($userId) {
        $phAnd = $bind = null;
		$updateFlg = false;

        // userId
        $phAnd .= " and userId = :userId ";
        $bind['userId']['value'] = $userId;
        $bind['userId']['type'] = PDO::PARAM_STR;

        // 既存データ取得
        $data = $this->get($bind, $phAnd);
        if ($data) {
			$data = $data[0];
		}

		// recent
		// 更新は施設詳細アクセス時

		$this->format($data);
		return $data;
	}

	/**
	 *
	 * 整形
	 *
	 */
	public function format(&$data) {
		if (!$data) return;
		// 全て配列化（テンプレートが流用できるようにするため）
		if ($data['recent']) {
			$data['recent'] = json_decode($data['recent']);
			foreach ($data['recent'] as $key => $value) {
				$data['recent'][$key] = (array)$value;
			}
		}
	}

	/**
	 *
	 * 更新
	 *
	 * 直近でチェックしたものを最初に追加する。
	 *
	 */
	public function add($userId, $colName, $data) {

		if ($colName === 'recent') {
			// 1件ずつ追加（$data は配列ではない！）
			$maxCnt = 8;
			$addData = null;
			$addData['id'] = $data['id'];
			$addData['name'] = $data['name'];
			$addData['authType'] = $data['authType'];
			$addData['opeType'] = $data['opeType'];
			$addData['address'] = $data['address'];
			$addData['traffic'] = $data['traffic'];
			$addData['recomLevelAvg'] = $data['recomLevelAvg'];
			$addData['reviewCnt'] = $data['reviewCnt'];
			$addData['reviewDetail'] = $data['reviewDetail'];
			$addData['reviewTime'] = $data['reviewTime'];
			$addData['updTime'] = $data['updTime'];
			$addData['imgMain'] = $data['imgMain'];

			// 既存データ取得
			$dataCache = $this->getByUserId($userId);
			if (isset($dataCache['recent']) && $dataCache['recent']) {
				// 更新の場合
				// 最初のデータを入れ替える。
				$arrOld = $dataCache['recent'];
				$arrNew = array();
				foreach ($arrOld as $key => $value) {
					if ($key === 0) {
						if ($value['id'] === $addData['id']) {
							// 最初が同じ施設の場合はスルー
							return true;
						} else {
							// 最初に追加
							$arrNew[] = $addData;
							$arrNew[] = $value;
						}
					} else {
						if ($value['id'] !== $addData['id']) {
							// 違う施設の場合は追加
							$arrNew[] = $value;
						}
					}
				}
				// 最大 $maxCnt 件まで保持なので余分はカット
				if (count($arrNew) > $maxCnt) {
					array_splice($arrNew, $maxCnt);	// $maxCnt+1 番目以降を削除
				}
			} else {
				// 新規追加の場合
				$arrNew = array($addData);
			}
		}
		$this->upsert($userId, array($colName => json_encode($arrNew)));
		return true;
	}

    /**
	 *
	 * 更新
	 * 
	 */
	public function upsert($userId, $paramDb) {
		$paramUpsert = array(
			'userId'    => array('value' => $userId	    		, 'type' => PDO::PARAM_STR),
			'updTime'   => array('value' => date('Y-m-d H:i:s')	, 'type' => 'datetime'),
		);
		if (isset($paramDb['recent'])) {
			$paramUpsert['recent'] = array('value' => $paramDb['recent'], 'type' => PDO::PARAM_STR);
		}
		return Db::pdoUpsert('CacheUser', $paramUpsert);
	}
}





































