<?php

/**
 *
 * フレームワーク全体の共通処理
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

/**
 * 
 * ページャ
 * 
 */
class wvPager {

	/**
	 * ページャ情報取得
	 *
	 * 左右に表示するインデックスに余裕がある場合、現在のページ番号は真ん中に配置される。 
	 *
	 *   $totalCnt         ：対象データの総数
	 *   $page
	 *       curPageNo     ：現在のページ番号
	 *       countPerPage  ：1ページ毎の表示件数
	 *       indexDelta    ：前後のインデックス数
	 *   $curPageItemCnt   ：現在のページに表示するアイテム数
	 *   $query            ：クエリ 
	 *
	 */
	public static function getPager($totalCnt, $page, $curPageItemCnt, $query){

		$curPageNo = $page['curPageNo'];
		$countPerPage = $page['countPerPage'];
		$indexDelta = $page['IndexDelta'];

	    // 全ページ数（＝最終ページ番号）
	    $totalPageCnt = ceil($totalCnt / $countPerPage);

		// 表示するインデックスの最大値
		$showIndexMaxCnt = $indexDelta * 2 + 1;
		$showIndexMaxCnt = ($showIndexMaxCnt > $totalPageCnt ? $showIndexMaxCnt : $totalPageCnt);

	    //------------------------------------------------
	    // インデックスのページ番号
	    //------------------------------------------------
	    // 表示する左端 ～ 右端を確定
	    $firstVisiblePageNo = 1;
	    $lastVisiblePageNo = $totalPageCnt;
	    if ($curPageNo <= ($indexDelta + 1)) {
	        // 左端ページ番号が1の場合
	        $lastVisiblePageNo = $indexDelta * 2 + 1;	
	        if ($lastVisiblePageNo > $totalPageCnt) {
				$lastVisiblePageNo = $totalPageCnt;
	        }
	    } else {
	        // 左端ページ番号が2以上の場合 
	        if (($curPageNo + $indexDelta) <= $totalPageCnt) {
	            // 右端に表示するインデックスに余裕ありの場合
	            $firstVisiblePageNo = $curPageNo - $indexDelta;
				$lastVisiblePageNo = $curPageNo + $indexDelta;
				
	        } else {
	            // 右端に表示するインデックスが不足する場合
	            
	            // 右端は最後のページで確定
				$lastVisiblePageNo = $totalPageCnt;
				
				// 左端は、右側の不足分を追加する。
				$lackPageCnt = $curPageNo + $indexDelta - $lastVisiblePageNo; // 右側の不足分
				$firstVisiblePageNo = ($curPageNo - $indexDelta) - $lackPageCnt;
	           $firstVisiblePageNo = ($firstVisiblePageNo > 0 ? $firstVisiblePageNo : 1);		
	        }
	    }
		
	    // 配列にセット
	    $indexList = array();	
	    for ($i = $firstVisiblePageNo; $i <= $lastVisiblePageNo; $i++) {
	        $indexList[] = $i;
	    }
		
	    //------------------------------------------------
	    // 「最初へ・前へ・次へ・最後へ」のページ番号
	    //------------------------------------------------
	    // 左端の「最初へ」
	    //     左端のページ番号が1の場合は非表示
	    $firstPageNo = ($firstVisiblePageNo === 1 ? null : 1);

	    // 右端の「最後へ」
	    //     右端のページ番号が最終ページ番号でなければ表示
	    $lastPageNo = ($lastVisiblePageNo < $totalPageCnt ? $totalPageCnt : null);

	    // 左端の「前へ」
	    if (intval($curPageNo) === 1) {
	        // 最初のページは「前へ」を非表示
	        $prevPageNo = null;
	    } else {
	        $prevPageNo = $curPageNo - 1;
	    }
	    // 右端の「次へ」
	    if ($curPageNo >= $totalPageCnt){
	        // 最後のページは「次へ」を非表示
	        $nextPageNo = null;
	    } else {
	        $nextPageNo = $curPageNo + 1;
	    }

        $pager['totalPageCnt'] = $totalPageCnt;         // 全ページ数
        $pager['curPageNo'] = $curPageNo;               // 現在のページ番号
        $pager['firstPageNo'] = $firstPageNo;           // 最初のページ番号
        $pager['prevPageNo'] = $prevPageNo;             // 次のページ番号
        $pager['nextPageNo'] = $nextPageNo;             // 前のページ番号
        $pager['lastPageNo'] = $lastPageNo;             // 最後のページ番号
        $pager['indexList'] = $indexList;               // ページインデックスリスト

	    //------------------------------------------------
	    // 引継ぎパラメータ設定
	    //------------------------------------------------
	    // 現在のページ番号はセット済みなので削除
		if (isset($query['curPageNo'])) unset($query['curPageNo']);
		$pager['query'] = http_build_query($query);
	
	    //------------------------------------------------
	    // 項目の件数／合計設定
	    //------------------------------------------------
		$pager['curPageItem']['startCnt'] = $countPerPage * ($curPageNo - 1) + 1;
		$pager['curPageItem']['endCnt'] = $countPerPage * ($curPageNo - 1) + $curPageItemCnt;
		$pager['totalCnt'] = $totalCnt;
		
	    return $pager;
	}

	/**
	 * 
	 * ページャのページ設定取得
	 * 
	 *     $page['countPerPage']
	 *     $page['IndexDelta']
	 *     $page['curPageNo']   
	 *
	 */
	public static function getPageInfo($curPageNo, $totalCnt, $pageName, &$config) {
		// 1ページ毎の表示件数
		if (isset($config['PagerCountPerPage'][$pageName])) {
			$page['countPerPage'] = $config['PagerCountPerPage'][$pageName];
		} else {
			$page['countPerPage'] = $config['PagerCountPerPage']['Default'];
		}

		// 前後のインデックス数
		if (defined('SCREEN_SIZE') && SCREEN_SIZE  === 'small' && isset($config['Pager']['IndexDeltaSp'])) {
			$page['IndexDelta'] = $config['Pager']['IndexDeltaSp'];
		} else {
			$page['IndexDelta'] = $config['Pager']['IndexDelta'];
		}

		// 現在のページ番号
		$curPageNo = isset($curPageNo) ? $curPageNo : 1; 

		// ページ番号指定不正チェック
		if (($curPageNo > ceil($totalCnt / $page['countPerPage'])) || $curPageNo < 1) {
			$curPageNo = 1;
		}		
		$page['curPageNo'] = $curPageNo;
		return $page;
	}
}

/**
 * ワンタイムトークン
 *
 *     CSRF対策
 *
 */
class Token {

    private static $name = 'token';

     /**
     *
     * ワンタイムトークン作成
     *
     */
    public static function generate($sessName = null) {
    	if (!$sessName) $sessName = self::$name;
        if (isset($_SESSION[$sessName])) unset($_SESSION[$sessName]);
        $_SESSION[$sessName] = md5(uniqid().mt_rand());
        return $_SESSION[$sessName];
    }

     /**
     *
     * ワンタイムトークン削除
     *
     */
    public static function delete($sessName = null) {
    	if (!$sessName) $sessName = self::$name;
		if ($_SESSION[$sessName]) {
			// セッションある場合

			// セッションクリア
			$_SESSION[$sessName] = array();
       }
        return;
    }

     /**
     *
     * トークンチェック
     *
     */
    public static function validate($token) {
	    if (session_status() === PHP_SESSION_NONE) {
			session_start();
	    }
		if ($_SESSION[self::$name] !== $token) {
            return false;
       }
        // ワンタイムセッションリセット
        unset($_SESSION[self::$name]);
        return true;
    }
}

//------------------------------------------------
// セッション
//------------------------------------------------
/**
 *
 * セッションリセット
 *
 * 現状、デバッグ専用ページでのみ使用。
 *
 */
function resetSession($key = null) {
	if ($key) {
		// 対象セッションを初期化
		$_SESSION[$key] = null;
	} else {
		// 全セッションを初期化
		$_SESSION = null;
	}
	session_regenerate_id(true);
	return;
}

/**
 *
 * クッキーリセット
 *
 */
function resetCookie($key = null) {
	if ($key){
		// 有効期限を過去に設定して削除
		// setcookie($key, '', time() - 1800, '/; SameSite=None', '', true, true);
		setcookie($key, '', time() - 1800, '/');
	} else {
		// 全クッキー削除
		foreach ($_COOKIE as $key => $value) {
			// setcookie($key, '', time() - 1800, '/; SameSite=None', '', true, true);
			setcookie($key, '', time() - 1800, '/');
		}
	}
	return;
}

//------------------------------------------------
// ネットワーク
//------------------------------------------------
/**
 *
 * GET、POSTのクエリ文字列取得
 *
 * サニタイズされた安全なデータを取得する。
 *
 */
function getHttpQuery() {
    $param = array();
    // POST で hidden が設定されている場合、GET には、何故か呼出元のページパラメータが入ってくるので注意。
    // 同じ KEY で上書きできないため、GET データを先にセットすると POST で上書きされない。
    if ($_GET) {
        $param += setQuery($_GET);
    }
    if ($_POST) {
        $param += setQuery($_POST);
    }
    return $param;
}

/**
 *
 * GET、POSTのクエリ文字列をサニタイズして配列に格納
 *
 */
function setQuery($input) {
	return array_map('htmlspecialcharsEx', $input);
}

/**
 *
 * htmlspecialchars の再帰処理
 *
 * htmlspecialcharsは配列を処理できないので再帰処理が必要。
 * 実行すると「Warning:  htmlspecialchars() expects parameter 1 to be string」になる。
 *
 */
function htmlspecialcharsEx($str) {
	if (is_array($str)) {
		return array_map("htmlspecialcharsEx", $str);
	} else {
		// 特殊文字をHTMLエンティティに変換する。
		//     対象文字：「& " ' < >」 → 「&amp; &quot; &#039; &lt; &gt;」
		//     ※バッククォート（`）、バックスラッシュ（\）は対象外なので注意！
		// ENT_QUOTES  ：シングルクウォート含む場合必須。
		return htmlspecialchars($str, ENT_QUOTES);
	}
}

/**
 *
 * GET、POSTのクエリ文字列取得
 *
 * サニタイズなしの生データを取得する。
 *
 */
function getRawHttpQuery() {
	$param = array();
	if ($_GET) {
		$param += $_GET;
	}
	if ($_POST) {
		$param += $_POST;
	}
	return $param;
}

/**
 * 現在のURL取得
 *
 * dirname：http～ドメイン名まで
 * baseDir：http～最終ディレクリまで
 * baseUrl：http～ファイル名まで
 *
 */
function getCurrentUrl() {
    // 現在のリクエストURL全体
    $curUrl = (empty ($_SERVER ['HTTPS']) ? 'http://' : 'https://') . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
    $result = parse_url($curUrl);

    // リクエスト例
    // http://hoge.com/test1/test.php?test=abc&test1=def
    //  Array
    //  (
    //      [scheme] => http
    //      [host] => hoge.com
    //      [path] => /test1/test.php
    //      [query] => test=abc&test1=def
    //  )

    // ホスト名まで （http://hoge.com）
    $url['host'] = $result['scheme'] . '://' . $result['host'];
    // ディレクトリとファイルまで（http://hoge.com/test1/test.php）
    $url['path'] = $result['scheme'] . '://' . $result['host'] . $result['path'];
    // 全体（$curUrlと同じ）
    $url['full'] = $result['scheme'] . '://' . $result['host'] . $result['path'] . '?' . $result['query'];
    return $url;
}

/**
 *
 * クエリ文字列を「key = value」の連想配列に変換
 *
 * parse_str の修正版
 * parse_str によるHTML禁止文字の自動変換（「+ → スペース」など）
 * を避けたい場合に利用する。（特殊用途）
 *
 */
function parseQueryStr($query) {
	$arrResult = array();
	$arr1 = explode('&', $query);
	foreach($arr1 as $key => $value) {
		$arr2 = explode('=', $value);
		$arrResult[$arr2[0]] = $arr2[1];
	}
	return $arrResult;
}

/**
 *
 * Ajax直URL作成
 *
 */
function getAjaxDirectUrl($param) {
	$query = http_build_query($param);
	return SITE_URL . "?{$query}&ajaxTest=1";
}

/**
 * クローラーのチェック
 *
 * 参考サイト
 *     https://www.casis-iss.org/ex1911/
 *
 */
