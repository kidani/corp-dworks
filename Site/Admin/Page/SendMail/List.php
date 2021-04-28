<?php

/**
 *
 * メール配信一覧画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

$phAnd = $bind = null;

$sendMail = new SendMail();
$totalCnt = $sendMail->getCnt($bind, $phAnd);

// ページ設定取得
$curPageNo = isset($param['curPageNo']) ? $param['curPageNo'] : 1;
$page = wvPager::getPageInfo($curPageNo, $totalCnt, $pageName, AdminConfig::$conf);

// ソート情報設定
$sort = setSortParam($param, 'sendStartTime', 'desc');
WvSmarty::$smarty->assign('sort', $sort);

// 対象ページのデータ取得
$data = $sendMail->getPage($bind, $phAnd, $page, $sort);
WvSmarty::$smarty->assign('data', $data);

// ページャ取得
$pager = wvPager::getPager($totalCnt, $page, count($data), $param);
WvSmarty::$smarty->assign('pager', $pager);
