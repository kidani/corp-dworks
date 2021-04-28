<?php

/**
 * 
 * 日次チェック
 * 
 * クーロンで実行
 * 
 * cd /var/www/html/Social/Hoikuen/Dev/Site/Admin/Web;
 * /usr/bin/php index.php p=Batch/CheckDaily
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Admin/Config/BatchConfig.php');
require_once (WVFW_ROOT . '/Core/Sitemap.php');

// 各処理のON／OFF
$makeDailyReport 	= array('title' => '日次レポート作成'			, 'active' => true);
$makeSiteMap 		= array('title' => 'サイトマップXML作成'		, 'active' => true);
$resultEvent = array();

////------------------------------------------------
//// 日次レポート作成
////------------------------------------------------
//if ($makeDailyReport['active']) {
//	$domein = DOMAIN;
//	$checkDate = date("Ymd", strtotime("-1 day"));				// 例：20190804
//	// $checkDate = '20190615';		// テスト★
//	$dateInfo = getDateInfo($checkDate);
//
//	// PV数（アクセスログから抽出）
//	$cntPv = null;
//	$checkDateUnix = date("d/M/Y", strtotime($checkDate));		// 例：04/Aug/2019
//	$command = "sudo sh -c 'cat /var/log/httpd/{$domein}_access_log* | grep {$checkDateUnix} | wc'";
//	// $command = "sudo sh -c 'cat /var/log/httpd/{$domein}_access_log* | grep Aug/2019 | wc'";		// テスト★
//	// echo "<pre>";print_r ($command);echo "</pre>";exit;
//	$result = exec($command, $output, $returnVar);
//	if ($returnVar !== 0) {
//		errorLog(setLogDetail($command, $output, $returnVar, $result), 'アクセスログからのPV数抽出失敗');
//	} else {
//		// 前後のスペース削除
//		$wcList = explode(' ', formatSpace($output[0]));
//		$cntPv = $wcList[0];
//	}
//	if (!$cntPv) {
//		errorLog($command, 'アクセスログからのPV数抽出失敗');
//	}
//
//	// DAU取得（現状未対応）
//	// 現状、会員以外のアクセス、既存会員がログアウト中のアクセスなどを取得するには別の仕組みが必要。
//	// 既存会員のDAUを取得するにしても、Users の別カラムにアクセスフラグを追加したりする必要あり。
//	// $report = new Report();
//	// $cntDau = $report->getDauCount($checkDate);
//
//	// 日次レポート取得
//	$report = new Report();
//	$dataReport = $report->getUserDailyReport($dateInfo['dateHyphen']);
//	// NULLに0をセット
//	foreach ($dataReport as $key => $value) {
//		if (!$value && $value !== 0) {
//			$value = 0;
//		}
//		$dataReport[$key] = $value;
//	}
//	// 日次レポート更新
//	$paramUpsert = array(
//		'reportDate'    => array('value' => $dateInfo['dateHyphen']         , 'type' => PDO::PARAM_STR),
//		'insTime'       => array('value' => date('Y-m-d H:i:s')             , 'type' => 'datetime'),
//		'pv'            => array('value' => $cntPv                          , 'type' => PDO::PARAM_INT),
//		'install'       => array('value' => $dataReport['insCount']         , 'type' => PDO::PARAM_INT),
//		'regist'        => array('value' => $dataReport['addCount']         , 'type' => PDO::PARAM_INT),
//		'resign'        => array('value' => $dataReport['delCount']         , 'type' => PDO::PARAM_INT),
//		'review'        => array('value' => $dataReport['reviewCount']      , 'type' => PDO::PARAM_INT),
//		'question'      => array('value' => $dataReport['questionCount']    , 'type' => PDO::PARAM_INT),
//		'answer'        => array('value' => $dataReport['answerCount']      , 'type' => PDO::PARAM_INT),
//	);
//	if (!Db::pdoUpsert('Report', $paramUpsert)) {
//		showError('Report 追加失敗');
//		return;
//	}
//
//	// メール送信（宛先：システム担当）
//	$mailBody = null;
//	$lblReport = array(
//		'reportDate'    => '対象日',
//		'pv'            => 'PV数',
//		'install'       => 'インストール数',
//		'regist'        => '入会数',
//		'resign'        => '退会数',
//		'review'        => '口コミ登録数',
//		'question'      => '質問登録数',
//		'answer'        => '回答登録数',
//	);
//	foreach ($paramUpsert as $key => $value) {
//		if (isset($lblReport[$key])) {
//			$mailBody .= "{$lblReport[$key]}：{$value['value']}\n";
//		}
//	}
//	$resultEvent[] = $makeDailyReport['title'];
//}

//------------------------------------------------
// DBデータのローテーション
//------------------------------------------------
if ($rotateDb['active']) {
	//------------------------------------------------
	// BackUrl のローテーション
	//------------------------------------------------
	// 3日前以前のデータは削除（15日に実行 → 13日0時より前のデータは削除）
	$fromDate = date("Y-m-d", strtotime("-2 day"));
	$dbh = Db::connect();
	try {
		$sql = "DELETE FROM BackUrl WHERE updTime < '{$fromDate}' ";
		$sth = $dbh->prepare($sql);
		$result = $sth->execute();
	} catch (PDOException $e) {
		errorLog($e, "BackUrl ローテーション失敗");
	}
	//------------------------------------------------
	// CacheUser のローテーション
	//------------------------------------------------
	// 60日前以前のデータは削除
	$fromDate = date("Y-m-d", strtotime("-60 day"));
	$dbh = Db::connect();
	try {
		$sql = "DELETE FROM CacheUser WHERE updTime < '{$fromDate}' ";
		$sth = $dbh->prepare($sql);
		$result = $sth->execute();
	} catch (PDOException $e) {
		errorLog($e, "CacheUser ローテーション失敗");
	}
	$resultEvent[] = $rotateDb['title'];
}

//	//------------------------------------------------
//	// サイトマップ用XML作成
//	//------------------------------------------------
//	// 親ファイル：sitemap.xml
//	//     lastmod に下記子ファイルそれぞれの更新日を記載。
//	// 子ファイル：自動更新
//	//     sitemap/school.xml
//	//         loc に保育施設詳細URLを記載。
//	//         lastmod に施設の最終の口コミの更新日時（なければ施設の公開開始日時）を記載。
//	//		sitemap/news.xml
//	//         lastmod にニュースの最終更新日時を記載。
//	//		sitemap/columns.xml
//	//         lastmod にコラムの最終更新日時を記載。
//	// 子ファイル：手動更新
//	//		sitemap/static.xml
//	//		sitemap/school_static.xml
//
//	if ($makeSiteMap['active']) {
//
//		if (WV_DEBUG) trace("サイトマップ用XML作成開始");
//
//		// DEPRECATED、NOTICE を除外（XML_Serializer が deprecated なので）
//		error_reporting(E_ALL & ~ E_DEPRECATED & ~ E_USER_DEPRECATED & ~ E_NOTICE);
//
//		$sitemap = new Sitemap();
//		$sitemapChildList = array(
//			'school.xml',
//			'columns.xml',
//			'news.xml',
//			'static.xml',
//			'school_static.xml',
//		);
//
//		// school.xml 作成
//		$school = new School();
//		$xmlPath = WVFW_ROOT . '/Site/User/Web/sitemap/school.xml';
//		$rdf = $school->getRdf($xmlPath);
//
//		if ($rdf) {
//			$sitemap->make($xmlPath, $rdf);
//		}
//
//		// columns.xml 作成
//		$columns = new Columns();
//		$xmlPath = WVFW_ROOT . '/Site/User/Web/sitemap/columns.xml';
//		$rdf = $columns->getRdf($xmlPath);
//		if ($rdf) {
//			$sitemap->make($xmlPath, $rdf);
//		}
//
//		// news.xml 作成
//		$news = new News();
//		$xmlPath = WVFW_ROOT . '/Site/User/Web/sitemap/news.xml';
//		$rdf = $news->getRdf($xmlPath);
//		if ($rdf) {
//			$sitemap->make($xmlPath, $rdf);
//		}
//
//		// sitemap.xml 作成（ルートサイトマップ）
//		$xmlPath = WVFW_ROOT . '/Site/User/Web/sitemap.xml';
//		$rdf = $sitemap->getRdf($sitemapChildList);
//		if ($rdf) {
//			$sitemap->make($xmlPath, $rdf, 'sitemapindex');
//		}
//
//		// サイトマップ更新通知
//		// 自動通知は SearchConsoleAPI が使えそう。
//		// https://blog.hiroyuki90.com/articles/laravel-search-console-api-submit/
//
//		$resultEvent[] = $makeSiteMap['title'];
//	}
//
//	// イベントログ追加
//	$resultEventStr = implode('、', $resultEvent);
//	eventLog($resultEventStr, "CheckDaily 完了", BATCH_EVENT_LOG_FILE);
