<?php

/**
 *
 * 施設インポート
 * 
 *     フォーマット：カンマ区切りCSV
 *     文字コード：Shit-JIS 
 * 
 */
class ItemImport {

	// 圧縮ファイルを配置する作業ディレクトリ
	private $workDir = "images/work";

	// 列毎の項目名と必須情報
	// value=0：NULL可／1：必須
	// 列毎の項目名と必須情報
	private $headerTitle = array(
		0    => array('name' => 'idWork'          , 'value' => 1),   // 仮ID
		1    => array('name' => 'insTime'         , 'value' => 1),   // DB登録日時
		2    => array('name' => 'openTime'        , 'value' => 0),   // 事業開始年月日
		3    => array('name' => 'cityCode'        , 'value' => 1),   // 市区町村コード
		4    => array('name' => 'authType'        , 'value' => 1),   // 施設種別
		5    => array('name' => 'authTypeSub'     , 'value' => 0),   // 施設種別サブ
		6    => array('name' => 'opeType'         , 'value' => 0),   // 運営機関種別（公立／私立）
		7    => array('name' => 'name'            , 'value' => 1),   // 名前
		8    => array('name' => 'zip'             , 'value' => 0),   // 郵便番号
		9    => array('name' => 'street'          , 'value' => 1),   // 住所（町域以降～号まで）
		10   => array('name' => 'building'        , 'value' => 0),   // 建物名
		11   => array('name' => 'tel'             , 'value' => 0),   // 電話番号
		12   => array('name' => 'fax'             , 'value' => 0),   // ファックス番号
		13   => array('name' => 'urlGov'          , 'value' => 0),   // 詳細ページコピー元URL
		14   => array('name' => 'urlCopySrc'      , 'value' => 0),   // 詳細ページコピー元名称
		15   => array('name' => 'urlOfficial'     , 'value' => 0),   // 公式サイトURL
		16   => array('name' => 'operatingHour'   , 'value' => 0),   // 営業時間
		17   => array('name' => 'shiftNormal'     , 'value' => 0),   // 保育標準時間
		18   => array('name' => 'shiftShort'      , 'value' => 0),   // 保育短時間
		19   => array('name' => 'quotaStr'        , 'value' => 0),   // 定員数詳細
		20   => array('name' => 'quotaTotal'      , 'value' => 0),   // 定員数合計
		21   => array('name' => 'ageMin'          , 'value' => 0),   // 受入年齢（テキスト）
		22   => array('name' => 'temporary'       , 'value' => 0),   // 一時保育
		23   => array('name' => 'dayClosed'       , 'value' => 0),   // 休園日
		24   => array('name' => 'organization'    , 'value' => 0),   // 設置者名
		25   => array('name' => 'traffic'         , 'value' => 0),   // 交通
		26   => array('name' => 'memo'            , 'value' => 0),   // メモ
		27   => array('name' => 'other1'          , 'value' => 0),   // 予備1（作業用）
		28   => array('name' => 'other2'          , 'value' => 0),   // 予備2（作業用）
	);

	/**
	 *
	 * インポートファイルのヘッダ部フォーマットチェック
	 *
	 * ヘッダ文字列チェック（先頭の数列抜粋）
	 * カラム数チェック
	 *
	 */
	public function chkImportHeader($line) {
		// ヘッダ文字列チェック
		foreach ($line as $key => $value) {
			$columnNo = $key+1;  // 列番号
			if ($line[$key]  !== $this->headerTitle[$key]['name']) {
				$message = "ヘッダ：{$columnNo}列目の名称が{$this->headerTitle[$key]['name']}と異なります。";
				showWarning($message);
				return false;
			}
		}
		// カラム数チェック
		$total = count($this->headerTitle);
		if (count($line) !== $total) {
			$message = "CSVの列数が：{$total}列になっていません。";
			showWarning($message);
			return false;
		}
		// if (WV_DEBUG) debug('ヘッダ部フォーマットOK');
		return true;
	}

	/**
	 *
	 * 必須チェック
	 *
	 */
	public function chkMustValue($line) {
		$errMessage = null;
		foreach ($line as $key => $value) {
			$columnNo = $key+1;  // 列番号
			// 設定値が空の場合に「-、ｰ、0」が設定されているパターンがあるので注意！
			if ((!$line[$key] || $line[$key] === '-' || $line[$key] === 'ｰ' || $line[$key] === '0')
				&& $this->headerTitle[$key]['value']) {
				// 必須項目の値が空の場合
				$errMessage .= "{$this->headerTitle[$key]['name']}は必須です。";
			}
		}
		return $errMessage;
	}

