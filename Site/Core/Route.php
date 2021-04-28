<?php

/**
 *
 * アクセス制御
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Route {

	// パラメータ、アクセス種別、アクセスページ保持
	private static $route = array();

	// サーバからのアクセス対象ページ
	private $serverAcccessPage = array(
		'Regist/NativeInfo',
	);

	/**
     * コンストラクタ
     */
    public function __construct($argv) {
		self::$route = $this->getRoute($argv);
	}

	/**
	 * ルート設定
	 */
	private static function getSetting() {
		// 現状未使用（コーポレートサイトではパラメータ化が必要そうなシーンがないので）
		$routeSetting = array();
		//$routeSetting = array(
		//	'/Service/Web' 		=> '/{pageDir}/{pageFile}/{name}/{age}',
		//	'/Service/Appli' => '/{pageDir}/{pageFile}/{name}/{age}',
		//);
		return $routeSetting;
	}

	/**
	 * ルート解析
	 *
	 * TODO：小文字のリクエストを変換する処理を追加する。
	 *   全て小文字で来た場合に、先頭だけ大文字に変換して存在チェックできるようにすること。
	 *
	 */
	public static function get()
	{
		return self::$route;
	}

	/**
     * パラメータ、アクセス種別、アクセスページ取得
	 *
     */
    private function getRoute($argv) {

		// 初期値セット
		$route = array(
			'php'   	=> null,
			'html'   	=> null,
			'pageName'  => null,
			'pageExist' => false,
			'param'  	=> array(),
			'paramOrg' 	=> array(),
			'access'  	=> null,
		);

		if (isset($argv)) {
			// コマドライン実行の場合

			// パラメータセット
			$route['param'] = $route['paramOrg'] = $param = formatCommadLineArgv($argv);

			// バッチ処理判定
			if (isset($argv) && isset($param['p']) && preg_match('|Batch/|', $param['p'])) {
				// 念のためブラウザ実行は除外
				$route['pageName'] = $param['p'];
				$route['php'] = WVFW_ROOT . "/Site/User/{$param['p']}.php";
				$route['access'] = 'BATCH';
			} else {
				errorLog($argv, "不正なバッチ処理実行検知");
			}

		} else {

			// パラメータセット
			$route['param'] = $route['paramOrg'] = $param = getHttpQuery();

			// ページ識別子設定
			$pageNameByParam = isset($param['p']) ? $param['p'] : null;
			//if ($pageNameByParam) {
			//	// p={pageName} でのアクセスの場合
			//	// docomo 公式など一部の API では mod_rewrite でのリダイレクトを禁止している場合があるので
			//	// この形式でのアクセスを受け入れる必要ありか。
			//	// $pageName = $param['p'];
			//	if (WV_DEBUG) {
			//		errorLog($param, 'p={pageName} でのアクセス検知');
			//		echo "<pre>";print_r ('p={pageName} でのアクセス検知');echo "</pre>";
			//		exit;
			//	}
			//}

			// アクセス元種別
			if (in_array($pageNameByParam, $this->serverAcccessPage)) {
				$route['access'] = 'SERVER';
			} elseif ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
				|| WV_DEBUG && isset($param['ajaxTest'])) {
				$route['access'] = 'AJAX';
			} else {
				$route['access'] = 'USER';
			}

			// アクセスページ取得
			// 例 http://hoge.com/Service/Web/?a=1&b=2
			//     → REQUEST_URI は /Service/Web?a=1&b=2
			//     → QUERY_STRING は a=1&b=2
			$RequestUri = $_SERVER['REQUEST_URI'];
			$RequestPath = preg_replace('/(.*)\?(.*)/u', '$1', $RequestUri);
			// $query = $_SERVER['QUERY_STRING'];

			// トップページの場合
			if ($RequestPath === '/' && !$pageNameByParam) {
				$route['html'] = WVFW_ROOT . "/Site/User/Template/Top.htm";
				$route['php'] = WVFW_ROOT . "/Site/User/Page/Top.php";
				$route['pageName'] = "Top";
				$route['pageExist'] = true;
				self::$route = $route;
				return $route;
			}

			// getSetting に設定ありかチェック
			$match = false;
			$setting = self::getSetting();
			foreach ($setting as $key => $value) {
				if (preg_match("|^{$key}|u", $RequestPath)) {
					// getSetting に設定ありの場合
					// getSetting の設定による紐付けルールによりパスとパラメータを分解する。
					$match = true;

					// URLのディレクトリ部を配列化
					$pathRequest = ltrim($RequestPath, '/');    // 先頭のスラッシュ削除
					$arrRequestPath = explode('/', $pathRequest);

					// getSetting 値を配列化
					$pathVal = ltrim($value, '/');    // 先頭のスラッシュ削除
					$arrPathVal = explode('/', $pathVal);

					$param = array();
					foreach ($arrRequestPath as $k => $val) {
						if ($arrPathVal[$k] === '{pageDir}') {
							$route['pageName'] .= '/' . $val;
						} elseif ($arrPathVal[$k] === '{pageFile}') {
							$route['pageName'] .= '/' . $val;
						} else {
							$paramKey = preg_replace('/\{|\}/u', '', $arrPathVal[$k]);
							$param[$paramKey] = $val;
						}
					}
					$route['param'] = $param;
					break;
				}
			}

			if (!$match) {
				// getSetting に設定なしの場合
				// パスだけセットされていると判断して処理する。
				$route['pageName'] = ltrim($RequestPath, '/');
			}
			// p={pageName} でのアクセスもここでフォロー
			if (!$route['pageName'] && $pageNameByParam) {
				// 旧サイトの会社概要だけページ名を変更したのでここでフォローしておく。
				if ($pageNameByParam === 'Company') {
					$pageNameByParam = 'About';
				}
				$route['pageName'] = $pageNameByParam;
			}
		}

		// 「ディレクトリ + ファイル名」と判断して、指定されたパスがあるかチェック
		// HTML の存在チェック
		$pathHtml = WVFW_ROOT . "/Site/User/Template/{$route['pageName']}.htm";

		if (file_exists($pathHtml)) {
			$route['html'] = $pathHtml;
		} else {
			// ディレクトリパス・ファイル名に間違って小文字が混在していた場合
			$wvfwRootDir = WVFW_ROOT;
			// ディレクトリ込みだと find コマンドエラーになるのでファイル名だけ抽出
			// ディレクトリ込みで大文字・小文字の区別をせずに存在チェックするようなコマンドが
			// 存在しないようなのでかなり力技な実装になっている点に注意！
			$fileName = getBaseName("{$route['pageName']}.htm");
			$command = "find $wvfwRootDir -iname '{$fileName}' ";
			exec($command, $output, $returnVar);
			if (isset($output[0]) && $output[0]) {
				// 本来の正常なパスをセット
				$route['html'] = $pathHtml = $output[0];
				// 本来の正常なページ名で上書き
				$route['pageName'] = preg_replace("|{$wvfwRootDir}/Site/User/Template/|u", '', $pathHtml);
			}
		}

		// PHP の存在チェック
		if ($route['access'] === 'AJAX') {
			$pathPhp = WVFW_ROOT . "/Site/User/Ajax/{$route['pageName']}.php";
		} else {
			$pathPhp = WVFW_ROOT . "/Site/User/Page/{$route['pageName']}.php";
		}
		if (file_exists($pathPhp)) {
			$route['php'] = $pathPhp;
		}

		if ($route['html'] || $route['php']) {
			$route['pageExist'] = true;
		}

		self::$route = $route;
		return $route;
    }

}
































































































