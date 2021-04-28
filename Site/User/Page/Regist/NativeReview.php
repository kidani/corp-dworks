<?php

/**
 *
 * ネイティブアプリ アプリ内レビュー誘導ダイアログ回答取得
 *
 * アプリ側でパラメータ：showReviewAlert=1 を検知すると、
 * レビュー誘導ダイアログ（フィードバックダイアログ）が開く。
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

// レビューの回答（android - 0：いえ、結構です、1：後で評価する、2：今すぐ評価する）
$answerNo = intval(isset($param['answerNo']) ? $param['answerNo'] : null);
 $showAppReviewCount = null;
//if ($answerNo === 0) {
//	// 「いえ、結構です」の場合 → 60日間のデイリーユニークアクセス後に再表示
//	// $showAppReviewCount = User::$userInfo['accDailyCount'] + 60;
if ($answerNo === 0) {
	// 「今は評価しない」の場合（iOSでは「今はしない」と表示） → 10日間のデイリーユニークアクセス後に再表示
	$showAppReviewCount = User::$userInfo['accDailyCount'] + 10;
} elseif ($answerNo === 1) {
	// 「今すぐ評価する」の場合 → 再表示しない
	$showAppReviewCount = null;
} else {
	errorLog($param, 'アプリ内レビューの回答不正検知');
	header("Location:" . SITE_URL);
	exit;
}

// DB更新
$paramDb['showAppReviewCount'] = $showAppReviewCount;
$logCount = ($showAppReviewCount ? $showAppReviewCount : 'NULL');
if (USERS_TEMP) {
	$usersTemp = new UsersTemp();
	$usersTemp->updateUsers(User::$userId, $paramDb);
	if (WV_DEBUG) trace("{User::$userId} の UsersTemp.showAppReviewCount を{$logCount} に更新しました。");
} else {
	$user->updateUsers(User::$userId, $paramDb);
	if (WV_DEBUG) trace("{User::$userId} の Users.showAppReviewCount を{$logCount} に更新しました。");
}

// リダイレクト
if ($answerNo === 1) {
	// 今すぐ評価するを選択した場合、Google Play へ
	header('Location:market://details?id=com.wd_valley.hokatsu');
	// iOS は [SKStoreReviewController requestReview]; によりアプリ内で完結するのでリダイレクト不要。
} else {
	// それ以外はトップへ
	header("Location:" . SITE_URL);
}

exit;













