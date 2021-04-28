<?php

/**
 *
 * ニュース
 *
 * 全ユーザー向けの共通のお知らせ。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class News {

	/**
	 *
	 * 取得
	 *
	 */
	public function get($bind, $phAnd, $limit = null, $phOrderBy = null) {
		$phLimit = null;
		if ($limit) {
			$phLimit = " LIMIT {$limit} ";
		}
		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
                      News.id
                    , News.insTime
                    , News.updTime
                    , News.openTime
                    , News.closeTime
                    , News.checkTime
                    , News.type
                    , News.title
                    , News.detail
                    , News.url
                FROM
                    News
                WHERE
                    News.id is not null
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
		if ($data) {
			$this->format($data);
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
		// id
		$phAnd .= " and News.id = :id ";
		$bind['id']['value'] = $id;
		$bind['id']['type'] = PDO::PARAM_INT;
		$data = $this->get($bind, $phAnd);
		if ($data) {
			return $data[0];
		}
	}

	/**
	 *
	 * データ数取得
	 *
	 */
	public function getCnt($bind, $phAnd){
		$dbh = Db::connect();
		try {
			$sql = "
	            SELECT
	            	count(News.id) as cnt
	            FROM
	               News
	            WHERE
					News.id is not null
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
	 * データ取得（ページ単位）
	 *
	 */
	public function getPage($bind, $phAnd, $page, $sort){

		// ORDER句取得
		$phOrderBy = $this->getPhOrderBy($sort);

		$dbh = Db::connect();
		try {
			$sql = "
                SELECT
                      News.id
                    , News.insTime
                    , News.updTime
                    , News.openTime
                    , News.closeTime                    
                    , News.checkTime
                    , News.type
                    , News.title
                    , News.detail
                    , News.url
                FROM
                    News
	            WHERE
	            	News.id is not null
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
	 * ソート用のORDER句取得
	 *
	 * $sort['sortCol']		：$sortColList のキーを指定
	 * $sort['sortOrder']	：asc／desc
	 *
	 */
	private function getPhOrderBy(&$sort) {
		if (!$sort) return;

		// News のソート指定カラムリスト
		$sortColList = array(
			'id' 		    => 'News.id',
			'insTime'		=> 'News.insTime',
			'openTime'		=> 'News.openTime',
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
		$phOrderBy = " ORDER BY $sortCol {$sort['sortOrder']}, News.id ";

		return $phOrderBy;
	}

	/**
	 *
	 * 全体から新着順に固定件数取得
	 *
	 */
	public function getNew($limit) {
		$phAnd = $bind = null;
		// openTime, closeTime 公開中のみ
		$phAnd .= " and News.openTime < now() and ( closeTime is null || closeTime > now() ) ";
		$phOrderBy = " ORDER BY News.openTime desc ";
		return $this->get($bind, $phAnd, $limit, $phOrderBy);
	}

	/**
	*
	* 配信ステータス文言取得
	*
	*/
	public function getNewsStatus($openTime, $closeTime) {
		return getOpenStatus($openTime, $closeTime);
	}

    /**
     *
	 * データ整形
	 *
     */
    private function format(&$data) {
		foreach($data as $key => $value) {
			// 配信状態追加
			$status = $this->getNewsStatus($value['openTime'], $value['closeTime']);
			$data[$key]['status'] = $status;
			// 詳細のリンク追加
			//     特殊記号「##～##」があればリンクに置換
			// $data[$key]['detail'] = preg_replace("/##(.+?)##/s", " <a href=\"$1\">$1</a> ", $value['detail']);
		}
    }

	/**
	 *
	 * バリデーション
	 *
	 */
	public function validate($input) {

		// タイトル
		$validation = new Validation();
		if (!$validation->checkStringCnt($input['title'], 5, 50)) {
			return 'タイトルは5文字以上、50文字以内で入力してください。';
		}

		// 詳細
		if (!$validation->checkStringCnt($input['detail'], 10, 500000)) {
			return '詳細は10文字以上で入力してください。';
		}

		// 配信開始日時
		if (!$validation->checkDateTime($input['openTime'])) {
			return '配信開始日時は「YYYY/MM/DD hh:mm」で入力して下さい。';
		}

		return null;
	}

	/**
	 *
	 * ニュース登録・更新
	 *
	 */
	public function upsert(&$newsId, $param) {
		$paramUpsert = array(
			'id'       		=> array('value' => $newsId     			, 'type' => PDO::PARAM_INT),
			'insTime'     	=> array('value' => date('Y-m-d H:i:s') 	, 'type' => 'datetime'),
			'updTime'     	=> array('value' => date('Y-m-d H:i:s') 	, 'type' => 'datetime'),
			'openTime'    	=> array('value' => $param['openTime']  	, 'type' => 'datetime'),
			'closeTime'   	=> array('value' => $param['closeTime']   	, 'type' => 'datetime'),
			'title'       	=> array('value' => $param['title']     	, 'type' => PDO::PARAM_STR),
			'detail'      	=> array('value' => $_POST['detail']     	, 'type' => PDO::PARAM_STR),	// HTMLタグなので $_POST
		);

		$dbh = Db::connect();
		if (!Db::pdoUpsert('News', $paramUpsert)) {
			showError($paramInsert, 'News 登録・更新失敗');
			return false;
		}

		if (!$newsId) {
			// 新規登録の場合
			$newsId = $dbh->lastInsertId();
		}

		return true;
	}

	/**
	 *
	 * 公開中のニュース取得
	 *
	 */
	public function getOpened($from = null, $to = null, $limit = null) {
		$phAnd = $bind = null;
		// 公開中のみ
		$phAnd .= " AND News.openTime < now() AND (News.closeTime IS NULL || News.closeTime > now()) ";
		if ($from) {
			// 指定日時以降に公開されたコラム
			$phAnd .= " AND News.openTime > '{$from}' ";
		}
		if ($to) {
			// 指定日時以前に公開されたコラム
			$phAnd .= " AND News.openTime < '{$to}' ";
		}
		return $this->get($bind, $phAnd, $limit);
	}

	/**
	 *
	 * ニュース情報のサイトマップ作成用 RDF 取得
	 *
	 */
	public function getRdf($sitemapPath) {
		// 前回の更新日時取得
		$timeStamp = date('Y-m-d H:i:s', filemtime($sitemapPath));
		// 前回から更新されたコラムを取得
		$data = $this->getOpened($timeStamp, null, 1);
		if ($data) {
			// 追加・更新ありの場合
			$rdf = array();
			// 公開中の全コラム取得
			$data = $this->getOpened();
			foreach ($data as $key => $value) {
				$url = SITE_URL . "?p=News/Detail&id={$value['id']}";
				// コラム更新日時（YYYY-MM-DDThh:mmTZD 形式）
				$openTime = strtotime($value['openTime']);
				$updTime = strtotime($value['updTime']);
				if ($value['openTime'] && $value['updTime']) {
					$lastmodTime = ($openTime > $updTime ? $openTime : $updTime);
				} elseif ($value['openTime']) {
					$lastmodTime = $openTime;
				} elseif ($value['updTime']) {
					$lastmodTime = $updTime;
				}
				$lastmodTime = date('c', $lastmodTime);
				$rdf['url'][] = array(
					"loc" 				=> $url,
					"lastmod" 			=> $lastmodTime,
				);
			}
			return $rdf;
		}
	}
}


