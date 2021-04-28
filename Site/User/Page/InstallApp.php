<?php
/**
 *
 * アプリインストール誘導画面
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

$fromPage = isset($param['fromPage']) ? $param['fromPage'] : null;
WvSmarty::$smarty->assign('fromPage', $fromPage);
