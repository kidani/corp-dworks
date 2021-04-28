<?php

/**
 *
 * 入退会レポート（日別）
 *
 * @author      kidani@wd-valley.com
 *
 */

// 現在値保持
$curParam = $param;

//------------------------------------------------
// 検索条件
//------------------------------------------------
/**
 * 年
 */
$releaseYmd = SiteConfig::$conf['System']['releaseYmd'];   // リリース日
$releaseYear = date("Y", strtotime($releaseYmd));
$arrYear = getYearFromTo($releaseYear);
WvSmarty::$smarty->assign('arrYear', $arrYear);
$date = getDateInfo(date("Ymd"));
$curParam['year'] = (isset($curParam['year']) ? $curParam['year'] : $date['year']);

/**
 * 月
 */
$releaseYm = date("Y-m", strtotime($releaseYmd));
$curParam['month'] = (isset($curParam['month']) ? $curParam['month'] : $date['month']);
$targetYm = "{$curParam['year']}-{$curParam['month']}";
// リリース後から現在月までの月文字を取得
//    全月表示にしないと、翌年の1月に前の年を選択した場合に、
//    最初は1月しか選択できないので不採用。
//    $arrMonth = getTargetMonthList($releaseYm, $targetYm);
$arrMonth = getMonthList('MM');
WvSmarty::$smarty->assign('arrMonth', $arrMonth);

// サイトの稼働状況取得
$status = getOpenStatusByMonth($releaseYm, $targetYm);
WvSmarty::$smarty->assign('status', $status);

// リリース前か来月が指定されたらアラート表示
if($status['status'] === '稼働前') {
	showWarning('検索条件にリリース前の月が指定されました。');
	
} elseif($status['status'] === '来月') {
	showWarning('検索条件に来月が指定されました。'); 
} else {
   // 稼働中 
}
// 入力値
WvSmarty::$smarty->assign('curParam', $curParam);

$report = new Report();
$data = $report->getUserMonthlyReport($targetYm);

// 末尾に合計を追加
$totalMonth['ymd'] = '合計';
$totalMonth['insCount'] = 0;
$totalMonth['addCount'] = 0;
$totalMonth['delCount'] = 0;
//$totalMonth['buyCount'] = 0;
//$totalMonth['buyAmount'] = 0;
//$totalMonth['buyUserCount'] = 0;
$totalMonth['reviewCount'] = 0;
$totalMonth['questionCount'] = 0;
$totalMonth['answerCount'] = 0;
foreach ($data as $value) {
	if (isset($value['insCount']) && $value['insCount']) {
		$totalMonth['insCount']	+= $value['insCount'];
	}
	if (isset($value['addCount']) && $value['addCount']) {
		$totalMonth['addCount']	+= $value['addCount'];
   	}
	if (isset($value['delCount']) && $value['delCount']) {
		$totalMonth['delCount']	+= $value['delCount'];
   	}
	//if (isset($value['buyCount']) && $value['buyCount']) {
	//	$totalMonth['buyCount']	+= $value['buyCount'];
	//}
	//if (isset($value['buyAmount']) && $value['buyAmount']) {
	//	$totalMonth['buyAmount']	+= $value['buyAmount'];
	//}
	//if (isset($value['buyUserCount']) && $value['buyUserCount']) {
	//	$totalMonth['buyUserCount']	+= $value['buyUserCount'];
	//}
	if (isset($value['reviewCount']) && $value['reviewCount']) {
		$totalMonth['reviewCount']	+= $value['reviewCount'];
	}
	if (isset($value['questionCount']) && $value['questionCount']) {
		$totalMonth['questionCount']	+= $value['questionCount'];
	}
	if (isset($value['answerCount']) && $value['answerCount']) {
		$totalMonth['answerCount']	+= $value['answerCount'];
	}
}
// 日次ARPPUの平均値を算出（月次ARPPUではないので注意。）
// 購入額の日別平均値
//$avgBuyAmount = 0;
//if (isset($totalMonth['buyAmount']) && $totalMonth['buyAmount']) {
//	$avgBuyAmount = $totalMonth['buyAmount']/count($data);
//}
//// 購入UU数の日別平均値
//$avgBuyUserCount = 0;
//if (isset($totalMonth['buyUserCount']) && $totalMonth['buyUserCount']) {
//	$avgBuyUserCount = $totalMonth['buyUserCount']/count($data);
//}
//if ($avgBuyAmount && $avgBuyUserCount) {
//	$totalMonth['arppu'] = round($avgBuyAmount/$avgBuyUserCount);
//} else {
//	$totalMonth['arppu'] = 0;
//}
$data[] = $totalMonth;
WvSmarty::$smarty->assign('data', $data);




























