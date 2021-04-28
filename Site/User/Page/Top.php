<?php

/**
 *
 * トップ画面
 *
 * @author      	kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// ニュース新着
$news = new News();
$dataNews = $news->getNew(4);
WvSmarty::$smarty->assign('dataNews', $dataNews);
