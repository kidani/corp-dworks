<?php

/**
 *
 * キャッシュ
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Cache {

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
                      Cache.id
                    , Cache.updTime
                    , Cache.news
                    , Cache.school
                    , Cache.question
                    , Cache.slider
                FROM
                    Cache
                WHERE
                    Cache.id is not null
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
	 * id から取得
     *
     */
    public function getById($id) {
        $phAnd = $bind = null;
		$updateFlg = false;

        // id
        $phAnd .= " and Cache.id = :id ";
        $bind['id']['value'] = $id;
        $bind['id']['type'] = PDO::PARAM_STR;

        // 既存データ取得
        $data = $this->get($bind, $phAnd);
        if ($data) {
			$data = $data[0];
		}

		if (!$data || checkElapsedTime($data['updTime'], 1) || WV_DEBUG) {
			// 1時間毎にデータ更新
			$paramDb = null;

			// news：ニュース新着
			$news = new News();
			$paramDb['news'] = $news->getNew(4);

			// school：口コミ新着
			// 全体から口コミ新着順に固定件数取得
			$school = new School();
			$paramDb['school'] = $school->getReviewNew(4);

			// question：QA新着
			$question = new Question();
			$paramDb['question'] = $question->getNew(4);

			// slider：スライダーに表示する施設
			// 画像ありの施設情報をランダム取得
			// TODO：とりあえずは手動で School.hasImage にフラグセットして取得している。
			$paramDb['slider'] = $school->getImageRand(20);

			// データ更新
			$this->upsert($id, $paramDb);

			// 再取得
			$data = $this->get($bind, $phAnd);
			$data = $data[0];
		};

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
		// JSON → 配列変換対象カラムリスト
		$itemJson = array(
			'news',
			'school',
			'question',
			'slider',
		);
		// 全て配列化（テンプレートが流用できるようにするため）
		// 階層が深い imgList は配列化できていないので注意！
		//（とりあえず imgMain 以外は使ってないので問題なし）
		foreach ($itemJson as $key => $value) {
			if ($data[$value]) {
				$data[$value] = json_decode($data[$value]);
				if ($data[$value]) {
					foreach ($data[$value] as $k => $val) {
						$data[$value][$k] = (array)$val;
					}
				}
			}
		}
	}

    /**
	 *
	 * 更新
	 * 
	 */
	public function upsert($id, $paramDb) {
		$paramUpsert = array(
			'id'        => array('value' => $id	    		    , 'type' => PDO::PARAM_STR),
			'updTime'   => array('value' => date('Y-m-d H:i:s')	, 'type' => 'datetime'),
		);
		if (isset($paramDb['news'])) {
			$paramDb['news'] = json_encode($paramDb['news']);
			$paramUpsert['news'] = array('value' => $paramDb['news'], 'type' => PDO::PARAM_STR);
		}
		if (isset($paramDb['school'])) {
			$paramDb['school'] = json_encode($paramDb['school']);
			$paramUpsert['school'] = array('value' => $paramDb['school'], 'type' => PDO::PARAM_STR);
		}
		if (isset($paramDb['question'])) {
			$paramDb['question'] = json_encode($paramDb['question']);
			$paramUpsert['question'] = array('value' => $paramDb['question'], 'type' => PDO::PARAM_STR);
		}
		if (isset($paramDb['slider'])) {
			$paramDb['slider'] = json_encode($paramDb['slider']);
			$paramUpsert['slider'] = array('value' => $paramDb['slider'], 'type' => PDO::PARAM_STR);
		}
		return Db::pdoUpsert('Cache', $paramUpsert);
	}
}





































