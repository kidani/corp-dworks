<?php

/**
 * 
 * 住所
 *
 * 複数登録許可の前提。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Address {

    /**
     * 
	 * 住所登録バリデーション
	 * 
     */
    public function validate(&$input, $userId) {
    	
		$errMessage = array();

		// 姓
		//     全角20文字以内
		$validation = new Validation();
		if (!$validation->checkStringCnt($input['sei'], 1, 20)) {
			return '姓は1文字以上～20文字以内で入力してください。';
		}

		// 名
		//     全角20文字以内
		if (!$validation->checkStringCnt($input['mei'], 1, 20)) {
			return '名は1文字以上～20文字以内で入力してください。';
		}
		
		// カナ（姓）
		//     全角20文字以内
		if (!$validation->checkStringCnt($input['seiKana'], 1, 20)) {
			return 'カナ（姓）は1文字以上～20文字以内で入力してください。';
		}

		// カナ（名）
		//     全角20文字以内
		if (!$validation->checkStringCnt($input['meiKana'], 1, 20)) {
			return 'カナ（名）は1文字以上～20文字以内で入力してください。';
		}

		// 郵便番号
		//     半角7文字固定
		if (!$input['zip']) {
			return '郵便番号を入力してください。';
		} elseif ($result = $validation->checkZip($input['zip'])) {
			return $result;
		}

		// 都道府県
		if (!$input['prefName']) {
			return '都道府県が取得できません。';
		}

		// 市区町村
		if (!$input['prefName']) {
			return '市区町村が取得できません。';
		}

		// 市区町村コード取得
		$area = new Area();
		$city = $area->getCityByPrefAndCityName($input['prefName'], $input['cityName']);
		if (!$city) {
			// TODO：prefName、cityName が空で遷移してくる場合が頻発するようなので、
			// JS で submit 時点で弾くようガード追加すること。
			// return '都道府県～市区町村が取得できません。';
			// 「ケ → ヶ」の変換のように、yubinbango.js 特有のの表記ゆれがあれば追加していくこと。
			errorLog($input, '市区町村コード取得不可');
			return '正しい郵便番号を入力して下さい。';
		}
		$input['cityCode'] = $city['cityCode'];

		// 町域以降の住所（字、番地・号、建物名）
		//     最短：全角2文字（千葉県旭市ロ1）＋建物名
		//     最長：全角60文字（京都府京都市東山区三条通南二筋目白川筋西入ル二丁目北木之元町）＋建物名
		if (!$input['street']) {
			return '市区町村に続く住所を入力してください。';
		} elseif (!$validation->checkStringCnt($input['street'], 2, 60)) {
			return '市区町村に続く住所は2文字以上～60文字以内で入力してください。';
		}

		// 重複チェック
		if ($this->getSame($input, $userId)) {
			// 全て同じ内容での登録はエラー
			return '既に同じ住所・名前で登録済みです。';
		}
		return null;
    }

    /**
     * 
	 * 住所取得
	 * 
     */
    private function get($bind, $phAnd) {	
        $dbh = Db::connect();
        try {
            $sql = "
                SELECT
					  Address.id
					, Address.userId
					, Address.sei
					, Address.mei
					, Address.seiKana
					, Address.meiKana
					, Address.zip
					, Address.cityCode
					, Address.street
					, City.cityName
					, Pref.prefName
					, Pref.prefCode
                FROM
                    Address JOIN City 
                    ON City.cityCode = Address.cityCode JOIN Pref 
                    ON Pref.prefCode = City.prefCode
                WHERE
                    Address.id is not null
                    $phAnd 
                ";		
            $sth = $dbh->prepare($sql);
            Db::bindValueList($sth, $bind);
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
	 * 住所取得
	 * 
	 * id から取得
	 * 
     */
    public function getById($id, $userId) {
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
	 * 住所取得
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

       	return $this->get($bind, $phAnd);
    }

	/**
	 *
	 * 整形
	 *
	 */
	private function format(&$data) {
		foreach ($data as $key => &$value) {
			$value['addressFull'] = "{$value['prefName']}{$value['cityName']}{$value['street']}";
		}
	}

    /**
     * 
	 * 住所の重複チェック
	 * 
     */
    public function getSame($input, $userId) {
    	$phAnd = $bind = null;

		// userId
		$phAnd .= " and Address.userId = :userId ";
		$bind['userId']['value'] = $userId;
		$bind['userId']['type'] = PDO::PARAM_STR;

		// sei
		$phAnd .= " and Address.sei = :sei ";
		$bind['sei']['value'] = $input['sei'];
		$bind['sei']['type'] = PDO::PARAM_STR;

		// mei
		$phAnd .= " and Address.mei = :mei ";
		$bind['mei']['value'] = $input['mei'];
		$bind['mei']['type'] = PDO::PARAM_STR;

		// seiKana
		$phAnd .= " and Address.seiKana = :seiKana ";
		$bind['seiKana']['value'] = $input['seiKana'];
		$bind['seiKana']['type'] = PDO::PARAM_STR;

		// meiKana
		$phAnd .= " and Address.meiKana = :meiKana ";
		$bind['meiKana']['value'] = $input['meiKana'];
		$bind['meiKana']['type'] = PDO::PARAM_STR;

		// zip
		$phAnd .= " and Address.zip = :zip ";
		$bind['zip']['value'] = $input['zip'];
		$bind['zip']['type'] = PDO::PARAM_STR;

		// cityCode
		$phAnd .= " and Address.cityCode = :cityCode ";
		$bind['cityCode']['value'] = $input['cityCode'];
		$bind['cityCode']['type'] = PDO::PARAM_INT;

		// street
		$phAnd .= " and Address.street = :street ";
		$bind['street']['value'] = $input['street'];
		$bind['street']['type'] = PDO::PARAM_STR;
			
		return $this->get($bind, $phAnd);
    }

    /**
     * 
	 * 住所削除
	 * 
     */
    public function deleteById($id, $userId) {
	    $paramWhere = array(
	    	  'id'         => array('value' => $id				, 'type' => PDO::PARAM_INT)
	        , 'userId'     => array('value' => $userId			, 'type' => PDO::PARAM_STR)
	    );
		return Db::pdoDelete('Address', $paramWhere);
    }

}






