function checkCrawler($agent) {
	$crawler = array(
		// アクセス許可（permit）
		'Google Search Console'         => array('userAgent'    => 'Googlebot'                  , 'access' => 'permit'),        //
		'AdsBot-Google'                 => array('userAgent'    => 'AdsBot-Google'              , 'access' => 'permit'),        //
		// 'Google favicon'             => array('userAgent'    => 'Google favicon'             , 'access' => 'permit'),        //
		// 'Google Web Preview'         => array('userAgent'    => 'Google Web Preview'         , 'access' => 'permit'),        //
		// 'Google Site-Verification'   => array('userAgent'    => 'Google Site-Verification'   , 'access' => 'permit'),        //
		'Msn'                           => array('userAgent'    => 'msnbot'                     , 'access' => 'permit'),        //
		//'Baidu'                       => array('userAgent'    => 'baidu.com'                  , 'access' => 'permit'),        // 百度
		//'Bing'                        => array('userAgent'    => 'bing.com'                   , 'access' => 'permit'),        //
		// アクセス禁止（forbidden）
		'Nimbostratus'                  => array('userAgent'    => 'Nimbostratus-Bot'           , 'access' => 'forbidden'),      //
		'MJ12bot'                       => array('userAgent'    => 'MJ12bot'                    , 'access' => 'forbidden'),      // スパムではないが質が悪いので除外。
		'Mail.RU_Bot'                   => array('userAgent'    => 'go.mail.ru'                 , 'access' => 'forbidden'),      // ロシアの検索サイト
		'PhpMyAdmin'                    => array('userAgent'    => 'phpMyAdmin'                 , 'access' => 'forbidden'),      //
		'Testproxy'                     => array('userAgent'    => 'testproxy.php'              , 'access' => 'forbidden'),      //
		'WordPress'                     => array('userAgent'    => 'wp-login.php'               , 'access' => 'forbidden'),      // wp-login.php はログインの不正アタックで有名。
		'Netcraft'                      => array('userAgent'    => 'NetcraftSurveyAgent'        , 'access' => 'forbidden'),      // スパムではないが質が悪いので除外。
		'Yandex'                        => array('userAgent'    => 'Yandex'                     , 'access' => 'forbidden'),      // ロシアの検索エンジン最大手
		'ipip.net'                      => array('userAgent'    => 'ipip.net'                   , 'access' => 'forbidden'),      // 中国系の何かぽい
		'bytespider'                    => array('userAgent'    => 'bytespider'                 , 'access' => 'forbidden'),      // 詳細不明
		'SemrushBot'                    => array('userAgent'    => 'SemrushBot'                 , 'access' => 'forbidden'),      // ドミニカ連邦のSEOサービス。アクセスできないファイルでも無視してくるのでたちが悪い。
	);
	foreach ($crawler as $key => $value) {
		$ua = $value['userAgent'];
		if (preg_match("/$ua/i", $agent)) {
			return $value['access'];
		}
	}
	// それ以外で「bot」の文字ありかチェック
	// if (preg_match("/bot/i", $agent)) {
	if(stripos($agent, 'bot') !== false) {
		// ここで絶対に errorLog を呼ばないこと！
		// checkCrawler は errorLog 中で使っているために無限ループになる！
		// Allowed memory size of 1073741824 bytes exhausted
		// errorLog($agent, '未確認のbot検知！');
		// debug($agent, '未確認のbot検知！', ERROR_LOG_FILE);		// これなら問題なし
		return 'none';
	}
	// ユーザーエージェントなしの場合
	if (!$agent) {
		// errorLog($_SERVER, '空のユーザーエージェント検知！');
		// debug($_SERVER, '空のユーザーエージェント検知！', ERROR_LOG_FILE);		// これなら問題なし
		return 'none';
	}
	return 'none';
}

/**
 *
 * ソートパラメータのセット
 *
 * $param
 *     クエリパラメータ
 * $sortCol
 *     ソートに指定された列名。sortOrder とセットで使う点に注意！
 * 	   $param 中に $sortCol と合致するキーが含まれない場合、
 * $sortOrder
 *     URLのsortOrder=asc の場合：昇順
 *     URLのsortOrder=desc の場合：降順
 *
 *
 */
function setSortParam($param, $sortCol, $sortOrder) {
	$sort['sortCol'] = isset($param['sortCol']) && $param['sortCol'] ? $param['sortCol'] : $sortCol;
	$sort['sortOrder'] = isset($param['sortOrder']) && $param['sortOrder'] ? $param['sortOrder'] : $sortOrder;
	$sort['toggleOrder'] = ($sort['sortOrder'] === 'desc') ? 'asc' : 'desc';
	// ▲：昇順に並んだ状態（クリックすると降順に）、▼：降順に並んだ状態（クリックすると昇順に）
	$sort['toggleMark'] = ($sort['toggleOrder'] === 'asc') ? '▼' : '▲';

	// クエリ付加パラメータ
	if (isset($param['sortOrder'])) unset($param['sortOrder']);
	if (isset($param['sortCol'])) unset($param['sortCol']);
	// sortCol はリンク毎
	$sort['query'] = http_build_query($param) . "&sortOrder={$sort['toggleOrder']}";
    return $sort;
}

//------------------------------------------------
// セキュリティー
//------------------------------------------------
// スパム防止用のリファラチェック
function refererCheck($refererCheckDomain) {
    if (strpos($_SERVER['HTTP_REFERER'], $refererCheckDomain) === false) {
        return exit('<p align="center">リファラチェックエラー。フォームページのドメインと、このファイルのドメインが一致しません。</p>');
    }
}

/**
 * サニタイズ
 */
function sanitize($string) {
    if (is_array($string)) {
        return array_map("sanitize", $string);
    } else {
        return htmlspecialchars($string, ENT_QUOTES);
    }
}
//------------------------------------------------
// 文字列処理
//------------------------------------------------
/**
 *
 * スペースの整形
 *
 * 全角スペースを半角に変更
 * 前後のスペース削除
 *
 */
function formatSpace($str) {
	// 全角スペースを半角に変更
	$str = mb_convert_kana($str, "s", 'UTF-8');

	// 連続する全角スペース、半角スペース、タブを1スペースに変換
	// $str = preg_replace('/(\s+|\t+|\xC2\xA0)/u', ' ', $str);
	$str = preg_replace('/(\s+|\t+)/u', ' ', $str);

	// 前後のスペース削除（先頭・末尾のスペース削除）
	//$str = preg_replace('/(\s+)$/u', '', $str);
	//$str = preg_replace('/^(\s+)/u', '', $str);
	$str = trim($str);

	return $str;
}

/**
 *
 * 全ての全角・半角スペース削除
 *
 */
function deleteSpace($str, $deleteNbsp = false) {
    // 半角・全角スペース削除
    $str = preg_replace('/[ 　]+/u', '', $str);

    // 改行なしスペース削除（&nbsp;）
    if ($deleteNbsp) {
		$str = preg_replace("/\xC2\xA0/", "", $str);
	}
	return $str;
}

/**
 *
 * 住所等の整形
 *
 * 全角英数を半角に変換。
 * スペースを整形
 * 各種ハイフンを半角マイナスに変更
 *
 */
function formatAddress($str, $delSpace = true, $chgHyphen = true, $chgKanji = true) {

	// 英数字を半角に変換（「（ → (」など記号も変換されるので注意！）
	// 半角カタカナを全角に変換（濁点を含めて1文字の全角カタカナに変換）
	// https://www.softel.co.jp/blogs/tech/archives/4517
	$str = mb_convert_kana($str, 'aKV' ,"UTF-8");

	// スペースの整形
	$str = formatSpace($str);

	// 改行削除
	$str = convertEOL($str);
	$str = preg_replace('/\n/um', '', $str);

	// 【特殊処理】「スペース○F|階」のスペースをハイフンに置換
	// 「1-1-1 2F」などとなっていた場合に、次の処理でスペース削除すると
	// 数字が繋がって divideAddress でも建物と分離できなくなるのでここで強引に回避している。
	if (preg_match('/(.*) ([0-9]+)(F|階)$/u', $str, $matches)) {
		// 「-○F」または「-○階」の分離
		$str = $matches[1] . '-' . $matches[2] . 'F';
	}

	// 全ての全角・半角スペース削除
	if ($delSpace) $str = deleteSpace($str);

    // ハイフンを半角マイナスに変更
	if ($chgHyphen) $str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '-', $str);

	if ($chgKanji) {
		// 丁目、番地、号を「-」に置換
		// 「四谷」など住所の一部は変換しないこと！
		// 漢数字 [一二三四五六七八九十壱弐参拾百千万萬億兆〇]丁目
		//         [一二三四五六七八九十壱弐参拾百千万萬億兆〇]番
		//         [一二三四五六七八九十壱弐参拾百千万萬億兆〇]号
		//$search = array('〇', '一', '二', '三', '四', '五', '六', '七', '八', '九');
		$search = array('〇丁目', '一丁目', '二丁目', '三丁目', '四丁目', '五丁目', '六丁目', '七丁目', '八丁目', '九丁目');
		//$replace = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		$replace = array('0丁目', '1丁目', '2丁目', '3丁目', '4丁目', '5丁目', '6丁目', '7丁目', '8丁目', '9丁目');
		$str = str_replace($search, $replace, $str);
		if (preg_match('/(.*?[0-9]+?)丁目([0-9]+?)(番地|番)([0-9]+?)号(.*)/u', $str, $matches)) {
			$str = $matches[1] . '-' . $matches[2] . '-' . $matches[4] . $matches[5];
		} elseif (preg_match('/(.*?[0-9]+?)丁目([0-9]+?)(番地|番)(.*)/u', $str, $matches)) {
			$str = $matches[1] . '-' . $matches[2] . $matches[4];
		} elseif (preg_match('/(.*?[0-9]+?)丁目([0-9]+?.*)/u', $str, $matches)) {
			// 丁目の後が「数字-」の場合
			$str = $matches[1] . '-' . $matches[2];
		} elseif (preg_match('/(.*?[0-9]+?)丁目(.*)/u', $str, $matches)) {
			$str = $matches[1] . $matches[2];
		} elseif (preg_match('/(.*)番地$/u', $str, $matches)) {
			// 末尾の番地を除去 「番地$」→ ''
			$str = $matches[1];
		}

	}

	// 記号「’」→「'」（英語の second's など）
	$str = preg_replace('/’/u', "'", $str);

	// ハイフンが3つあるものを調整
	//     -[0-9]+-[0-9]+-[0-9]+ → ありえることが判明したのでスルー

	// TODO：住所の間のカンマを削除（例：伊勢原市池端536番地、桜台1-4-2）
	//    保育施設でよくあるので最初だけ残す。
	if (preg_match('/、|,/u', $str, $matches)) {
		$str .= '★要修正';
	}

	return $str;
}

/**
 *
 * 住所と建物名の分離
 *
 */
function divideAddress($str) {
	$arrStr['street'] = $arrStr['building'] = null;
	if (preg_match('/(.*)-([0-9]+)(F|階)$/u', $str, $matches)) {
		// 末尾の「-○F」または「-○階」の分離
		$arrStr['street'] = $matches[1];
		$arrStr['building'] = "{$matches[2]}F";
	//} elseif (preg_match('/(.*-[0-9]+)(.*-[0-9]+.*)/u', $str, $matches)) {
	//	// FIXME：「-数字パターン」が2箇所ある場合の分離 → これがあると通常のおかしくなる。
	//	// 例：藤沢市藤が岡1-12コンフォ-ル藤沢C3-104 → 藤沢市藤が岡1-12 コンフォ-ル藤沢C3-104 に分離非対応
	//	$arrStr['street'] = $matches[1];
	//	$arrStr['building'] = $matches[2];
	} elseif (preg_match('/(.*-[0-9]+)(.*)/u', $str, $matches)) {
		// 「-数字パターン」が1箇所ある場合の分離
		$arrStr['street'] = $matches[1];
		$arrStr['building'] = $matches[2];
	} else {
		$arrStr['street'] = $str;
	}
	if ($arrStr['building']) {
		$arrStr['building'] = deleteSpace($arrStr['building']);
		if (!$arrStr['building']) {
			$arrStr['building'] = null;
		}
	}
	if (preg_match('/F|\(|（/u', $arrStr['street'], $matches)) {
		// 「F, (, （」ありの場合
		if (!$arrStr['building']) {
			$arrStr['building'] = '★要修正';
		}
	}
	return $arrStr;
}

/**
 *
 * 建物名の整形
 *
 */
function formatBuilding($str, $chgHyphen = true, $chgFloor = true) {

	// 英数字を半角に変換（「（ → (」など記号も変換されるので注意！）
	// 半角カタカナを全角に変換（濁点を含めて1文字の全角カタカナに変換）
	$str = mb_convert_kana($str, 'aKV' ,"UTF-8");

	// 全ての全角・半角スペース削除
	$str = deleteSpace($str);

	// ハイフンを半角マイナスに変更
	if ($chgHyphen) $str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '-', $str);

	return $str;
}

/**
 *
 * 郵便番号のフォーマットチェック
 *
 */
function checkZip(&$str, $format = true, $delHyphen = false) {
	// ハイフン削除
	if ($delHyphen && !preg_match('/([0-9]{7})/us', $str, $matchs)) {
		$str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '', $str);
	} elseif ($format) {
		// ハイフン変換
		$str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '', $str);
	}
	if (!preg_match('/([0-9]{7})/us', $str, $matchs)) {
		return false;
	}
	return true;
}

/**
 *
 * 電話番号のフォーマットチェック
 *
 */
function checkTel(&$str, $format = true) {
	if ($format) {
		// ハイフン変換
		$str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '', $str);
	}
	// 長さチェック（ハイフンなしで10～11以外はエラー、ハイフン込で14以上はエラー）
	$len = mb_strlen($str, "UTF-8");
	if ($len > 13 || $len < 10) {
		return false;
	}
	return true;
}

