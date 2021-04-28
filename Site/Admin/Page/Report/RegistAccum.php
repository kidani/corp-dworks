<?php

$dbh = Db::connect();
try {
	// insCount：インストール数
	// validCount：有効会員数
	// delCount：退会数
	// addCount：入会数
	$sql = "
	    select
	          (select count(userId) from UsersTemp) + (select count(userId) from Users 
	          	 where iosUid is not null or androidUid is not null) as insCount
	        , (select count(userId) from Users
	          	where (status = '登録済') or (status = '仮登録')) as validCount
	        , (select count(userId) from Users
	        	where status = '退会済') as delCount
	        , (select validCount + delCount) as addCount
	 	";
    $sth = $dbh->prepare($sql);
    $result = $sth->execute();
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    showError($e);
    throw $e;
}
WvSmarty::$smarty->assign('data', $data);


