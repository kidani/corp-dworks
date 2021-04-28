<?php

/**
 *
 * ニュース一覧画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

$phAnd = $bind = null;

// openTime, closeTime 公開中のみ
$phAnd .= " and News.openTime < now() and ( closeTime is null || closeTime > now() ) ";

$news = new News();
$totalCnt = $news->getCnt($bind, $phAnd);

// ページ設定取得
$curPageNo = isset($param['curPageNo']) ? $param['curPageNo'] : 1;
$page = wvPager::getPageInfo($curPageNo, $totalCnt, $pageName, UserConfig::$conf);

// ソート情報設定
$sort = setSortParam($param, 'openTime', 'desc');
WvSmarty::$smarty->assign('sort', $sort);

// 対象ページのデータ取得
$data = $news->getPage($bind, $phAnd, $page, $sort);
WvSmarty::$smarty->assign('data', $data);

// ページャ取得
$pager = wvPager::getPager($totalCnt, $page, count($data), $param);
WvSmarty::$smarty->assign('pager', $pager);