/**
 *
 * 住所のフォーマットチェックと分割
 *
 */
function checkAddress(&$str, $format = true) {
	if ($format) {
		// ハイフン変換
		$str = preg_replace('/－|ー|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', '', $str);
	}
	// 長さチェック（ハイフンなしで10～11以外はエラー、ハイフン込で14以上はエラー）
	$len = mb_strlen($str, "UTF-8");
	if ($len > 13 || $len < 10) {
		return false;
	}
	return true;
}
// 		// 長さチェック
// 2行またぎを1行に変更 \n^\t(.*) → $1\n
// ○○市、○○区を削除

/**
 *
 * 名前・タイトルの整形
 *
 * とりあえず施設名専用。
 *
 */
function formatName($str, $delSpace = true) {

	// 英数字を半角に変換（「（ → (」など記号も変換されるので注意！）
	// 半角カタカナを全角に変換（濁点を含めて1文字の全角カタカナに変換）
	// https://www.softel.co.jp/blogs/tech/archives/4517
	$str = mb_convert_kana($str, 'aKV' ,"UTF-8");

	// ハイフンを全角に変更
	// 横浜市港北区「港北子育て支援ﾜｰｶｰｽﾞ・ｺﾚｸﾃｨﾌﾞ　ｺｺｯﾄ」など長い名前の場合だけ半角記号になってる。
	// $str = preg_replace('/－|﹣|−|⁻|₋|‐|‑|‒|–|—|―/i', 'ー', $str);
	$str = preg_replace('/ｰ/ui', 'ー', $str);

	// スペースの整形
	$str = formatSpace($str);

	// スペースの削除
	if ($delSpace) {
		// 全ての全角・半角スペース削除
		$str = deleteSpace($str);

		// 改行なしスペース削除（&nbsp;）
		$str = deleteSpace($str, true);
	}

	// スペースだけ残った場合は削除
	if ($str === ' ') {
		$str = '';
	}

	return $str;
}

/**
 * 改行付きの文章を配列に格納
 */
function replaceLineBreakToArray($str) {
	$str = str_replace(array('\r\n','\r','\n'), '\n', $str);	// 改行コードをLFに統一
	$str = explode("\n", $str);									// 配列に格納
	$str = array_map('trim', $str); 							// 各要素をトリム
	$str = array_filter($str, 'strlen'); 						// 空の要素を削除
	$str = array_values($str); 									// キーを連番に振り直し
	return $str;
}

/**
 *
 * 先頭行を取得（改行区切りリストから）
 *
 * 改行（\n）区切り文章から先頭の指定行数分を取得する。
 *
 */
function getHeadFromLineBreakList($envList, $count = 5) {

	if (!$envList) return;

	// 一旦配列化
	$envList = replaceLineBreakToArray($envList);

	// 指定行数分の改行区切りリストを作成
	$count = ($count < count($envList) ? $count : count($envList));
	$result = null;
	if ($envList) {
	    for ($i = 0; $i < $count; $i++) {
	        $result .= "{$envList[$i]}\n";
	    }
	 }
	return $result;
}

/**
 * ディレクトリ名を取得
 *
 * /var/www/hoge/test.htm → /var/www/hoge
 *
 */
function getDirName($path) {
	$pathParts = pathinfo($path);
	return $pathParts['dirname'];
}

/**
 *
 * ファイル名を取得
 *
 * パスに日本語が含まれる場合、対応できないので注意！
 * 例：http://wd-valley.com/サンプル_BBB.jpg → _BBB.jpg になる。
 * 日本語が含まれる場合、zmbUrlencode を使うこと！
 *
 */
function getBaseName($path) {
	$pathParts = pathinfo($path);
	return $pathParts['basename'];
}

/**
 *
 * パスに日本語を含むURLを urlencode する
 *
 * パスに日本語を含む場合、日本語部分のみURLエンコードする必要あるので、
 * これ以外方法がないかも。
 *
 * $url = 'http://wd-valley.com/サンプル_BBB.jpg';
 * $url = zmbUrlencode($url);
 * print_r ($url . "\n");				// http://wd-valley.com/%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB_BBB.jpg
 * print_r (urldecode($url) . "\n");	// http://wd-valley.com/サンプル_BBB.jpg
 * 参考サイト：https://zakkiweb.net/a/25/
 *
 */
function zmbUrlencode($str) {
	return preg_replace_callback(
		'/[^\x21-\x7e]+/',
		function($matches) {
			return urlencode($matches[0]);
		},
		$str);
}

/**
 * 拡張子を除いたファイル名を取得
 */
function getFileName($path) {
	$pathParts = pathinfo($path);
	return $pathParts['filename'];
}

/**
 * 拡張子を取得
 */
function getExtName($path) {
	$pathParts = pathinfo($path);
	$extension = isset($pathParts['extension']) ? $pathParts['extension'] : null;
	return $extension;
}

/**
 * 拡張子を変更
 */
function replaceExtName($path, $ext) {
	$dir = getDirName($path);
	if ($dir) $dir .= '/';
	return $dir . getFileName($path) . '.' . $ext;
}

/**
 * 指定文字をファイル名末尾に追加
 *     $path = 'http://phpjp.com/info/index.htm';
 *     $pathThumb = addNameTail($path, '_s');	// http://phpjp.com/info/index_s.htm
 */
function addNameTail($fpath, $strAdd) {
	return preg_replace("/(.\\w+)$/", "{$strAdd}$1", $fpath);
}

/**
 * 指定文字をファイル名末尾から削除
 *     $path = '/var/www/aaa_s.htm';
 *     $path = deleteNameTail($path, '_s');			// /var/www/aaa.htm
 */
function deleteNameTail($fpath, $strDel) {
	return preg_replace("/{$strDel}(.\\w+)$/", "$1", $fpath);
}

/**
 *
 * 特定の文字列以降をカット
 *
 * URLのパラメータカットなどに使用。
 * explode の処理を簡易にしただけ。
 *
 */
function cutTailString($str, $delim = '?') {
	$result = explode($delim, $str);
	return $result[0];
}

/**
 *
 * ランダム文字列生成
 *
 * 半角英小文字、半角英大文字、半角数からランダム文字列を生成する。
 * 58の $length 乗
 *
 * $length: 生成する文字数
 *
 */
function makeRandString($length) {
    $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    $result = null;
    for ($i = 0; $i < $length; $i++) {
        $result .= $str[rand(0, count($str) - 1)];
    }
    return $result;
}

/**
 *
 * ユニークID生成
 *
 * 0～9、a～f から推測困難な一意なIDを生成する。
 * 完璧かどうか不明だが実用的には問題なさそう。
 * 16の $length 乗なので makeRandString の方が良さそうだが
 * こちらの方が高速かも。
 *
 * $length: 生成する文字数
 *
 */
function makeUniqueId($length = null) {
	// 40文字生成
	$id = sha1(uniqid(mt_rand(), true));
	// 末尾をトリム
	if ($length) {
		$id = substr($id, 0, $length);
		return $id;
	}
}

/**
 *
 * 長い文章の切り詰め（三点リーダー、...、…、クランプ文字、省略記号、カット、トリム、文字列を丸める）
 *
 * mb_strimwidth と同類。
 * 全角は2、半角は1でカウントする。
 *
 * $str		：丸める文字列
 * $count	：丸めた後の文字幅（三点リーダーも合わせた幅）
 *
 */
function cutLongText($str, $count, $mask = true) {
	if(mb_strlen($str, "UTF-8") > $count) {
		// 全角・半角問わず、文字 $count より多い場合、
		if ($mask) {
			// ($count - 1)文字に切り詰めて末尾に「…」を付加
			$str = mb_substr($str, 0, $count, "UTF-8") . '…';
		} else {
			$str = mb_substr($str, 0, $count+1, "UTF-8");
		}
	}
	return $str;
}

/**
 *
 * 長い文章の切り詰め（カラム指定）
 *
 * リストの特定カラム文章を指定最大文字でカットする。
 *
 * ＜例＞
 * cutLongTextByColName($data, 'detail', 100)
 *
 */
function cutLongTextByColName(&$data ,$column, $maxLength, $mask = true) {
	if (!$data) return;
	foreach($data as $key => $value) {
		if ($value[$column]) {
			// 最大 $maxLength でカット
			$data[$key][$column] = cutLongText($value[$column], $maxLength, $mask);
		}
	}
}

/**
 *
 * 配列の特定キーと値のリスト作成
 *
 */
function getKeyList($arr, $key) {
	$keyList = array();
	foreach ($arr as $value) {
		$keyList[] = $value[$key];
	}
	return $keyList;
}

/**
 *
 * 特定文字で分割して配列化
 *
 * wvSplitString($str, '|');   // パイプで分割
 * wvSplitString($str, '');    // $pattern なしなら1文字ずつ分割
 *
 */
