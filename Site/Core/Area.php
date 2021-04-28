<?php
    
/**
 *
 * エリア
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

 
/**
 *
 * 
 * 
 */
class Area {

	/**
	 * 
	 * 都道府県取得
	 *
	 */
	public function getPref() {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
		              Pref.prefCode
					, Pref.regionCode
					, Pref.prefName
					, Pref.prefNameKatakana
					, Region.regionName 
		        FROM
		            Pref
		            JOIN Region ON Region.regionCode = Pref.regionCode
		        ORDER BY Region.sortNo, Pref.prefCode
		        ";
		    $sth = $dbh->prepare($sql);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data;
	}

	/**
	 *
	 * 都道府県取得
	 *
	 */
	public function getPrefWithSchoolCnt() {
		$dbh = Db::connect();
		try {
			$sql = "
		        SELECT
		              Pref.prefCode
					, Pref.regionCode
					, Pref.prefName
					, Pref.prefNameKatakana
					, Region.regionName 
		        FROM
		            Pref
		            JOIN Region ON Region.regionCode = Pref.regionCode
		            JOIN City ON City.prefCode = Pref.prefCode
		            JOIN School ON School.cityCode = City.cityCode
		        GROUP BY Pref.prefCode
		        ORDER BY Region.sortNo, Pref.prefCode
		        ";
			$sth = $dbh->prepare($sql);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data;
	}

	/**
	 *
	 * 地方区分・都道府県取得
	 *
	 */
	public function getRegionWithPref() {
		$dataRegion = array();
		$data = $this->getPref();
		foreach ($data as $key => $value) {
			if (!isset($dataRegion[$value['regionCode']]['regionCode'])) {
				$dataRegion[$value['regionCode']]['regionCode'] = $value['regionCode'];
				$dataRegion[$value['regionCode']]['regionName'] = $value['regionName'];
			}
			$dataRegion[$value['regionCode']]['pref'][] = $value;
		}
		return $dataRegion;
	}

	/**
	 *
	 * 地方区分・都道府県を施設数付きで取得
	 *
	 */
	public function getRegionWithSchoolCnt() {
		$dataRegion = array();
		$data = $this->getPrefWithSchoolCnt();
		foreach ($data as $key => $value) {
			if (!isset($dataRegion[$value['regionCode']]['regionCode'])) {
				$dataRegion[$value['regionCode']]['regionCode'] = $value['regionCode'];
				$dataRegion[$value['regionCode']]['regionName'] = $value['regionName'];
			}
			$dataRegion[$value['regionCode']]['pref'][] = $value;
		}
		return $dataRegion;
	}

	/**
	 * 
	 * 地方区分情報取得         
	 *
	 */
	public function getRegionByRegionCode($regionCode) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT     
					  regionCode
					, regionName
					, regionNameKatakana    
		        FROM
		            Region
		        WHERE regionCode = :regionCode
		        ";
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':regionCode', $regionCode, PDO::PARAM_STR);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		if ($data) return $data[0];
	}
	
	/**
	 * 
	 * 都道府県情報取得（都道府県コードから）         
	 *
	 */
	public function getPrefByPrefCode($prefCode) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  prefName
					, prefNameKatakana
					, regionCode
		        FROM
		            Pref
		        WHERE prefCode = :prefCode
		        ";					
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':prefCode', $prefCode, PDO::PARAM_INT);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		if ($data) return $data[0];
	}
	
	/**
	 * 
	 * 市区町村情報取得（都道府県から）     
	 *
	 */
	public function getCityListByPrefCode($prefCode) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT     
					  City.cityCode
					, City.cityName
					-- , City.cityNameKatakana
					, City.prefCode    
					, Pref.prefName   
		        FROM
		            City 
		            JOIN Pref ON Pref.prefCode = City.prefCode
		        WHERE City.prefCode = :prefCode
		        ";
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':prefCode', $prefCode, PDO::PARAM_INT);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data;
	}

	/**
	 *
	 * 市区町村情報・管轄役場情報取得（都道府県から）
	 *
	 */
	public function getCityListByPrefCodeWithGovCity($prefCode) {
		$dbh = Db::connect();
		try {
			$sql = "
		        SELECT     
					  City.cityCode
					, City.cityName
					-- , City.cityNameKatakana
					, City.prefCode    
					, Pref.prefName 
				 	, GovCity.name as govName
				 	, GovCity.urlTop  
				 	, GovCity.urlHoiku
				 	-- , GovCity.urlHoikuOld
		        FROM
		            City 
		            JOIN Pref ON Pref.prefCode = City.prefCode
		            JOIN GovCity ON GovCity.cityCode = City.cityCode
		        WHERE City.prefCode = :prefCode
		        ";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':prefCode', $prefCode, PDO::PARAM_INT);
			$sth->execute();
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
	 */
	private function format(&$data) {
		foreach ($data as $key => $value) {
			// GovCity.urlHoiku
			if (isset($value['urlHoiku'])) {
				// 配列に変換
				$data[$key]['urlHoiku'] = (array)json_decode($value['urlHoiku']);
			}
			// GovCity.urlHoikuOld
			if (isset($value['urlHoikuOld'])) {
				// 配列に変換
				$data[$key]['urlHoikuOld'] = (array)json_decode($value['urlHoikuOld']);
			}
		}
	}

	/**
	 * 
	 * 市区町村情報取得（市区町村コードから）         
	 *
	 */
	public function getCityByCityCode($cityCode) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  City.cityCode
					, City.cityName
					, City.cityNameKatakana
					, City.prefCode
					, City.latitude
					, City.longitude
				    , Pref.prefName
				    , Pref.prefNameKatakana
		        FROM
		            City JOIN Pref on Pref.prefCode = City.prefCode
		        WHERE cityCode = :cityCode
		        ";		
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':cityCode', $cityCode, PDO::PARAM_INT);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		if ($data) return $data[0];
	}

	/**
	 * 
	 * 市区町村リスト情報取得（市区町村コードリストから）         
	 *
	 */
	public function getCityListByCityCodeList($cityCodeList) {	
		$phList = implode(",", $cityCodeList);
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  City.cityCode
					, City.cityName
					, City.cityNameKatakana
					, City.prefCode 
				    , Pref.prefName
				    , Pref.prefNameKatakana
		        FROM
		            City JOIN Pref on Pref.prefCode = City.prefCode
		        WHERE cityCode in ($phList)
		        ";		
		    $sth = $dbh->prepare($sql);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data;
	}
	
	/**
	 * 
	 * 市区町村コード取得（都道府県名と市区町村名から）         
	 *
	 */
	public function getCityByPrefAndCityName($prefName, $cityName) {

		// yubinbango.js の表記ゆれ対策
		// 「ケ → ヶ」に変換（鎌ケ谷市など）
		// TODO：他にもあるかも。
		$cityName = preg_replace('/ケ/u', 'ヶ', $cityName);

		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  City.cityCode
					, City.cityName
					, City.cityNameKatakana
					, City.prefCode 
				    , Pref.prefName
				    , Pref.prefNameKatakana
		        FROM
		            City JOIN Pref on Pref.prefCode = City.prefCode
		        WHERE 
		        	prefName = :prefName 
		        	and cityName = :cityName
		        ";		
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':prefName', $prefName, PDO::PARAM_STR);
		    $sth->bindValue(':cityName', $cityName, PDO::PARAM_STR);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		if (isset($data[0])) {
			if (count($data) > 1) {
				showError($data, '市区町村コードが2件以上マッチします。');				
			} else {
				return $data[0];	
			}
		}
	}
	
	/**
	 * 
	 * 市区町村情報取得（市区町村名から）         
	 * 
	 */
	public function getCityByCityName($keyword) {
		$dbh = Db::connect();
		try {
		    $sql = "
		        SELECT
					  City.cityCode
					, City.cityName
					, City.prefCode
					, City.latitude
					, City.longitude
				    , Pref.prefName
				    -- , concat(Pref.prefName, City.cityName) as prefCityName
		        FROM
		            City JOIN Pref on Pref.prefCode = City.prefCode
		        WHERE 
		        	-- City.cityName like :keyword
					concat(Pref.prefName, City.cityName) like :keyword		
		        ";	
		    $sth = $dbh->prepare($sql);
		    $sth->bindValue(':keyword', "%{$keyword}%", PDO::PARAM_STR);
		    $sth->execute();
		    $data = $sth->fetchAll(PDO::FETCH_ASSOC);	    
		} catch (PDOException $e) {
		    showError($e);
		    throw $e;
		}
		return $data;
	}

	/**
	 * 
	 * 市区町村データの緯度・経度設定    
	 * 
	 */
	public function setLatLng(&$target) {
		// 住所から緯度・経度取得
		if ($gps = getGpsFromAddress($target['address'])) {
			
			// 緯度・経度セット
			$target['latitude'] = $gps['lat'];
			$target['longitude'] = $gps['lng'];	
			
			// DB更新
		    $paramUpdate = array(
		           'latitude'    => array('value' => $gps['lat'], 'type'  => PDO::PARAM_INT)
		         , 'longitude'   => array('value' => $gps['lng'], 'type' => PDO::PARAM_INT)
		    );
		    $paramWhere = array(
		          'cityCode'     => array('value' => $target['cityCode'],  'type' => PDO::PARAM_INT)
		    );
		   if (!Db::pdoUpdate('City', $paramUpdate, $paramWhere)) {
		    	showError($target, '緯度・経度更新失敗');		
		    }		 			
		} else {
			errorLog($target, '緯度・経度取得失敗');
			// Google Map でも取得エラーなら暫定対処として東京駅の位置情報をセット
			$target['latitude'] = 139.766103;
			$target['longitude'] = 35.681391;		
		}
	}

	/**
	 *
	 * キー：prefName のリスト取得
	 *
	 * 「都道府県名 → 都道府県ID」への変換テーブル取得
	 *  管理画面でのCSV登録に利用する。
	 *
	 */
	public function getKeyPrefNameList() {
		$data = $this->getPref();
		$keyNameList = Db::changeKey($data, 'prefName');
		return $keyNameList;
	}
}






























