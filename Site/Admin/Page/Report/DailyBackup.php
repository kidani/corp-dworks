<?php

/**
 *
 * 日別レポート
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
$data = $report->getMonthlyReport($targetYm);

// 末尾に合計を追加
$totalMonth['reportDate'] = '合計';
$totalMonth['pv'] = 0;
$totalMonth['install'] = 0;
$totalMonth['regist'] = 0;
$totalMonth['resign'] = 0;
$totalMonth['review'] = 0;
$totalMonth['question'] = 0;
$totalMonth['answer'] = 0;
foreach ($data as $value) {
	if (isset($value['pv']) && $value['pv']) {
		$totalMonth['pv']	+= $value['pv'];
	}
	if (isset($value['install']) && $value['install']) {
		$totalMonth['install']	+= $value['install'];
	}
	if (isset($value['regist']) && $value['regist']) {
		$totalMonth['regist']	+= $value['regist'];
   	}
	if (isset($value['resign']) && $value['resign']) {
		$totalMonth['resign']	+= $value['resign'];
   	}
	if (isset($value['review']) && $value['review']) {
		$totalMonth['review']	+= $value['review'];
	}
	if (isset($value['question']) && $value['question']) {
		$totalMonth['question']	+= $value['question'];
	}
	if (isset($value['answer']) && $value['answer']) {
		$totalMonth['answer']	+= $value['answer'];
	}
}
$data[] = $totalMonth;
WvSmarty::$smarty->assign('data', $data);

