function wvSplitString($str, $pattern = null) {
	if (!$pattern) $pattern = '';
	if (!$str) return;
    return preg_split("/{$pattern}/u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 *
 * JSONか判別
 *
 */
function isJson($string){
   return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}

/**
 *
 * 改行コードを統一
 *
 * convertEOL($str, '\n');		// LFで統一 UNIX系（MacOSX以降）
 * convertEOL($str, '\r\n');	// CR+LFで統一 Windows系
 * convertEOL($str, '\r');		// CRで統一 MAC系（MacOS 9まで）
 *
 */
function convertEOL($string, $to = "\n")
{
	return preg_replace("/\r\n|\r|\n/", $to, $string);
}

/**
 *
 * テキスト最適化
 *
 * HTMLからスクレイピングしたテキストを最適化する。
 * 行頭・行末の全角・半角スペース、改行なしスペース、タブ、空文字改行を削除する。
 *
 */
function optimizeText(&$str)
{
	// 改行コードを統一（これ重要！入り混じってる場合が多いかも）
	$str = convertEOL($str);
	// 全体の改行なしスペースを半角に置換（これ重要！入り混じってる場合が多い）
	$str = preg_replace('/\xC2\xA0/', '', $str);
	// 行頭の空白文字（全角スペース、半角スペース、タブ）削除
	$str = preg_replace('/^[ 　	]+/um', '', $str);
	// 行末の空白文字（全角スペース、半角スペース、タブ）削除
	$str = preg_replace('/[ 　	]+$/um', '', $str);
	// 空白行を削除
	$str = preg_replace('/^\n/um', '', $str);
}

/**
 *
 * Kakasi でローマ字変換
 *
 */
function KakasiToRoman($str, &$message = null) {
	// ローマ字変換（全て大文字）
	$com = "echo '{$str}' | nkf -e | /usr/local/bin/kakasi -w | /usr/local/bin/kakasi -Ja -Ha -Ka -Ea | nkf -w";
	exec($com, $output, $returnVar);
	if ($returnVar != 0) {
		$message[] = 'Kakasiでの変換失敗';
	} else {
		// 大文字に変換
		$str = strtoupper($output[0]);
		// 「^」→「 」に置換
		// ハイフンは変換後「^」になるので、スペースに置換する。
		$str = preg_replace('/\^/' , ' ', $str);

		return $str;
	}
}

/**
 *
 * Kakasi でカタカナ変換
 *
 */
function KakasiToKana($str, &$message = null) {
	// ローマ字変換（全て大文字）
	$com = "echo '{$str}' | nkf -e | /usr/local/bin/kakasi -w | /usr/local/bin/kakasi -JK -HK | nkf -w";
	$ret = exec($com, $output, $returnVar);
	if ($returnVar != 0) {
		$message[] = 'Kakasiでの変換失敗';
	} else {
		// 「^」→「 」に置換
		// ハイフンは変換後「^」になるので、スペースに置換する。
		$str = preg_replace('/\^/' , ' ', $output[0]);
		// スペース削除
		$str = deleteSpace($str);
		return $str;
	}
}

/**
 *
 * URLテキストをAタグに置換
 *
 * 参考
 * https://phpjp.com/reg/%E6%96%87%E5%AD%97%E5%88%97%E5%86%85%E3%81%AEURL%E3%81%AB%E3%83%AA%E3%83%B3%E3%82%AF%E3%82%BF%E3%82%B0%E3%82%92%E4%BB%98%E3%81%91%E3%82%8B.php
 * https://www.php.net/manual/ja/function.preg-replace-callback.php
 *
 */
function changeUrlTextToAnker($str) {
	if (preg_match('#http://|https://#', $str)) {
		// リンクURLありならAタグに置換
		// URL短縮なしの場合はこちらでいい。
		// $urlTag = preg_replace("|(https?)(://[-_.!~*\'()a-zA-Z0-9;/?:\@&=+\$,%#]+)|mi", "<a href=\"$1$2\">$1$2</a>", $str);
		// URLを3点リーダーで短縮する場合
		// コールバック関数での置換が必要。
		// Yahoo!知恵袋だと70文字以上なら切詰めしている。（URLは1投稿で5つまで許可）
		$urlTag = preg_replace_callback("|(https?)(://[-_.!~*\'()a-zA-Z0-9;/?:\@&=+\$,%#]+)|mi",
			function ($matches) {
				return '<a href="' . $matches[1].$matches[2] . '" class="help-link">'
					. cutLongText("{$matches[1]}{$matches[2]}", 50) . '</a>';
			},
			$str);
		if (!$urlTag) {
			// 通常ないはず！
			errorLog($str, 'AタグからのURLテキスト抽出失敗');
		}
	} else {
		$urlTag = $str;
	}
	return $urlTag;
}

/**
 *
 * AタグをURLテキストに置換
 *
 * 参考
 * https://qiita.com/ao_love/items/6b5299b06214348c03a1
 *
 */
function changeAnkerToUrlText($str) {
	if (strstr($str, 'href=')) {
		// AタグありならURLのテキストに置換
		$urlText = preg_replace("|<a href=\"(.*?)\".*?>(.*?)</a>|mi", "$1", $str);
		if (!$urlText) {
			// 通常ないはず！
			errorLog($str, 'AタグからのURLテキスト抽出失敗');
		}
	} else {
		$urlText = $str;
	}
	return $urlText;
}

//------------------------------------------------
// 日時処理
//------------------------------------------------
/**
 *
 * YYYY年MM月DD日からYYYYMMDDに変換
 *
 */
function formatToYYYYMMDD($date){
	if (!preg_match("/^[0-9]{4}年[0-9]{1,2}月[0-9]{1,2}日$/", $date)) {
		return null;
	}
	$datetime = DateTime::createFromFormat('Y年m月d日', $date);
	return $datetime->format('Ymd');
}

/**
 *
 * 年齢取得
 *
 * 誕生日から年齢を取得。
 *
 * $birthdate YYYYMMDD、YYYY/MM/DD、YYYY-MM-DD に対応。
 *
 */
function getAgeByBirthday($birthday) {
	$birthday = str_replace('-', '', $birthday);
	$birthday = str_replace('/', '', $birthday);
	$age = floor((date('Ymd') - $birthday)/10000);
    return $age;
}

/**
 *
 * 現在日時取得
 *
 *   YYYY/MM/DD(曜日) HH:MM:SS
 *   YYYY/MM/DD(WEEK) HH:MM:SS
 *
 */
function getCurrentTimeStamp($country = null) {
    if ($country === 'JP') {
        // 2015/11/27(金) 12:38:09
        //     曜日を和暦に変更
        $weekday = array( "日", "月", "火", "水", "木", "金", "土" );
        $str = date("Y/m/d", time()) . '(' . $weekday[date("w")] . ') ' . date("H:i:s", time());
    } else {
        // 2015/11/27 (Fri) 12:38:09
        $str = date("Y/m/d (D) H:i:s", time());
    }
    return $str;
}

/**
 *
 * 	指定した期間内の日付と曜日のリストを取得
 *
 * @param   $intarval		  ：
 * @param   $dateFrom   	  ：取得日数
 *
 * 用例：
 *     getDay('+10');					// 現在から10日後
 *     getDay('-10', '2016-10-10');		// 2016-10-10から10日前
 *
 */
function getDay($intarval, $dateFrom = null) {
	if (!$dateFrom) {
		// 今日を基準
		$dateFrom = date('Y-m-d');
	}
	$timestamp = strtotime("{$dateFrom} {$intarval} day");
	return date('Y-m-d', $timestamp);
}

/**
 *
 * 年リスト取得
 *
 *     西暦、和暦（年号、年）、何年前かを取得
 *     ※大正以前は非対応
 *
 */
function getYearList($backYear, $sort = 'asc') {
	$curYear = date('Y');
	if ($sort === 'asc') {
		// 昇順
		$targetSeireki = $curYear - $backYear;
	} else {
		// 降順
		$targetSeireki = $curYear;
	}
	if ($targetSeireki < 1989) {
		// 昭和：1926～1989年（64年）
		$targetNengou = '昭和';
		$targetWareKi = $targetSeireki - 1925;
	} elseif ($targetSeireki >= 1989) {
		// 平成：1989～年
		$targetNengou = '平成';
		$targetWareKi = $targetSeireki - 1988;
	}
	$yearList = array();
	if ($sort === 'asc') {
		$interval = $backYear;
		for (; $curYear >= $targetSeireki; $targetSeireki++, $targetWareKi++, $interval--) {
			if ($targetSeireki === 1989) {
				$targetNengou = '平成';
				$targetWareKi = 1;
			}
			$yearList[$targetSeireki]['nengou'] = $targetNengou;
			$yearList[$targetSeireki]['wareki'] = $targetWareKi;
			$yearList[$targetSeireki]['interval'] = $interval;
		}
	} else {
		$interval = 0;
		for (; $interval <= $backYear; $targetSeireki--, $targetWareKi--, $interval++) {
			if ($targetSeireki === 1988) {
				$targetNengou = '昭和';
				$targetWareKi = 63;
			}
			$yearList[$targetSeireki]['nengou'] = $targetNengou;
			$yearList[$targetSeireki]['wareki'] = $targetWareKi;
			$yearList[$targetSeireki]['interval'] = $interval;
		}
	}
    return $yearList;
}

/**
 *
 * 特定月から現在月までの月文字を配列で取得
 *
 * 指定月が現在年の場合：
 *     特定月～現在月までを返す。
 * 指定月が現在年より前の場合：
 *     特定月～12月までを返す。
 *
 */
function getMonthToNow($startYm) {
	$curYear = date('Y');
	$curMonth = date('m');
    $startYear = date("Y", strtotime($startYm));
	$startMonth = date("m", strtotime($startYm));
	if($curYear === $startYear) {
		// 今年開始の場合
		$endMonth = $curMonth;
	} else {
		// 前年以前に開始の場合
		$endMonth = '12';
	}
	if ($startMonth <= $endMonth) {
		while (($endMonth -  $startMonth) >= 0) {
			$arrMonth[] = sprintf("%02d", $startMonth++);
		}
	}
	return $arrMonth;
}

/**
 *
 * 指定した年の文字を配列で取得
 *
 * 1950年から現在までの場合は getYearFromTo('1950');
 *
 */
function getYearFromTo($startYear, $endYear = null, $format = 'YY') {
	$endYear = ($endYear ? $endYear : date('Y'));
	if ($startYear <= $endYear) {
		while (($endYear -  $startYear) >= 0) {
			if ($format = 'YY') {
				$arrYear[] = sprintf("%04d", $startYear++);
			} else {
				$arrYear[] = $startYear++;
			}
		}
	}
	return $arrYear;
}

/**
 *
 * 月の数値リスト取得
 *
 */
function getMonthList($format = 'M') {
	if ($format = 'MM') {
		return array('01','02','03','04','05','06','07','08','09','10','11','12');
	} else {
		return array('1','2','3','4','5','6','7','8','9','10','11','12');
	}
}

/**
 * 月リスト取得
 *
 * 現在月～指定ヶ月後までの月リスト取得（YYYY/MM）
 *
 */
function getMonthListByInterval($interval) {
	$ymList = array();
	for ($i = 0; $i < $interval; $i++) {
    	$ymList[] =date("Y/m", strtotime("+{$i} month"));
	}
	return $ymList;
}

/**
 *
 * 日の数値リスト取得
 *
 */
function getDayList($format = 'D') {
	if ($format = 'DD') {
		$dayList = range(1, 31);
		foreach ($dayList as $key => $value) {
			$dayList[$key] = sprintf("%02d", $value);
		}
		return $dayList;
	} else {
		return range(1, 31);
	}
}

/**
 *
 * 年・月・日の数値リスト取得
 *
 * 生年月日の入力フォームなどで利用。
 *
 */
function getYearMonthDayList($yearFrom, $yearTo = null, $format = 'MAEZERO') {
	$ymdList['year'] = getYearFromTo($yearFrom, $yearTo);
	if ($format = 'MAEZERO') {
		$ymdList['month'] = getMonthList('MM');
		$ymdList['date'] = getDayList('DD');
	} else {
		$ymdList['month'] = getMonthList('M');
		$ymdList['date'] = getDayList('D');
	}
	return $ymdList;
}

/**
 *
 * 	月情報取得
 *
 */
function getMonthInfo($ym) {
	$date = "{$ym}01";  // 1日をセット
	$monthInfo = array();
	$monthInfo['year'] = substr($date, 0, 4);
	$monthInfo['month'] = substr($date, 4, 2);
	// 現在月より過去か未来か
	$curMonth = date('Ym');
	$monthInfo['type'] = 'before';
	if (intval($ym) - intval($curMonth) > 0) {
		$monthInfo['type'] = 'after';
	} elseif (intval($ym) - intval($curMonth) === 0) {
		$monthInfo['type'] = 'current';
	}
	$monthInfo['dayCount'] = date("t", strtotime($date));
	$monthInfo['firstDay'] = date("Ymd", strtotime("first day of {$monthInfo['year']}-{$monthInfo['month']}"));
	$monthInfo['lastDay'] = date("Ymd", strtotime("last day of {$monthInfo['year']}-{$monthInfo['month']}"));
	return $monthInfo;
}

/**
 *
 * 	指定した期間内の日付を取得
 *
 * @param   $dayFrom		  ：取得開始日
 * @param   $dayTo   	      ：取得終了日
 * @return  $dayList          ：日付、曜日のリスト
 *
 */
//     [0] => Array
//         (
//             [date] => 2016-08-22
//             [week] => 月
//         )
function getDayListByFromTo($dayFrom, $dayTo) {
	$weekday = array("日", "月", "火", "水", "木", "金", "土");
    // 日付をUNIXタイムスタンプに変換
    $timestampFrom = strtotime($dayFrom);
    $timestampTo = strtotime($dayTo);
    // 何秒離れているかを計算
    $seconddiff = abs($timestampFrom - $timestampTo);
    // 日数に変換
    $intervalDay = $seconddiff / (60 * 60 * 24);
	return getDayListByInterval($dayFrom, $intervalDay);
}

/**
 *
 * 	指定した期間内の日付と曜日のリストを取得
 *
 * @param   $dayFrom		  ：取得開始日
 * @param   $intervalDay   	  ：取得日数
 *
 */
//     [0] => Array
//         (
//             [date] => 2016-08-25
//             [week] => 木
//         )
//
function getDayWeekListByInterval($dayFrom, $intervalDay) {
	$weekday = array("日", "月", "火", "水", "木", "金", "土");
	$dayList = array();
	for($i = 0; $i <= $intervalDay; $i++) {
		$timestamp = strtotime("$dayFrom +{$i} day");
		// 日付：YYYY-MM-DD
	    $dayList[$i]['date'] = date('Y-m-d', $timestamp);
		$dayList[$i]['week'] = $weekday[date("w", $timestamp)];
	}
	return $dayList;
}

/**
 *
 * 	指定した期間内の日付リストを取得
 *
 * @param   $dayFrom		  ：取得開始日
 * @param   $intervalDay   	  ：取得日数
 *
 * 用例
 *     getDayListByInterval(date('Y-m-d'), 30);
 *
 */
//     [0] => 2016-08-25
//     [1] => 2016-08-26
//
function getDayListByInterval($dayFrom, $intervalDay) {
	$dayList = array();
	for($i = 0; $i <= $intervalDay-1; $i++) {
		$timestamp = strtotime("$dayFrom +{$i} day");
		// 日付：YYYY-MM-DD
		$dayList[] = date('Y-m-d', $timestamp);
	}
	return $dayList;
}

/**
 *
 * 指定した期間内の日付を取得
 *
 * 日付をキーにしたリスト形式を返す。
 *
 * @param   $dayFrom		  ：取得開始日
 * @param   $dayTo   	      ：取得終了日
 * @return  $resultList       ：日付をキーにしたリスト
 *     ----------------------------
 *     [2016-08-18] => Array
 *     (
 *         [week] => 木
 *     )
 *     ----------------------------
 *
 */
function getDayKeyListByFromTo($dayFrom, $dayTo) {
    // 日付をUNIXタイムスタンプに変換
    $timestampFrom = strtotime($dayFrom);
    $timestampTo = strtotime($dayTo);
    // 何秒離れているかを計算
    $seconddiff = abs($timestampFrom - $timestampTo);
    // 日数に変換
    $intervalDay = $seconddiff / (60 * 60 * 24);
	return getDayKeyListByInterval($dayFrom, $intervalDay);
}

/**
 *
 * 	指定した期間内の日付を取得
 *
 * 日付をキーにしたリスト形式を返す。
 *
 * @param   $dayFrom		  ：取得開始日
 * @param   $intervalDay   	  ：取得日数
 * @return  $resultList       ：日付をキーにしたリスト
 *     ----------------------------
 *     [2016-08-18] => Array
 *     (
 *         [week] => 木
 *         [type] => before	   ：before／after／today
 *     )
 *     ----------------------------
 *
 * 用例
 *     getDayKeyListByInterval(date('Y-m-d'), 30);
 *
 */
function getDayKeyListByInterval($dayFrom, $intervalDay) {
	// 日付と曜日のリストを取得
	$dayList = getDayWeekListByInterval($dayFrom, $intervalDay);
	// 現在日と比較して、過去・現在・未来かのフラグ
	$curDay = date('Y-m-d');
	$curType = 'before';
	if (strtotime($curDay) < strtotime($dayFrom)) {
		$curType = 'after';
	}
	// 日付をキーにして再配列化
	$resultList = array();
	foreach ($dayList as $key => $value) {
		$resultList[$value['date']]['week'] = $value['week'];
		if ($value['date'] === $curDay) {
			// 現在日と合致する場合
			$resultList[$value['date']]['type'] = 'today';
			$curType = 'after';
		} else {
			$resultList[$value['date']]['type'] = $curType;
		}
	}
	return $resultList;
}

/**
 *
 * 	指定日付の曜日取得
 *
 * 日付をキーにしたリスト形式を返す。
 *
 * @param   $targetDateList   ：日付リスト：YYYY-MM-DD
 * @return  $resultList       ：日付をキーにしたリスト
 *     ----------------------------
 *     [2016-08-18] => Array
 *     (
 *         [week] => 木
 *         [type] => before	   ：before／after／today
 *     )
 *     ----------------------------
 *
 */
function getDayKeyListByDateList($targetDateList) {
	$weekday = array("日", "月", "火", "水", "木", "金", "土");
	$dayList = array();
	// 現在日と比較して、過去・現在・未来かのフラグ
	$curDay = date('Y-m-d');
	$curType = 'before';
	$first = each($targetDateList);   // 最初のキーと値取得
	if (strtotime($curDay) < strtotime($first['value'])) {
		$curType = 'after';
	}
	foreach ($targetDateList as $key => $value) {
		$timestamp = strtotime($value);
		if ($value === $curDay) {
			// 現在日と合致する場合
			$type = 'today';
			$curType = 'after';
		} else {
			$type = $curType;
		}
	    $dayList[] = array(
	    	  'date'  => $value								// 日付：YYYY-MM-DD
	        , 'week'  => $weekday[date("w", $timestamp)]	// 曜日：日～土
	        , 'type'  => $type
	     );
	}
	// 日付をキーにして再配列化
	$resultList = array();
	foreach ($dayList as $key => $value) {
		$resultList[$value['date']]['week'] = $value['week'];
		$resultList[$value['date']]['type'] = $value['type'];
	}
	return $resultList;
}

/**
 *
 * 	指定した年月の日付一覧を取得
 *
 * @param   $ym   			：年月：YYYY-MM
 * @return  $resultList		：日付をキーにしたリスト
 *     ----------------------------
 *     [2016-08-18] => Array
 *     (
 *         [week] => 木
 *         [type] => before ：before／after／today
 *     )
 *     ----------------------------
 *
 */
function getDayListByMonth($ym) {

	// 月情報取得
	$info = getMonthInfo($ym);

	// 配列化
	$dayList = array();
	$weekday = array('日', '月', '火', '水', '木', '金', '土');
	$curDay = date('Y-m-d');
	// 現在日と比較して、過去・現在・未来かのフラグ
	$curType = 'before';
	if (strtotime($curDay) < strtotime($info['firstDay'])) {
		$curType = 'after';
	}
	for ($i = 1; $i <= $info['dayCount']; $i++) {
		$day = sprintf("%02d", $i);
		$date = "{$info['year']}-{$info['month']}-{$day}";
		if ($date === $curDay) {
			// 現在日と合致する場合
			$dayList[$date]['type'] = 'today';
			$curType = 'after';
		} else {
			$dayList[$date]['type'] = $curType;
		}
	    $timestamp = mktime(0, 0, 0, $info['month'], $i, $info['year']);
	    $week = date("w", $timestamp);
		$dayList[$date]['week'] = $weekday[$week];
	}
	return $dayList;
}

/**
 *
 * 	曜日取得
 *
 */
function getWeek($date) {
	$weekday = array( "日", "月", "火", "水", "木", "金", "土" );
	$timestamp = strtotime("$date");
	return $weekday[date("w", $timestamp)];
}

/**
 *
 * 	日付情報取得
 *
 * $dateInfo = getDateInfo();
 * $dateInfo = getDateInfo('2018-10-10');
 *
 */
function getDateInfo($date = null) {
	$date = ($date ? $date : date("Ymd"));
	// 全てのフォーマットをYYYYMMDD形式に変換
	$date = date('Ymd', strtotime($date));
	$dateInfo = array();
	// 年、月、日に分解
	$dateInfo['date'] = $date;
	$dateInfo['dateHyphen'] = date('Y-m-d', strtotime($date));
	$dateInfo['dateSlash'] = date('Y/m/d', strtotime($date));
	$dateInfo['year'] = substr($date, 0, 4);
	$dateInfo['month'] = substr($date, 4, 2);
	$dateInfo['day'] = substr($date, 6, 2);
	$dateInfo['week'] = getWeek($date);
	$dateInfo['firstDay'] = date("Ymd", strtotime("first day of {$dateInfo['year']}-{$dateInfo['month']}"));
	$dateInfo['lastDay'] = date("Ymd", strtotime("last day of {$dateInfo['year']}-{$dateInfo['month']}"));
	return $dateInfo;
}

/**
 *
 * 	日時情報取得
 *
 * $dateInfo = getDateTimeInfo();
 * $dateInfo = getDateTimeInfo('2018-10-10 11:15:15');
 *
 */
function getDateTimeInfo($date = null) {
	$date = ($date ? $date : date("YmdHis"));
	// 全てのフォーマットをYYYYMMDD形式に変換
	$date = date('YmdHis', strtotime($date));
	// 時分秒以外を取得
	$info = getDateInfo($date);
	$info['hour'] = substr($date, 8, 2);
	$info['min'] = substr($date, 10, 2);
	$info['sec'] = substr($date, 12);
	return $info;
}

/**
 *
 * 和暦から西暦取得
 *
 * 明治より前は非対応。
 *
 */
function getSeirekiByWareki($year, $nengou){
	// 元年 → 1年に変更
    if (strval($year) === '元') {
        $year = 1;
    }
    // 和暦から西暦
    if ($nengou === '平成') {
        $seireki = $year + 1988;
    } elseif ($nengou === '昭和') {
        $seireki = $year + 1925;
    } elseif ($nengou === '大正') {
        $seireki = $year + 1911;
    } elseif ($nengou === '明治') {
        $seireki = $year + 1867;
    } else {
    	$seireki = null;
	}
    return $seireki;
}

/**
 *
 * 西暦から和暦取得
 *
 * 1801/02/05（享和元年）より前は非対応
 *
 */
function getWarekiBySeireki($year, $month, $day) {
    if (!checkdate($month, $day, $year) || $year < 1800) {
        return false;
    }
    $date = (int)sprintf('%04d%02d%02d', $year, $month, $day);
    if ($date >= 19890108) {
        $nengou = '平成';
        $jpYear = $year - 1988;
    } elseif ($date >= 19261225) {
        $nengou = '昭和';
        $jpYear = $year - 1925;
    } elseif ($date >= 19120730) {
        $nengou = '大正';
        $jpYear = $year - 1911;
    } elseif ($date >= 18680125) {
        $nengou = '明治';
        $jpYear = $year - 1867;
    } elseif ($date >= 18650407) {
        $nengou = '慶応';
        $jpYear = $year - 1864;
    } elseif ($date >= 18640220) {
        $nengou = '元治';
        $jpYear = $year - 1863;
    } elseif ($date >= 18610219) {
        $nengou = '文久';
        $jpYear = $year - 1860;
    } elseif ($date >= 18600318) {
        $nengou = '万延';
        $jpYear = $year - 1859;
    } elseif ($date >= 18541127) {
        $nengou = '安政';
        $jpYear = $year - 1853;
    } elseif ($date >= 18480228) {
        $nengou = '嘉永';
        $jpYear = $year - 1847;
    } elseif ($date >= 18441202) {
        $nengou = '弘化';
        $jpYear = $year - 1843;
    } elseif ($date >= 18301210) {
        $nengou = '天保';
        $jpYear = $year - 1829;
    } elseif ($date >= 18180422) {
        $nengou = '文政';
        $jpYear = $year - 1817;
    } elseif ($date >= 18040211) {
        $nengou = '文化';
        $jpYear = $year - 1803;
    } elseif ($date >= 18010205) {
        $nengou = '享和';
        $jpYear = $year - 1800;
    } else {
        $nengou = '';
        $jpYear = null;
    }
	// 1年 → 元年に変更
    if ($jpYear == 1) {
        $jpYear = '元';
    }
	$wareki = array(
	          'year'   => $jpYear
	        , 'nengou' => $nengou
	    );
	return $wareki;
}

/**
 *
 * 西暦和暦変換テーブル
 *
 *     ----------------------------------------------------------------------------------------------------
 *     $yearTable = getYearTable();
 *     WvSmarty::$smarty->assign('yearTable',  $yearTable);
 *     ----------------------------------------------------------------------------------------------------
 *     生年月日
 *     <select name="birthday">
 *     	%% assign var=curYear value=$smarty.now|date_format:"%Y" %%
 *     	%% foreach from=$yearTable key=key item=val %%
 *     		%%* 30年前の位置に未設定行を挟む *%%
 *             %% if ($curYear - $key) === 30 && !$data.birthday %%
 *             	<option value="" %% if !$data.birthday == $key %% selected %% /if %%>--</option>
 *             %% /if %%
 *         	<option value="%% $key %%" %%if $data.birthday == $key %% selected %% /if %%>
 *         		%% $key %%/%% $val.nengou %%%% $val.year %%
 *         	</option>
 *     	%% /foreach %%
 *     </select>
 *     ----------------------------------------------------------------------------------------------------
 *
 */
function getYearTable() {
	// ベースになるテーブル
	$yearTable = array(
	      "1931" => array('nengou' => '昭和', 'year' => '6')
	    , "1932" => array('nengou' => '昭和', 'year' => '7')
	    , "1933" => array('nengou' => '昭和', 'year' => '8')
	    , "1934" => array('nengou' => '昭和', 'year' => '9')
	    , "1935" => array('nengou' => '昭和', 'year' => '10')
	    , "1936" => array('nengou' => '昭和', 'year' => '11')
	    , "1937" => array('nengou' => '昭和', 'year' => '12')
	    , "1938" => array('nengou' => '昭和', 'year' => '13')
	    , "1939" => array('nengou' => '昭和', 'year' => '14')
	    , "1940" => array('nengou' => '昭和', 'year' => '15')
	    , "1941" => array('nengou' => '昭和', 'year' => '16')
	    , "1942" => array('nengou' => '昭和', 'year' => '17')
	    , "1943" => array('nengou' => '昭和', 'year' => '18')
	    , "1944" => array('nengou' => '昭和', 'year' => '19')
	    , "1945" => array('nengou' => '昭和', 'year' => '20')
	    , "1946" => array('nengou' => '昭和', 'year' => '21')
	    , "1947" => array('nengou' => '昭和', 'year' => '22')
	    , "1948" => array('nengou' => '昭和', 'year' => '23')
	    , "1949" => array('nengou' => '昭和', 'year' => '24')
	    , "1950" => array('nengou' => '昭和', 'year' => '25')
	    , "1951" => array('nengou' => '昭和', 'year' => '26')
	    , "1952" => array('nengou' => '昭和', 'year' => '27')
	    , "1953" => array('nengou' => '昭和', 'year' => '28')
	    , "1954" => array('nengou' => '昭和', 'year' => '29')
	    , "1955" => array('nengou' => '昭和', 'year' => '30')
	    , "1956" => array('nengou' => '昭和', 'year' => '31')
	    , "1957" => array('nengou' => '昭和', 'year' => '32')
	    , "1958" => array('nengou' => '昭和', 'year' => '33')
	    , "1959" => array('nengou' => '昭和', 'year' => '34')
	    , "1960" => array('nengou' => '昭和', 'year' => '35')
	    , "1961" => array('nengou' => '昭和', 'year' => '36')
	    , "1962" => array('nengou' => '昭和', 'year' => '37')
	    , "1963" => array('nengou' => '昭和', 'year' => '38')
	    , "1964" => array('nengou' => '昭和', 'year' => '39')
	    , "1965" => array('nengou' => '昭和', 'year' => '40')
	    , "1966" => array('nengou' => '昭和', 'year' => '41')
	    , "1967" => array('nengou' => '昭和', 'year' => '42')
	    , "1968" => array('nengou' => '昭和', 'year' => '43')
	    , "1969" => array('nengou' => '昭和', 'year' => '44')
	    , "1970" => array('nengou' => '昭和', 'year' => '45')
	    , "1971" => array('nengou' => '昭和', 'year' => '46')
	    , "1972" => array('nengou' => '昭和', 'year' => '47')
	    , "1973" => array('nengou' => '昭和', 'year' => '48')
	    , "1974" => array('nengou' => '昭和', 'year' => '49')
	    , "1975" => array('nengou' => '昭和', 'year' => '50')
	    , "1976" => array('nengou' => '昭和', 'year' => '51')
	    , "1977" => array('nengou' => '昭和', 'year' => '52')
	    , "1978" => array('nengou' => '昭和', 'year' => '53')
	    , "1979" => array('nengou' => '昭和', 'year' => '54')
	    , "1980" => array('nengou' => '昭和', 'year' => '55')
	    , "1981" => array('nengou' => '昭和', 'year' => '56')
	    , "1982" => array('nengou' => '昭和', 'year' => '57')
	    , "1983" => array('nengou' => '昭和', 'year' => '58')
	    , "1984" => array('nengou' => '昭和', 'year' => '59')
	    , "1985" => array('nengou' => '昭和', 'year' => '60')
	    , "1986" => array('nengou' => '昭和', 'year' => '61')
	    , "1987" => array('nengou' => '昭和', 'year' => '62')
	    , "1988" => array('nengou' => '昭和', 'year' => '63')
	    , "1989" => array('nengou' => '平成', 'year' => '元')
	    , "1990" => array('nengou' => '平成', 'year' => '2')
	    , "1991" => array('nengou' => '平成', 'year' => '3')
	    , "1992" => array('nengou' => '平成', 'year' => '4')
	    , "1993" => array('nengou' => '平成', 'year' => '5')
	    , "1994" => array('nengou' => '平成', 'year' => '6')
	    , "1995" => array('nengou' => '平成', 'year' => '7')
	    , "1996" => array('nengou' => '平成', 'year' => '8')
	    , "1997" => array('nengou' => '平成', 'year' => '9')
	    , "1998" => array('nengou' => '平成', 'year' => '10')
	    , "1999" => array('nengou' => '平成', 'year' => '11')
	    , "2000" => array('nengou' => '平成', 'year' => '12')
	    , "2001" => array('nengou' => '平成', 'year' => '13')
	    , "2002" => array('nengou' => '平成', 'year' => '14')
	    , "2003" => array('nengou' => '平成', 'year' => '15')
	    , "2004" => array('nengou' => '平成', 'year' => '16')
	    , "2005" => array('nengou' => '平成', 'year' => '17')
	    , "2006" => array('nengou' => '平成', 'year' => '18')
	    , "2007" => array('nengou' => '平成', 'year' => '19')
	    , "2008" => array('nengou' => '平成', 'year' => '20')
	    , "2009" => array('nengou' => '平成', 'year' => '21')
	    , "2010" => array('nengou' => '平成', 'year' => '22')
	    , "2011" => array('nengou' => '平成', 'year' => '23')
	    , "2012" => array('nengou' => '平成', 'year' => '24')
	    , "2013" => array('nengou' => '平成', 'year' => '25')
	    , "2014" => array('nengou' => '平成', 'year' => '26')
	    , "2015" => array('nengou' => '平成', 'year' => '27')
	    , "2016" => array('nengou' => '平成', 'year' => '28')
	);

	// 2016年以降を自動で追加
	//     追加年分、過去を削除
	$currentYear = date("Y");
	$intarval = $currentYear - 2016;
	for ($i = 0, $seirekiDel = 1931, $seirekiAdd = 2016, $warekiAdd = 28;
		 $i < $intarval; $i++, $seirekiDel++, $seirekiAdd++, $warekiAdd++) {
		unset($yearTable[$seirekiDel]);
	 	$yearTable[$seirekiAdd] = array('nengou' => '平成', 'year' => $warekiAdd);
	}
    return $yearTable;
}

/**
 *
 * 指定月から現在月までの月文字を配列で取得
 *
 * 指定月が現在年の場合：
 *     特定月～現在月までを返す。
 * 指定月が現在年より前の場合：
 *     特定月～12月までを返す。
 *
 */
function getTargetMonthList($releaseYm, $targetYm) {
    $curYear = date('Y');
    $curMonth = date('m');
    $releaseYear = date("Y", strtotime($releaseYm));
    $releaseMonth = date("m", strtotime($releaseYm));
    $targetYear = date("Y", strtotime($targetYm));
    $targetMonth = date("m", strtotime($targetYm));

	if($curYear === $targetYear) {
		// 指定年が今年の場合
		if ($releaseYear === $targetYear) {
			// 指定年がリリース年の場合
			$workMonth = $releaseMonth;
		} else {
			$workMonth = 1;
		}
		$endMonth = $curMonth;
		while (($endMonth -  $workMonth) >= 0) {
			$arrMonth[] = sprintf("%02d", $workMonth++);
		}
	} else {
		// 指定年が前年以前の場合

		if ($releaseYear === $targetYear) {
			// 指定年がリリース年の場合
			$workMonth = $releaseMonth;
		} else {
			$workMonth = 1;
		}
		$endMonth = 12;
		while (($endMonth -  $workMonth) >= 0) {
			$arrMonth[] = sprintf("%02d", $workMonth++);
		}
	}
	return $arrMonth;
}


/**
 *
 * 日数の差分を取得
 *
 * $startDay 	：計算の起点日、NULL なら今日。
 * $endDay		：起点日に対して未来ならプラス。
 *
 * getDayDiff('2015-06-12', '2015-06-15')   → 3
 * getDayDiff('2015-06-12', '2015-06-10')   → -2
 *
 * @return              ：整数で返す。
 *
 */
function getDayDiff($startDay = null, $endDay) {

	// DateTime に変換
	$startDay = ($startDay ? new DateTime($startDay) : new DateTime());
	$endDay = new DateTime($endDay);

	// $startDay から $endDay までの日数を取得
    $diff = $startDay->diff($endDay);

    return intval($diff->format('%R%a'));
}

/**
 *
 * 日時の差分を秒、分、時間、日に分けて表現
 *
 * 差分の取得自体は DateTime::diff でも可能だが、これだと最大の単位を
 * 指定した表示ができないので作成した。（電源タップ）
 *
 * 例：getFormatDiffTime(329250, '秒');	// 329250秒
 * 例：getFormatDiffTime(329250, '分');	// 5487分30秒
 * 例：getFormatDiffTime(329250, '時');	// 91時間27分30
 * 例：getFormatDiffTime(329250, '日');	// 3日31時間27分30秒
 *
 */
function getFormatDiffTime($second, $head) {
	$result = null;
	$result['sec'] = $second;
	if ($second < 59 || $head === '秒') {
		// 秒まで表示
		$result['show'] = "{$result['sec']}秒";
	} else {
		$result['min'] = intval($second/60);
		$result['sec'] = intval($second%60);
		if ($result['min'] < 59 || $head === '分') {
			// 分まで表示
			$result['show'] = "{$result['min']}分{$result['sec']}秒";
		} else {
			$result['hour'] = intval($result['min']/60);
			$result['min'] = intval($result['min']%60);
			if ($result['hour'] < 24 || $head === '時') {
				// 時間まで表示
				$result['show'] = "{$result['hour']}時間{$result['min']}分{$result['sec']}秒";
			} else {
				// 日まで表示
				$result['day'] = intval($result['hour']/24);
				$result['hour'] = $result['hour']%60;
				$result['show'] = "{$result['day']}日{$result['hour']}時間{$result['min']}分{$result['sec']}秒";
			}
		}
	}
	return $result;
}

/**
 *
 * 経過時間チェック
 *
 * 特定の時間から指定した時間（日数、時間数、分数、秒数など）を過ぎたかチェックする。
 *
 *   $checkTime  	：チェック日時
 *   $interval   	：指定間隔
 *   $unit          ：指定間隔の単位
 *                  ：month
 *                  ：day
 *                  ：week
 *                  ：hour（デフォルト）
 *                  ：minute
 *                  ：second
 *   true			：指定時間経過済み
 *
 * 例：
 *     checkElapsedTime('2018/01/01 0:10', 2)				// 2時間経過済みなら true
 *     checkElapsedTime('2018/01/01 0:10', 20, 'minute')	// 20分経過済みなら true
 *
 */
function checkElapsedTime($checkTime, $interval, $unit = 'hour') {
	if (!$checkTime) return true;
	if (strtotime("$checkTime + $interval {$unit}") < strtotime("now")) {
		return true;
	}
	return false;
}

/**
 *
 * 日時フォーマット（Smarty向け date_format の拡張版）
 *
 * Smarty3 で日時未設定の場合でも「1970/01/01」が表示されないようにする。
 *
 * 例
 * %% $data.insTime|date_format_ex %% 日時あり
 * %% $data.updTime|date_format_ex:false %% 日時なし
 *
 */
function date_format_ex($date, $addTime = true){
	if ($date) {
		if ($addTime) {
			return date('Y/m/d H:i', strtotime($date));
		} else {
			return date('Y/m/d', strtotime($date));
		}
	}
	// 未設定ならNULLを返すのがポイント！
	return null;
}

//------------------------------------------------
// ファイル操作
//------------------------------------------------
/**
 *
 * 画像アップロード
 *
 * アップロード直後のデータチェック
 *
 */
function checkUploadFile($fileData) {

	// データがあるかチェック
	if(!isset($fileData['uploadData'])){
	    showError($fileData, '$_FILES が空です。');
		return false;
	}
	// ファイル名チェック
	//     PCからなら問題ないが、iPhoneに日本語名ファイルをDropbox経由でアップロードしたファイルを
	//     サーバにアップロードすると、日本語が文字化した状態かつ、ファイルサイズゼロでアップロード
	//     されて、コンバートに失敗する。文字化けした場合、$encoding：UTF-8 になる。通常は ASCII
	//     iPhone 自体では文字化してない状態で（FileExplorerで確認）ファイル配置されるみたいだが、
	//     ネットにも情報がなくて原因の解析が難しい。
	$srcFileName = $fileData['uploadData']['name'];
	$srcFileSize = $fileData['uploadData']['size'];
	$encoding = mb_detect_encoding($srcFileName);
	if ($encoding !== 'ASCII' && $srcFileSize === 0){
		showError($encoding, '不正なアップロード検出！文字化け かつ サイズゼロ');
		return false;
	}

	// ファイルサイズチェック
	if ($srcFileSize === 0){
		// 頻発するのでエラーログ停止
		// 画像容量が upload_max_filesize（32MB）を超過すると出るのかも。
		// showError(setLogDetail($fileData, $encoding, $_FILES, $_SERVER['CONTENT_LENGTH']), '空ファイルのアップロード検出！');
		return false;
	}
	return true;
}

/**
 *
 * 画像切り抜き・リサイズ・移動
 *
 */
function imageCropResize($srcPath, $dstPath, $cropInfo, $resizeInfo) {

	// 切り抜き
	//     -auto-orient ：画像を回転
	//	if ($cropInfo) {
	//		// この処理は png だと 2000px 程度でも20秒 ぐらいかかるので不採用。
	//      // 下で -resize と同時にやれば一瞬でできる。
	//		// convert -auto-orient /tmp/phpd3LzT4 -crop 2160x2160+0+840 -strip images/user/DPFgvEQGPnZVT7Duf40A/profIcon.png
	//		if (WV_DEBUG) debug('imageCropResize 切り抜き 開始（png だと時間かかる！）');
	//		$command = "convert -auto-orient {$srcPath} -crop {$cropInfo['width']}x{$cropInfo['height']}+{$cropInfo['x']}+{$cropInfo['y']} -strip {$dstPath}";
	//		exec($command, $output, $returnVar);
	//		if (WV_DEBUG) debug($command, 'imageCropResize 切り抜き');
	//		if ($returnVar != 0) {
	//			showError(func_get_args(), "画像切り抜き失敗：{$dstPath}");
	//			return false;
	//		}
	//	}

	// 正方形かチェック
	// この時点で正方形になってないケースが頻発する。
	// この時点で $cropInfo['width'] ≠ $cropInfo['height'] になることはないようだが、
	// $cropInfo['x'] または $cropInfo['y'] にマイナス値があったりすると正方形に切り抜きできないみたい。
	// 何故 Cropper 側で正方形以外になるよう指定できてるのかは原因不明。
	// とりあえずこの現象が発生したら -extent で強制的に余白を塗り潰すようにした。
	/*
	list($width, $height) = getimagesize($dstPath);
	if($width !== $height){
		// ログ解析中
		errorLog(setLogDetail($_FILES, $cropInfo), '正方形以外の画像検知！');
	}*/

	// 切り抜き・リサイズ
	//     付加情報削除してファイル名変更
	//     余白部分があれば rgb(218, 218, 218) ＝ #DADADA で塗り潰す。
	if ($resizeInfo || $cropInfo) {
		// リサイズのみやる場合 → 切り抜きと同時にやらないと異常に時間がかかる場合があるので不採用
		// $command = "convert {$srcPath} -resize {$resizeInfo['width']}x{$resizeInfo['height']} -background 'rgb(218, 218, 218)' -gravity center -extent {$resizeInfo['width']}x{$resizeInfo['height']} -strip {$dstPath}";
		// 切り抜き・リサイズを同時にやる場合
		$command = "convert -auto-orient {$srcPath} -crop {$cropInfo['width']}x{$cropInfo['height']}+{$cropInfo['x']}+{$cropInfo['y']} -resize {$resizeInfo['width']}x{$resizeInfo['height']} -background 'rgb(218, 218, 218)' -gravity center -extent {$resizeInfo['width']}x{$resizeInfo['height']} -strip {$dstPath}";
		exec($command, $output, $returnVar);
		if (WV_DEBUG) debug($command, 'imageCropResize リサイズ');
		if ($returnVar != 0) {
			// ログ監視中
			$detail['func_get_args'] = func_get_args();
			$detail['dstPath'] = $dstPath;
			$detail['output'] = $output;
			$detail['returnVar'] = $returnVar;
			$detail['command'] = $command;
			showError($detail, "画像リサイズ失敗");
			return false;
		}
	}

	// Exif情報削除
	if (!$resizeInfo && !$cropInfo) {
		$command = "convert {$srcPath} -strip {$dstPath}";
		exec($command, $output, $returnVar);
		if (WV_DEBUG) debug($command, 'imageCropResize Exif情報削除');
		if ($returnVar != 0) {
			showError(func_get_args(), "画像のExif情報削除失敗：{$dstPath}");
			return false;
		}
	}

	// 属性変更
	if (!chmod($dstPath, 0666)) {
		showError(func_get_args(), "chmod 失敗：{$dstPath}");
		return false;
	}

	return true;
}

/**
 *
 * 画像アップロード・リサイズ・サムネイル作成
 *
 * $data		：アップロードデータ情報
 * $dstPath 	：配置先パス
 * $maxSize 	：
 *     img  	：通常画像
 *         width	：横幅
 * 		   height	：高さ
 *         fsize	：ファイルサイズ
 *     thumb：サムネイル
 *         width	：横幅
 * 		   height	：高さ
 *         fsize	：ファイルサイズ
 * $makeThumb	：サムネイル作成する／しない
 *
 */
function uploadFile($data, $dstPath, $maxSize, $makeThumb) {

    //    [uploadData] => Array
    //        (
    //            [name] => Point1.png
    //            [type] => image/png
    //            [tmp_name] => /tmp/php2LuN6p
    //            [error] => 0
    //            [size] => 15753
    //        )
	$tmpPath = $data['tmp_name'];
	$fileName = $data['name'];
	$fileSize = $data['size'];
	$dataType = $data['type'];

	//------------------------------------------------
	// ファイルサイズチェック
	//------------------------------------------------
    if ($fileSize > $maxSize['img']['fsize']) {
		$message = "ファイルサイズオーバー：{$fileSize}";
		return $message;
    }

	//------------------------------------------------
	// PDFファイルのコピー
	//------------------------------------------------
	// PDFファイルアップロードの場合も、同様の処理で通常画像、サムネイルを生成するため
	// $dstPath には画像パスが設定されてくるので、ここで単純コピーしておく。
	if ($dataType === 'application/pdf') {
		if (!copyFile($tmpPath, replaceExtName($dstPath, 'pdf'))) {
			$message = "PDFファイルコピー失敗：{$dstPath}";
			return $message;
		}
	}

	//------------------------------------------------
	// リサイズ
	//------------------------------------------------
    // 縦横のピクセルサイズ取得
	$result = fileResize($tmpPath, $dstPath, $maxSize['img']['width'], $maxSize['img']['height']);
	if (!$result) {
		$message = "画像リサイズ、移動失敗：{$dstPath}";
		return $message;
	}

	//------------------------------------------------
	// サムネイル生成
	//------------------------------------------------
    if ($makeThumb) {
		// サムネイルのファイルパス取得
        $pathParts = pathinfo($dstPath);
		$thumbPass = "{$pathParts['dirname']}/{$pathParts['filename']}_s.{$pathParts['extension']}";
		$result = fileResize($dstPath, $thumbPass, $maxSize['thumb']['width'], $maxSize['thumb']['height']);
		if (!$result) {
			$message = "サムネイル生成失敗：{$dstPath}";
			return $message;
		}
    }
}

/**
 *
 * ファイルコピー
 *
 */
function copyFile($srcPath, $dstPath) {
	 $command = "cp -p {$srcPath} {$dstPath}";
    exec($command, $output, $returnVar);
    if ($returnVar != 0) {
        showError($srcPath, 'エラー：コピー失敗');
		return false;
    }
    if (!chmod($dstPath, 0666)) {
    	showError($srcPath, 'エラー：chmod 失敗');
		return false;
	}
	return true;
}

/**
 *
 * ファイルリサイズと移動
 *
 */
function fileResize($srcPath, $dstPath, $maxWidth, $maxHeight) {
	// PDFの場合、変換するページ番号を指定
	$srcExt = getExtName($srcPath);
	$pageNo = $density = null;
	if ($srcExt === 'pdf') {
		// PDFの場合
		//     リサイズするとボケるので、1ページ目だけを変換する。
		$pageNo = '[0]';				// 1ページ目のみ出力
		$density = ' -density 600 '; 	// 600dpiの解像度指定
	}
	list($width, $height) = getimagesize($srcPath);
	if ($width > $maxWidth || $height > $maxHeight) {
		// 最大サイズオーバーの場合
		//     縦横比を保ったまま、リサイズして配置先に移動。
		$command = "convert {$srcPath}{$pageNo} {$density} -resize {$maxWidth}x{$maxHeight} -strip {$dstPath}";
		exec($command, $output, $returnVar);
    } else {
    	// サイズオーバーしていない場合
    	//     Exif情報のみ削除して配置先に移動。
    	$command = "convert {$srcPath}{$pageNo} {$density} -strip {$dstPath}";
		exec($command, $output, $returnVar);
    }
    if ($returnVar != 0) {
		$detail['movepath'] = $srcPath . '→' . $dstPath;
		$detail['FILES'] = $_FILES;
		showError($detail, 'エラー：convert 失敗');
		// 一時ディレクトリから自動削除されるので移動しておく。
		$command = "mv {$srcPath} {$dstPath}";
		exec($command, $output, $returnVar);
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
 * 連番ファイル名の振り直し
 *
 * 同じディレクトリに配置された連番ファイル名（1.jpg, 2.jpg ... など）
 * に歯抜けがあれば振り直しする。
 *
 * ※ファイル名が数字のみで構成されている前提条件あり。
 *
 */
function resetFileNo($imgDir) {
	// 「-v」で数字の昇順に表示
	// https://takuya-1st.hatenablog.jp/entry/2015/05/11/103051
	$command = "ls -v {$imgDir} | grep .jpg";
	exec($command, $output, $returnVar);
	if ($returnVar != 0) {
		showError($detail, "コマンド：{$command} 失敗");
		return false;
	}
	$imgNo = 1;
	foreach ($output as $key => $value) {
		// ファイル名取得
		$fileName = getFileName($value);
		if (!preg_match("/^[0-9]+$/", $fileName)) {
			showError($fileName, "施設ファイル名に半角数字以外を検知！");
			return false;
		}
		if (intval($fileName) !== $imgNo) {
			// 連番になってなければファイル名変更
			if (!rename("{$imgDir}/{$fileName}.jpg", "{$imgDir}/{$imgNo}.jpg")) {
				showError($fileName, "ファイル名変更失敗！");
				return false;
			} else {
				$message = "ファイル名変更成功：{$imgDir}/{$fileName}.jpg → {$imgDir}/{$imgNo}.jpg";
				if (WV_DEBUG) trace($message);
			}
		}
		$imgNo++;
	}
	return true;
}

/**
 * INIファイルの値更新
 *
 * $iniFile      ：対象ファイル
 * $section      ：変更したいセクション
 * $targetKey    ：変更したいキー
 * $value        ：変更したい値
 *
 * 戻り値		：ファイルに書き込まれたバイト数／false
 *
 * ＜INIファイル例＞
 * ----------------------------------------------------------------
 * [../User/Tmp/error.log]              // 監視対象ファイル名
 * time = "2015/11/11 15:18:28"         // 前回処理日時
 * fileEnd = "500"                      // ファイルエンドのバイト数
 * ----------------------------------------------------------------
 *
 * 用例
 *     $result = updateIniFile('../Config/BatchErrorLogCheck.ini', '../User/Tmp/error.log', 'time', date('Y-m-d H:i:s'));
 *     $result = updateIniFile('../Config/BatchErrorLogCheck.ini', '../Admin/Tmp/error.log', 'fileEnd', 100);
 */
function updateIniFile($iniFile, $section, $targetKey, $value) {
    // 読込対象ファイル
    $handle = fopen($iniFile, "r");

    // ファイルを1行ずつ出力
    $targetSection = false;
    $updateText = array();

    if($handle){
		while ($line = fgets($handle)) {
            if (preg_match("/^;/", $line)) {
                // コメント行はそのまま
                //     行の先頭以外から始まるコメントは削除されるので注意。
                $updateText[] = $line;
                continue;
            }
            if (preg_match("/^\[([^\]]+)\]/", $line, $matches)) {
                // ']'以外の連続文字
                // セクションの判別
                if ($matches[1] === $section) {
                    $targetSection = true;
                } else {
                    $targetSection = false;
                }
            }
            // 値の更新
            if ($targetSection === true) {
                if (preg_match("/$targetKey = (.*)/", $line, $matches)) {
                    $updateText[] = $targetKey . ' = "' .  $value . '"' ."\n";
                    continue;
                }
            }
            $updateText[] = $line;
        }
    } else {
    	errorLog($iniFile, 'ファイルオープン失敗');
	}
    fclose($handle);

    // ファイル更新
	return file_put_contents($iniFile, $updateText);
}

/**
 *
 * 画像パス追加
 *
 * フレームワーク共通のルールとして、
 * DBに画像パスを持たない構成にする場合、
 * 以下のフォーマットで画像パスを設定する。
 *
 * img => /huga/hoge/＜ID＞.jpg
 * img1 => /huga/hoge/＜ID＞-1.jpg
 * img2 => /huga/hoge/＜ID＞-2.jpg
 *     ：
 * thumb => /huga/hoge/＜ID＞_s.jpg
 * thumb1 => /huga/hoge/＜ID＞-1_s.jpg
 * thumb2 => /huga/hoge/＜ID＞-2_s.jpg
 *     ：
 *
 * @param    $data     		：画像パス設定するデータ本体
 * @param    $targetPath    ：メイン画像パス
 * @param    $idx     		：メイン画像パス設定ON／OFF
 * @param    $setThumb     	：メイン画像サムネイルパス設定ON／OFF
 * @param    $setNoImage    ：代替画像設定ON／OFF
 * @return                  ：なし
 *
 *
 */
function setImgPath(&$data, $targetPath, $idx, $setThumb = true, $setNoImage = true) {
	// 画像
	if (file_exists($targetPath)) {
		$data["img{$idx}"] = $targetPath;
	} elseif ($setNoImage) {
		$data["img{$idx}"] = 'images/Common/Sample/sample_white_400.png';
	}
	// サムネイル
	if ($setThumb) {
		$targetPathThumb = preg_replace("/.jpg$/", "_s.jpg", $targetPath);
		if (file_exists($targetPathThumb)) {
			$data["thumb{$idx}"] = $targetPathThumb;
		} elseif ($setNoImage) {
			$data["thumb{$idx}"] = 'images/Common/Sample/sample_white_100.png';
		}
	}
}

//------------------------------------------------
// その他ユーティリティー
//------------------------------------------------
/**
 *
 * ユーザーエージェントによるデバイス情報取得
 *
 *     @return    $device     ：デバイス種類
 *           $device['ua']       ：HTTP_USER_AGENT全体（空白ならUA取得失敗）
 *           $device['os']       ：Windows, Android, iOS（空白なら異常）
 *           $device['type']     ：Mobile, Tablet, PC（初期値PC）
 *           $device['products'] ：iPhone, iPod, iPod
 *           $device['browser']  ：Chrome, Firefox, IE, Safari（Safari は付いているブラウザが多いので識別しても意味なさそう。）
 *
 *               ＜type-os-products-browser＞
 *               Mobile
 *                   Android--
 *                   iOS-iPhone-
 *                   iOS-iPod-
 *                   Windows-Windows Phone OS-
 *               Tablet
 *                   Android--
 *                   iOS-iPad-
 *               PC
 *                   Windows--Chrome
 *                   Windows--Firefox
 *                   Windows--IE
 *                   Windows--
 *                   Mac OS--Chrome
 *                   Mac OS--Safari
 *                   Mac OS--
 *
 *               ＜例＞
 *                [type] => Mobile
 *                [ua] => Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X)
 *                            AppleWebKit/538.39.2 (KHTML, like Gecko) Version/7.0 Mobile/12A4297e Safari/9537.53
 *                [os] => iOS
 *                [products] => iPhone
 *
 */
function getDevice($availVersion = null) {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return;
    $device = array();
    $device['type'] = 'PC';
    $device['ua'] = $_SERVER['HTTP_USER_AGENT'];
    $device['available'] = true;  // ブラックリスト方式
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
        // Android 系
        $device['os'] = 'Android';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile')) {
            $device['type'] = 'Mobile';
        } else {
            $device['type'] = 'Tablet';
        }
        // OSバージョン取得とOS機種の有効性チェック
        $result = validateDevice($device, $availVersion);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac OS')) {
        // iOS 系
        $device['os'] = 'iOS';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') && !strpos($_SERVER['HTTP_USER_AGENT'], 'Macintosh')) {
			// Safari でデスクトップ用サイトを表示した場合
			// https://qiita.com/niwasawa/items/a2b947b24121b30d5eb6
			// Mobile ありだが iPhone なし、like Mac OS → Intel Mac OS となるので注意！
			// Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.1 Mobile/15E148 Safari/604.1
			$device['type'] = 'Mobile';
            $device['products'] = 'iPhone';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iPod')) {
            // iPod の UA には iPhone も含まれる。
            $device['type'] = 'Mobile';
            $device['products'] = 'iPod';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            $device['type'] = 'Tablet';
            $device['products'] = 'iPad';
        } else {
            // PC
            $device['os'] = 'Mac OS';
            $device['type'] = 'PC';
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
                $device['browser'] = 'Chrome';      // Chrome にも Safari は含まれる。
            } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
                $device['browser'] = 'Safari';
           }
        }
        // OSバージョン取得と機種の有効性チェック
        $result = validateDevice($device, $availVersion);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows')) {
        // Windows 系
        $device['os'] = 'Windows';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Phone')) {
            $device['products'] = 'Windows Phone';
        }
    }

    // ブラウザ種類
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
        $device['browser'] = 'Safari';
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
        $device['browser'] = 'Chrome';
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
        $device['browser'] = 'Firefox';
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $device['browser'] = 'IE';      // Internet Explorer 10以前
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident')) {
        $device['browser'] = 'IE';      // Internet Explorer 11以降
    } else {
        $device['browser'] = 'unknown';
    }
    //} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/')) {
    //} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle')) {
    //} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry')) {
    //} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini')) {
    //} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi')) {

    return $device;
}

