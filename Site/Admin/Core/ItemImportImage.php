<?php

/**
 *
 * 画像インポート
 * 
 */
class ItemImportImage {

	/**
	 *
	 * 作業ディレクトリ配下の全ディレクトリ・ファイル削除（.gitkeepは残し）
	 *
	 */
	private function resetWokrDir($dir) {
		$output = array();
		$command = "rm -rf {$dir}";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, '作業ディレクトリのクリアに失敗しました。');
			return false;
		}
		return true;
	}

	/**
	 *
	 * 画像アップロード
	 *
	 * janCode 毎にフォルダ分けしてZIP圧縮されたファイルを
	 * 作業フォルダに解凍する。
	 *
	 */
	public function upload($files) {

		// ディレクトリ構成
		$workDir = "images/work";					// 圧縮ファイルを配置する作業ディレクトリ
		$srcTopDir = "{$workDir}/item";		 		// 移動元トップディレクトリ

		// 画像用の作業ディレクトリを削除
		if (!$this->resetWokrDir($srcTopDir)) {
			return false;
		}

		//------------------------------------------------
		// ファイルサイズ・種類のチェック
		//------------------------------------------------
		// ファイル存在チェック
		if ($files['importImg']['size'] <= 0) {
			showWarning('インポートするZIPファイルを選択して下さい。');
			return false;
		}
		// ファイルサイズチェック
		/*if ($files['importImg']['size'] > 31457280) {
			showWarning('1回でインポートするファイルのサイズは30MB以下にして下さい。');
			return false;
		}*/
		// ファイルサイズが post_max_size を超えると $_FILES に値が入らず、
		// 更にPOSTデータも取得できなくなるので要注意。
		if ($_SERVER['CONTENT_LENGTH'] > 31457280) {
			showWarning('1回でインポートするファイルのサイズは30MB以下にして下さい。');
			return false;
		}
		// ZIPファイルかチェック
		$fileName = $files['importImg']['name'];
		$pathParts = pathinfo($fileName);
		$ext = strtolower($pathParts['extension']);    // 拡張子を小文字変換
		if ($ext !== 'zip') {
			showWarning('ZIPファイルを指定して下さい。');
			return false;
		}

		// ZIPファイルを作業ディレクトリに移動
		$zipSrcPath = $files['importImg']['tmp_name'];
		$zipDstPath = "{$workDir}/import.zip";
		$command = "mv {$zipSrcPath} {$zipDstPath}";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, 'アップロードファイルの移動に失敗しました。');
			return false;
		}
		if (!chmod($zipDstPath, 0666)) {
			showError($zipDstPath, 'エラー：chmod 失敗');
			return false;
		}
		// ZIPファイルの解凍
		//     同名ディレクトリが存在する場合失敗する。（上書き不可）
		$command = "unzip -d {$workDir} {$zipDstPath}";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, 'アップロードファイルの解凍に失敗しました。');
			return false;
		}

		// この時点で圧縮ファイルは不要なはずだが、
		// 削除すると何故かトップディレクトリ以外解凍されないので注意！

		if (!file_exists("{$srcTopDir}")) {
			showWarning('施設ID毎のフォルダを格納するフォルダの名称は「item」にして下さい。');
			return false;
		}

		// トップディレクトリ配下の権限変更
		$command = "chmod -R 777 {$srcTopDir}";
		exec($command, $output, $returnVar);
		if ($returnVar !== 0) {
			showError($command, 'ディレクトリの権限変更に失敗しました。');
			return false;
		}
		return true;
	}

	/**
	 *
	 * インポート前チェック
	 *
	 */
	public function validate($importData, &$resultMessage) {

		// 作業に関わるディレクトリ
		$srcTopDir = "images/work/item";		 	// 移動元トップディレクトリ

		// トップディレクトリの存在チェック
		if (!file_exists($srcTopDir)) {
			// インポートをリロードすると発生する。
			// showWarning('画像をアップロードして下さい。');		// このサービスでは画像なしを容認
			return false;
		}

		// CSV側の更新対象施設
		// キー：janCode に変更
		$itemData = Db::changeKey($importData, 'janCode');

		// アップロードされたフォルダ・ファイルのチェック
		$importImageDir = array();
		$dirObject = opendir("{$srcTopDir}");
		while (($kanriDirName = readdir($dirObject)) !== false) {
			if ($kanriDirName === '.' || $kanriDirName === '..')
				continue;
			// ディレクトリ名に半角数字以外あり
			if (!preg_match("/^[0-9]+$/", $kanriDirName)) {
				// 半角数字
				showWarning($kanriDirName . '画像配置フォルダ名に半角数字以外が含まれています。');
				return false;
			}
			$importImageDir[$kanriDirName]['matchCsv'] = false;
		}
		closedir($dirObject);

		// バリデーション
		$checkResult = true;
		$lineMessageIdx = 0;

		foreach ($itemData as $key => $value) {
			$targetPath = "{$srcTopDir}/{$value['janCode']}";

			// 画像フォルダの存在チェック
			if (!file_exists($targetPath)) {
				// $resultMessage['line'][$lineMessageIdx] .= "アップロードした item フォルダ配下に、画像配置フォルダがありません。";
				// $checkResult = false;    // このサービスでは画像なしを容認
				$resultMessage['line'][$lineMessageIdx] .= "画像登録なし";
			} else {
				// 画像の存在チェック（最低1枚は必須）
				if (!file_exists("{$targetPath}/1.jpg")) {
					$resultMessage['line'][$lineMessageIdx] .= "画像：1.jpg がありません。";
					$checkResult = false;
				} else {
					// 画像サイズチェック 500px×500px
					$imgCount = 4;
					for ($i = 1; $i <= $imgCount ; $i++) {
						$targetImgFilePath = "{$targetPath}/{$i}.jpg";
						if (file_exists($targetImgFilePath)) {
							$imageInfo = getimagesize($targetImgFilePath);
							if ($imageInfo[0] !== 500 || $imageInfo[1] !== 500 ) {
								// 横幅・高さのチェック
								$resultMessage['line'][$lineMessageIdx] .= "画像サイズが500px×500pxになっていません。";
								$checkResult = false;
							}
						}
					}
					// マッチしたフォルダを消込み
					$importImageDir[$key]['matchCsv'] = true;
				}
			}
			$lineMessageIdx++;
		}

		// 不要なフォルダのアップロードをチェック
		foreach ($importImageDir as $key => $value) {
			if (!$value['matchCsv']) {
				showWarning($kanriDirName . "不要なフォルダ［{$key}］は削除して再アップロードして下さい。");
				$checkResult = false;
			}
		}

		if (!$checkResult) {
			$resultMessage['summary'] = "施設画像にエラーあるのでインポートできません。エラー箇所を訂正して再アップロードして下さい。</p>";
		}
		return $checkResult;
	}

	/**
	 *
	 * 画像インポート
	 *
	 */	
	public function import(&$importData, &$resultMessage) {

		// 作業に関わるディレクトリ
		$workDir = "images/work";					// 圧縮ファイルを配置する作業ディレクトリ
		$srcTopDir = "{$workDir}/item";		 		// 移動元トップディレクトリ
		$dstTopDir = "images/item";   				// 移動先トップディレクトリ

		// インポート前チェック
		if (!$this->validate($importData, $resultMessage)) {
			return false;
		}

		// 更新対象のアイテム取得
		// キー：janCode に変更
		$itemData = Db::changeKey($importData, 'janCode');

		//------------------------------------------------
		// 所定ディレクトリへの移動実行
		//------------------------------------------------
		// 下層ディレクトリの読取り
		$dirObject = opendir("{$srcTopDir}");
		while (($kanriDirName = readdir($dirObject)) !== false) {

			if ($kanriDirName === '.' || $kanriDirName === '..')
				continue;

			// 移動元ディレクトリ確定
			//     例：images/work/item/10001
			$srcDir = "{$srcTopDir}/{$kanriDirName}";

			// 移動先ディレクトリ確定
			//     例：images/item/10001
			$itemId = $itemData[$kanriDirName]['itemId'];
			if (!$itemId) {
				showError($itemData, $kanriDirName . 'itemId が未確定です。');
				return false;
			}

			$dstDir = "{$dstTopDir}/{$itemId}";

			// ディレクトリ丸ごと移動
			if (file_exists($dstDir)) {
				// 既にあれば削除
				$command = "rm -rf {$dstDir}";
				exec($command, $output, $returnVar);
				if ($returnVar !== 0) {
					showError($command, 'ディレクトリの削除に失敗しました。');
					return false;
				}
			}
			$command = "mv {$srcDir} {$dstDir}";
			exec($command, $output, $returnVar);
			if ($returnVar !== 0) {
				showError($command, 'ディレクトリの移動に失敗しました。');
				return false;
			}
			chmod($dstDir, 0777);
		}
		closedir($dirObject);

		// 画像用の作業ディレクトリを削除
		$this->resetWokrDir($srcTopDir);

		return true;
	}

}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	