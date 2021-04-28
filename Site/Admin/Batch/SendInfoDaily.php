<?php

/**
 * 
 * 日次通知メール送信
 * 
 * クーロンで実行
 * 
 * cd /var/www/html/Social/Hoikuen/Prd/Site/Admin/Web;
 * /usr/bin/php index.php p=Batch/SendInfoDaily
 * 
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once (WVFW_ROOT . '/Site/Admin/Config/BatchConfig.php');

//------------------------------------------------
// 日次レポート
//------------------------------------------------
$domein = DOMAIN;
$checkDate = date("Ymd", strtotime("-1 day"));				// 例：20190804
$dateInfo = getDateInfo($checkDate);

// 日次レポート取得
$report = new Report();
$dataReport = $report->getDailyReport($dateInfo['dateHyphen']);

// メール送信（宛先：システム担当）
$mailBody = null;
$lblReport = array(
	'reportDate'    => '対象日',
	'pv'            => 'PV数',
	'install'       => 'インストール数',
	'regist'        => '入会数',
	'resign'        => '退会数',
	'review'        => '口コミ登録数',
	'question'      => '質問登録数',
	'answer'        => '回答登録数',
);
foreach ($dataReport as $key => $value) {
	if (isset($lblReport[$key])) {
		$mailBody .= "{$lblReport[$key]}：{$value}\n";
	}
}

// メール送信
$mailData = array();
$mail = new Mail();
$mail->setBaseInfo($mailData, SiteConfig::$conf);
$subject = "［{$mailData['siteName']}］日次レポート";
$mailData['detail'] = $mailBody;
$mailData['adminUrl'] = SiteConfig::$conf['SITE_URL_ADMIN'] . "?p=Report/Regist";	// 入退会（日別）
// 運用元へ送信
$template = WVFW_ROOT . "/Site/Doc/Mail/Report/ToAdmin.txt";
if (!$mail->sendMail($mailData, $mailData['fromAddress'], SiteConfig::$conf['Mail']['toAdmin'], $template, $subject)) {
	errorLog($mailData, '運用元への日次レポート送信失敗');
}