/**
 *
 * 対応端末判定
 *
 * OSバージョン取得とOS機種の有効性チェック
 *
 * @return      ：OSバージョンと有効／無効情報
 */
// FIXME：内部処理の checkVersion は各デバイス毎のクラス（UserIos, UserAndroid ど）でも重複して呼ばれているので改修が必要。
function validateDevice(&$device, $availVersion) {
    if ($device['os'] === 'Android') {
    	// 例
    	// Mozilla/5.0 (Linux; U; Android 4.1.2; ja-jp; SonySOL21 Build/9.1.D.0.401) AppleWebKit
    	// Mozilla/5.0 (Linux; Android 5.1.1) AppleWebKit
		// HUAWEI TIT-TL00_CMCC/B200 Linux/3.10.65 Android/5.1.0 Release/05.28.2015
		// preg_match('/Android( |\/)(\d+\.\d+\.\d+|\d+\.\d+|\d+)(;|\| )/', $device['ua'], $match);
        preg_match('/Android( |\/)(\d+\.\d+\.\d+|\d+\.\d+|\d+)/', $device['ua'], $match);
		if ($match) {
			$device['osVersion'] = $match[2];
	        // OSバージョンチェック
			if ($availVersion) {
				$device['available'] = checkVersion($device['osVersion'], $availVersion['android']);
			}
		} else {
			if ($device['type'] !== 'Tablet' && !strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'zxing')) {
				// Tablet の場合以下のようなパターンありなので除外
				// Opera/9.80 (Android; Opera Mini/37.0.2254/118.144; U; ja) Presto/2.12.423 Version/12.16
				// QRコード読み取りライブラリは除外
				// 例：ZXing (Android)
				errorLog($device, 'AndroidのOSバージョンが取得できません。');
			}
		}
		/*
		// 特定の非対応機種を除外
		if (preg_match( '/Android.* (003P|SC-01D) Build/', $device['ua'])) {
			// SC-01D（GALAXY Tab）のみ除外
			$device['available'] = false;
		}
		*/
    } elseif ($device['os'] === 'iOS')  {
        if ($device['type'] != 'PC') {
			preg_match( '/OS (\d+_\d+_\d+|\d+_\d+|\d+) like Mac OS/', $device['ua'], $match);
			if ($match) {
	            // フォーマット N_N[_N] → N.N[.N]に変更
	            $device['osVersion'] = implode('.', explode('_', $match[1]));
	            // OSバージョンチェック
				if ($availVersion) {
					$device['available'] = checkVersion($device['osVersion'], $availVersion['ios']);
				}
			} else {
				errorLog(setLogDetail($device), 'iOSのOSバージョンが取得できません。');
			}
        }
    }
    return $device;
}

