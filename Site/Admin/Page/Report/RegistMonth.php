<?php

/**
 *
 * 入退会レポート（月別）
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
		      monthList.ym                  			-- 月
		    , ( CASE WHEN insData.insCount IS NOT NULL THEN insData.insCount ELSE 0 END ) 
		       	+ ( CASE WHEN appInsData.appInsCnt IS NOT NULL THEN appInsData.appInsCnt ELSE 0 END ) 
		       	as insCount   							-- インストール数	    
		    , addData.addCount              			-- 入会数
		    , delData.delCount              			-- 退会数
		from
		    (
		        -- 年月リスト
		        SELECT
		        	concat(:targetY, '-',lpad(wknum.number, 2, '0')) as ym
		        FROM 
		        	WorkNumber as wknum
		        WHERE 
		        	wknum.number BETWEEN 1 and 12
		    ) as monthList
		    left join
		    (
		        -- インストール（UsersTemp）
		        select
		              DATE_FORMAT(insTime, '%Y-%m') as ym
		            , count(userId) as insCount
		        from UsersTemp
		        where
		        	DATE_FORMAT(insTime, '%Y') = :targetY
		        group by ym
		    ) as insData on insData.ym = monthList.ym
		    left join
		    (
		        -- インストール（Users）
		        -- アプリインストール後、Usersに移行済みのユーザー数
		        select
		              DATE_FORMAT(appInsTime, '%Y-%m') as ym
		            , count(userId) as appInsCnt
		        from Users
		        where
		        	DATE_FORMAT(appInsTime, '%Y') = :targetY
		        group by ym
		    ) as appInsData on appInsData.ym = monthList.ym  		        		    		    
		    left join
		    (
		        -- 入会
		        select
		              DATE_FORMAT(insTime, '%Y-%m') as ym
		            , count(userId) as addCount
		        from Users
		        where
		        	status = '登録済'  
		        	AND DATE_FORMAT(insTime, '%Y') = :targetY
		        group by ym
		    ) as addData on addData.ym = monthList.ym
		    left join
		    (
		        -- 退会
		        select
		              DATE_FORMAT(updTime, '%Y-%m') as ym
		            , count(userId) as delCount
		        from Users
		        where  
		        	status = '退会済'
		        	AND DATE_FORMAT(updTime, '%Y') = :targetY
		        group by ym
		    ) as delData on delData.ym = monthList.ym
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


























