<?php

/**
 *
 * 月別レポート
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
$releaseY = date("Y", strtotime($releaseYmd));
$arrYear = getYearFromTo($releaseY);
WvSmarty::$smarty->assign('arrYear', $arrYear);
$date = getDateInfo(date("Ymd"));
$curParam['year'] = (isset($curParam['year']) ? $curParam['year'] : $date['year']);
$targetY = $curParam['year'];

// サイトの稼働状況取得
$status = getOpenStatusByYear($releaseY, $targetY);
WvSmarty::$smarty->assign('status', $status);

// リリース前か来年が指定されたらアラート表示
if($status['status'] === '稼働前') {
	showWarning('検索条件にリリース前の年が指定されました。');
} elseif($status['status'] === '来年') {
	showWarning('検索条件に来年が指定されました。');
}

// 入力値
WvSmarty::$smarty->assign('curParam', $curParam);

$dbh = Db::connect();
try {
	$sql = "	
		select
              monthList.ym                  -- 月
            , reportData.pvCount            -- PV数
            , reportData.insCount           -- インストール数
            , reportData.addCount           -- 入会数
            , reportData.delCount           -- 退会数
            , reportData.reviewCount        -- 数
            , reportData.questionCount      -- 質問数
            , reportData.answerCount        -- 回答数	    
		from
		    (
		        -- 年月リスト
		        SELECT
		        	concat(:targetY, '-', lpad(wknum.number, 2, '0')) as ym
		        FROM 
		        	WorkNumber as wknum
		        WHERE 
		        	wknum.number BETWEEN 1 and 12
		    ) as monthList			
		    left join
		    (
		        select
		              DATE_FORMAT(reportDate, '%Y-%m') as ym    -- YYYY-MM
                    , sum(Report.pv) as pvCount                 -- PV数
                    , sum(Report.install) as insCount           -- インストール数
                    , sum(Report.regist) as addCount            -- 入会数
                    , sum(Report.resign) as delCount            -- 退会数
                    , sum(Report.review) as reviewCount         -- 数
                    , sum(Report.question) as questionCount     -- 質問数
                    , sum(Report.answer) as answerCount         -- 回答数
		        from 
		        	Report
		        where
		        	DATE_FORMAT(reportDate, '%Y') = :targetY
		        group by ym
		    ) as reportData on reportData.ym = monthList.ym		
		order by monthList.ym
	 ";
	$sth = $dbh->prepare($sql);
	$sth->bindValue(':targetY', $targetY, PDO::PARAM_STR);
	$result = $sth->execute();
	$data = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	showError($e);
	throw $e;
}

// 末尾に合計を追加
$totalYear['ym'] = '合計';
$totalYear['insCount'] = 0;
$totalYear['addCount'] = 0;
$totalYear['delCount'] = 0;
foreach ($data as $value) {
	if (isset($value['insCount']) && $value['insCount']) {
		$totalYear['insCount']	+= $value['insCount'];
	}
	if (isset($value['addCount']) && $value['addCount']) {
		$totalYear['addCount']	+= $value['addCount'];
	}
	if (isset($value['delCount']) && $value['delCount']) {
		$totalYear['delCount']	+= $value['delCount'];
	}
}
$data[] = $totalYear;
WvSmarty::$smarty->assign('data', $data);