/**
 *
 * バージョンチェック
 *
 * OS、アプリなど全てのバージョンチェックに共通で使える。
 *
 * $version      ：チェック対象のバージョン。フォーマット：N[.N[.N]]
 * $availVersion ：対応バージョン。フォーマット：N[.N[.N]]
 * ※対応バージョンの3桁目のマイナーバージョンは設定したとしても無視されるので注意。
 * ※アプリのバージョンコードの場合、以下を比較する。
 *    Android：versionCode（defaultConfig.versionCode）
 *    iOS    ：versionCode（CFBundleVersion、ビルドバージョン）
 *
 */
function checkVersion($version, $availVersion) {

	if (!$version) {
		// 取得できなかった場合は対応端末扱い。（ブラックリスト方式）
		errorLog($version, 'アクセス端末のバージョンが取得できません。');
		return true;
	}
	if (!$availVersion) {
		errorLog($availVersion, 'INIファイルの対応バージョンが取得できません。');
		return true;
	}

	// メジャー、マイナー、ビルドバージョンに分割（iOS例 9.3.4）
	$versionList = explode('.', $version);
	//$versionList[0] = ($versionList ? $version : $versionList[0]);
	$availVersionList = explode('.', $availVersion);
	//$availVersionList[0] = ($availVersionList ? $availVersion : $availVersionList[0]);
	foreach ($versionList as $key => $value) {
		if (!isset($availVersionList[$key])) {
			// アクセス端末バージョンが「5.0.2」で利用可能バージョンが「5」などの場合に通る。
			// INIファイル未記入か「N[.N[.N]]」形式でない場合でも通るがそれはありえない前提。
			break;
		}
		if ($value < $availVersionList[$key]) {
			// 利用可能バージョンより低い場合
			$detail['端末のバージョン'] = $version;
			$detail['利用可能バージョン'] = $availVersion;
			if (WV_DEBUG) debug($detail, '利用可能バージョンより低い端末からのアクセス発生');
			return false;
		} elseif ($value > $availVersionList[$key]) {
			// 利用可能バージョンより高い場合
			break;
		} else {
			// 利用可能バージョンと同じ場合
			// 続けてマイナーバージョンのチェックへ
			continue;
		}
	}
    return true;
}

