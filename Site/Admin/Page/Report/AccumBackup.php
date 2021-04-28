<?php

/**
 *
 * 累計レポート（全期間）
 *
 * @author      kidani@wd-valley.com
 *
 */

$dbh = Db::connect();
try {
	$sql = "
	    	select
				  sum(Report.pv) as pvCount                 -- PV数
				, sum(Report.install) as insCount           -- インストール数
				, sum(Report.regist) as addCount            -- 入会数
				, sum(Report.resign) as delCount            -- 退会数
				, sum(Report.review) as reviewCount         -- 数
				, sum(Report.question) as questionCount     -- 質問数
				, sum(Report.answer) as answerCount         -- 回答数      
            from 
            	Report 				        		        
	 	";
	$sth = $dbh->prepare($sql);
	$result = $sth->execute();
	$data = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	showError($e);
	throw $e;
}
WvSmarty::$smarty->assign('data', $data);

















