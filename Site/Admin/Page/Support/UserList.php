<?php

/**
 *
 * ユーザー一覧
 *
 * @author      kidani@wd-valley.com
 *
 */

// クエリ保持（検索フォーム引継ぎ用）
WvSmarty::$smarty->assign('curParam', $param);

//------------------------------------------------
// 検索条件
//------------------------------------------------
$phAnd = $bind = null;

// ユーザーID
if (isset($param['userId']) && $param['userId']) {
	$phAnd .= " and Users.userId = :userId ";
	$bind['userId']['value'] = $param['userId'];
	$bind['userId']['type'] = PDO::PARAM_STR;
}

// ニックネーム
if (isset($param['nickname']) && $param['nickname']) {
	$phAnd .= " and nickname like :nickname ";
	$bind['nickname']['value'] = $param['nickname'];
	$bind['nickname']['type'] = 'like';
}

// メールアドレス
if (isset($param['mailAddress']) && $param['mailAddress']) {
	$phAnd .= " and mailAddress like :mailAddress "; 
	$bind['mailAddress']['value'] = $param['mailAddress'];
	$bind['mailAddress']['type'] = 'like';
}

// 電話番号
//if (isset($param['tel']) && $param['tel']) {
//	$phAnd .= " and Profile.tel like :tel ";
//	$bind['tel']['value'] = $param['tel'];
//	$bind['tel']['type'] = 'like';
//}

// 会員ステータス	
if (isset($param['status']) && $param['status']) {
	// カンマ連結
	$keyList = Db::makePhInFromList($param['status'], true);
	$phAnd .= " and Users.status in ( {$keyList} ) ";
}

// 操作制限
if (isset($param['restriction']) && $param['restriction']) {
	$keyList = Db::makePhInFromList($param['restriction'], true);
	$phAnd .= " and Users.restriction in ( {$keyList} ) ";
}

// メモ
if (isset($param['memo']) && $param['memo']) {
	$phAnd .= " and Users.memo is not null ";
}

// fakeUser
if (isset($param['fakeUser']) && $param['fakeUser']) {
	$phAnd .= " and fakeUser = :fakeUser ";
	$bind['fakeUser']['value'] = $param['fakeUser'];
	$bind['fakeUser']['type'] = PDO::PARAM_INT;
}

// 登録日時	
if (isset($param['insTimeFrom']) && $param['insTimeFrom']
	|| isset($param['insTimeTo']) && $param['insTimeTo']) {	
	$phAnd .= Db::makeSqlTimeFromTo('Users', $param, 'insTime');
}

// 最終ログイン日時	
if (isset($param['lastLoginTimeFrom']) && $param['lastLoginTimeFrom']
	|| isset($param['lastLoginTimeTo']) && $param['lastLoginTimeTo']) {
	$phAnd .= Db::makeSqlTimeFromTo('Users', $param, 'lastLoginTime');
}

//------------------------------------------------
// 表示データ取得
//------------------------------------------------
// ユーザー数取得
//     検索条件にマッチするユーザー数合計を取得
$user = new User();
$totalCnt = $user->getUserCount($bind, $phAnd);
WvSmarty::$smarty->assign('totalCnt', $totalCnt);

// ページ設定取得
$curPageNo = isset($param['curPageNo']) ? $param['curPageNo'] : 1;
$page = wvPager::getPageInfo($curPageNo, $totalCnt, $pageName, AdminConfig::$conf);

// ソート情報設定
$sort = setSortParam($param, 'insTime', 'desc');
WvSmarty::$smarty->assign('sort', $sort);

// ユーザー一覧取得
$data = $user->getPage($bind, $phAnd, $page, $sort);
WvSmarty::$smarty->assign('data', $data);

// ページャ取得
$pager = wvPager::getPager($totalCnt, $page, count($data), $param);
WvSmarty::$smarty->assign('pager', $pager);



