/**
 *
 * 画面サイズ設定取得
 *
 *   以下の3要素より確定
 *     $paramScreen				：JSからのリダイレクトパラメータ
 *     $deviceType				：デバイスタイプ
 *     $_SESSION['screenSize'] 	：セッション値
 *
 */
function getScreenSize($paramScreen, $deviceType) {
	if (!isset($_SESSION['screenSize'])) {
		// セッション値なしの場合
		if ($paramScreen) {
			// 画面サイズ変更パラメータありの場合
			//     閾値768pxで切替
			//     normal：PC用
			//     small：SP用
			$_SESSION['screenSize'] = $paramScreen;
		} else {
			// 画面サイズ変更パラメータなしの場合（初回アクセスの場合）
			//     デバイスタイプに応じて初期値を設定
			if ($deviceType === 'Mobile') {
				$_SESSION['screenSize'] = 'small';
			} else {
				$_SESSION['screenSize'] = 'normal';
			}
		}
	} else {
		// 画面サイズ切替り時（通常発生しない遷移）
		if ($paramScreen && ($_SESSION['screenSize'] !== $paramScreen)) {
			$_SESSION['screenSize'] = $paramScreen;
		}
	}
	return $_SESSION['screenSize'];
}

/**
 *
 * 公開ステータス取得
 *
 * @param    $openTime       ：公開開始時間（YYYY-MM-DD hh:mm:ss）
 * @param    $closeTime     	：公開終了時間（YYYY-MM-DD hh:mm:ss）
 * @return                   ：ステータス（公開中、公開前、公開終了、未設定）
 *
 */