	/**
	 *
	 * 作業ディレクトリ配下の「import.csv」削除
	 *
	 */
	private function resetWokrDir($dir) {
		$output = array();
		$command = "rm -rf {$dir}/import.csv";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, '作業ディレクトリのクリアに失敗しました。');
			return false;
		}
		return true;
	}

	/**
	 *
	 * CSVアップロード
	 *
	 */
	public function upload($files) {
		// 作業ディレクトリ配下をクリア
		if (!$this->resetWokrDir($this->workDir)) {
			return false;
		}
		// サイズチェック
		$importFileSize = $files['importCsv']['size'];
		if ($importFileSize <= 0) {
			showWarning('インポートするCSVファイルを選択して下さい。');
			return false;
		}
		// CSVを作業ディレクトリに移動
		$srcPath = $files['importCsv']['tmp_name'];
		$dstPath = "{$this->workDir}/import.csv";
		$command = "mv {$srcPath} {$dstPath}";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, '作業ディレクトリへの移動失敗');
			return false;
		}
		if (!chmod($dstPath, 0666)) {
			showError($dstPath, 'エラー：chmod 失敗');
			return false;
		}
		return true;
	}

	/**
	 *
	 * インポート前チェック
	 *
	 */
	public function validate(&$resultMessage, &$importData = null) {

		// 既にアップロード済みのCSV
		$dstPath = "{$this->workDir}/import.csv";
		if (!file_exists($dstPath)) {
			showWarning("CSVをアップロードして下さい。");
			return false;
		}

		// タイムスタンプチェック
		$curTime = time();			// 現在時間
		$limitTime = 180;           // 180秒
		if ($curTime - filemtime($dstPath) > $limitTime) {
			// 3分以上経過している場合
			showWarning("CSVアップロードから3分以内にインポートして下さい。");
			return false;
		}

		// UTF-8変換した一時ファイルを作成
		//     正確な文字コード検出が難しいので、UTF-8のファイルも含めて一律変換する。
		$data = file_get_contents($dstPath);
		$data = mb_convert_encoding($data, 'UTF-8', 'sjis-win');
		$temp = tmpfile();
		$meta = stream_get_meta_data($temp);
		fwrite($temp, $data);
		rewind($temp);

		// 一時ファイルの読取り
		$file = new SplFileObject($meta['uri']);
		$file->setFlags(SplFileObject::READ_CSV);

		// インポートデータを作業用の配列に格納
		$lineData = array();			// インポート対象となるデータ
		$janCodeList = array();			// インポート対象となる ItemModel.janCode リスト
		foreach($file as $key => $line) {
			// ヘッダチェック（1行目）
			if ($key === 0) {
				if (!$this->chkImportHeader($line)) {
					return false;
				}
				continue;
			}
			// 空白チェック（最終行はこれに該当するのでスキップ）
			if (!$line[0]) {
				continue;
			}
			// 既存データとの突合対象項目のみ格納したリスト作成
			$janCodeList[] = $line[0];
			// 全データを格納
			$lineData[] = $line;
		}
		fclose($temp);
		$file = null;

		// 行毎のチェック実行
		$resultMessage['line'] = array();				// 行毎に表示するメッセージ
		$totalCnt['add'] = 0;							// 合計数
		$checkResult = true;

		foreach($lineData as $key => $line) {
			$importData[$key] = array();
			$resultMessage['line'][$key] = null;
			// 必須チェック
			if ($errorMessage = $this->chkMustValue($line)) {
				$resultMessage['line'][$key] = $errorMessage;
				$checkResult = false;
			}
			$totalCnt['add']++;
			for($i = 0; $i < count($this->headerTitle); $i++) {
				$importData[$key][$this->headerTitle[$i]['name']] = $line[$i];
			}
		}
		if (!$checkResult) {
			$resultMessage['summary'] = "CSVに不適切な記入があるのでインポートできません。エラー箇所を訂正して再アップロードして下さい。";
		} else {
			$resultMessage['summary'] = "インポートを実行すると［{$totalCnt['add']}件］の施設が新規登録されます。";
		}
		return $checkResult;
	}

	/**
	 *
	 * 作業テーブルへのインポート
	 *
	 */	
	public function import(&$resultMessage) {

		// インポート前チェック
		$importData = array();
		if (!$this->validate($resultMessage, $importData)) {
			return false;
		}

		// 作業テーブル初期化
		if (!Db::truncate('GovSchool_new')) {
			showError($paramUpsert, 'GovSchool_new truncate 失敗');
			return false;
		}

		$resultMessage['line'] = array();
		foreach($importData as $key => $value) {

			$resultMessage['line'][$key] = null;

			// 表記ゆれを吸収
			// 値なしの場合「-、ｰ、0」がセットされていることがあるので削除する。
			//	$checkArr = array('-', 'ｰ', '0');
			//	if (in_array($value['modelName'], $checkArr)) {
			//		$value['modelName'] = null;
			//	}
			//	if (in_array($value['modelNo'], $checkArr)) {
			//		$value['modelNo'] = null;
			//	}
			//	if (in_array($value['price'], $checkArr)) {
			//		$value['price'] = null;
			//	} else {
			//		// 桁区切り（カンマ）削除
			//		$value['price'] = preg_replace('/,/', '', $value['price']);
			//	}

			// GovSchool_new
			$paramUpsert = array(
				'idWork'         => array('value' => $value['idWork']        , 'type' => PDO::PARAM_INT),
				'insTime'        => array('value' => $value['insTime']       , 'type' => 'datetime'),
				'openTime'       => array('value' => $value['openTime']      , 'type' => 'datetime'),
				'cityCode'       => array('value' => $value['cityCode']      , 'type' => PDO::PARAM_INT),
				'authType'       => array('value' => $value['authType']      , 'type' => PDO::PARAM_STR),
				'authTypeSub'    => array('value' => $value['authTypeSub']   , 'type' => PDO::PARAM_STR),
				'opeType'        => array('value' => $value['opeType']       , 'type' => PDO::PARAM_STR),
				'name'           => array('value' => $value['name']          , 'type' => PDO::PARAM_STR),
				'zip'            => array('value' => $value['zip']           , 'type' => PDO::PARAM_STR),
				'street'         => array('value' => $value['street']        , 'type' => PDO::PARAM_STR),
				'building'       => array('value' => $value['building']      , 'type' => PDO::PARAM_STR),
				'tel'            => array('value' => $value['tel']           , 'type' => PDO::PARAM_STR),
				'fax'            => array('value' => $value['fax']           , 'type' => PDO::PARAM_STR),
				'urlGov'         => array('value' => $value['urlGov']        , 'type' => PDO::PARAM_STR),
				'urlCopySrc'     => array('value' => $value['urlCopySrc']    , 'type' => PDO::PARAM_STR),
				'urlOfficial'    => array('value' => $value['urlOfficial']   , 'type' => PDO::PARAM_STR),
				'operatingHour'  => array('value' => $value['operatingHour'] , 'type' => PDO::PARAM_STR),
				'shiftNormal'    => array('value' => $value['shiftNormal']   , 'type' => PDO::PARAM_STR),
				'shiftShort'     => array('value' => $value['shiftShort']    , 'type' => PDO::PARAM_STR),
				'quotaStr'       => array('value' => $value['quotaStr']      , 'type' => PDO::PARAM_STR),
				'quotaTotal'     => array('value' => $value['quotaTotal']    , 'type' => PDO::PARAM_INT),
				'ageMin'         => array('value' => $value['ageMin']        , 'type' => PDO::PARAM_STR),
				'temporary'      => array('value' => $value['temporary']     , 'type' => PDO::PARAM_STR),
				'dayClosed'      => array('value' => $value['dayClosed']     , 'type' => PDO::PARAM_STR),
				'organization'   => array('value' => $value['organization']  , 'type' => PDO::PARAM_STR),
				'traffic'  		 => array('value' => $value['traffic']  	 , 'type' => PDO::PARAM_STR),
				'memo'           => array('value' => $value['memo']          , 'type' => PDO::PARAM_STR),
				'other1'         => array('value' => $value['other1']        , 'type' => PDO::PARAM_STR),
				'other2'         => array('value' => $value['other2']        , 'type' => PDO::PARAM_STR),
			);

			if (!Db::pdoUpsert('GovSchool_new', $paramUpsert)) {
				showError($paramUpsert, 'GovSchool_new 登録・更新失敗');
				return false;
			}

			// 結果の出力作成
			$resultMessage['line'][$key] = "施設名：{$value['name']} を登録しました。";
		}

		return true;
	}

}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	