function getOpenStatus($openTime, $closeTime) {

	$openStatus = '';

	$curTime = strtotime("now");
	$openTime = strtotime($openTime);
	$closeTime = strtotime($closeTime);

	// 公開状態設定
	//     通常ユーザー画面では公開中以外取得しないので不要のはず。
	if ($openTime) {
		// 開始時間あり
		if ($openTime < $curTime && ($closeTime > $curTime || !$closeTime)) {
			// 開始時間が現在より過去 && 終了時間が現在より未来または未設定
			$openStatus = '公開中';
		} elseif ($openTime > $curTime && ($closeTime > $curTime || !$closeTime)) {
			// 開始時間が現在より未来 && 終了時間が現在より未来または未設定
			$openStatus =  '公開前';
		} elseif ($closeTime < $curTime) {
			// 終了時間が現在より過去
			$openStatus = '公開終了';
		}
	} else {
		// 開始時間なし
		if ($closeTime && $closeTime < $curTime) {
			// 終了時間のみありで過ぎている場合
			$openStatus = '公開終了';
		}
	}
	return $openStatus;
}

/**
 *
 * 新着ステータス取得
 *
 */
function getNewStatus($openTime, $newLimitTime) {
	// 新着フラグ設定
	if ((strtotime("now") - $openTime) < $newLimitTime) {
		// 新着表示期間内の場合
		$newStatus = true;
	} else {
		$newStatus = false;
	}
	return $newStatus;
}

/**
 *
 * 緯度経度を取得
 *
 * GoogleMapsAPI に住所を渡して緯度経度を取得する。
 *
 */
function getGpsFromAddress($address){
    $res = array();
    $req = 'http://maps.google.com/maps/api/geocode/xml';
    $req .= '?address='.urlencode($address);
    $req .= '&sensor=false';
    $xml = simplexml_load_file($req);
    if (!$xml) {
        showError('GoogleMapsAPI によるGPS情報が取得できません。');
       return;
    }
    if ($xml->status == 'OK') {
        $location = $xml->result->geometry->location;
        $res['lat'] = (string)$location->lat[0];
        $res['lng'] = (string)$location->lng[0];
    }
    return $res;
}

/**
 *
 * 税込価格取得
 *
 * 逆の税抜価格取得は危険そうなので作らない。
 *
 */
function getPriceZeikomi($price){
    if (!defined('ETAX')) {
       showError('ETAX が未定義です。');
    }
	return floor($price*ETAX);
}

/**
 *
 * コマンドライン引数の加工
 *
 * クエリ同様に扱えるように加工する。
 *
 * 用例：
 *    php test.php "param1=aaa" "param2=bbb"
 *    →  $parameter = formatCommadLineArgv($argv);
 *        pring $parameter['param1'];       // aaa
 *        pring $parameter['param2'];       // bbb
 *
 */
function formatCommadLineArgv($argv) {
    $result = array();
    foreach ($argv as $key => $value) {
        if ($key == 0) {
            continue;    // ファイル名はスキップ
        }
        list($param, $value) = explode('=', $value);
        $result[$param] = $value;
    }
    return $result;
}

/**
 *
 * Smarty初期設定
 *
 */
function setSmarty(&$smarty) {	
	$smarty->plugins_dir[] = WVFW_ROOT . '/Lib/Smarty/plugins';         // Smarty プラグインディレクトリ
	$smarty->template_dir = '../Template/';                             // テンプレートディレクトリ
	$smarty->compile_dir = '../Tmp/Template_c';                         // コンパイルディレクトリ
	$smarty->cache_dir = '../Tmp/Cache';                                // キャッシュディレクトリ
	$smarty->left_delimiter = '%%';                                     // 左デリミタ
	$smarty->right_delimiter = '%%';                                    // 右デリミタ
}

/**
 *
 * クレジットカードのブランド取得
 *
 * クレジットカード番号の最初の6桁は「発行者識別番号（IIN：Issuer Identifier Number）」と呼ばれ、
 * 国際ブランドやカード発行会社によって数字が割り当てられている。
 * --------------------------------------------------
 * VISA   	：最初の1桁が「4」
 * MASTER 	：最初の1桁が「5」
 * AMEX   	：最初の2桁が「34」または「37」
 * DINERS 	：最初の2桁が「36」
 * JCB    	：最初の2桁が「35」
 * --------------------------------------------------
 * ※ココハのGMO決済でのカードブランド識別処理で対応した。
 *
 */
function getCardBrand($cardNo) {
	if (preg_match('/^4/', $cardNo)) {
		return 'VISA';
	} elseif (preg_match('/^5/', $cardNo)) {
		return 'MASTER';
	} elseif (preg_match('/^34/', $cardNo) || preg_match('/^37/', $cardNo)) {
		return 'AMEX';
	} elseif (preg_match('/^36/', $cardNo)) {
		return 'DINERS';
	} elseif (preg_match('/^35/', $cardNo)) {
		return 'JCB';
	} else {
		return 'NONE';
	}
}

















