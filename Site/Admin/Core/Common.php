<?php

/**
 * 
 * サイトの稼働状況取得（指定月から）
 * 
 * レポート表示の際に、リリース月から今月までを
 * 有効なレポート対象の月として返す。
 * 
 */
function getOpenStatusByMonth($releaseYm, $targetYm) {
    
    // YYYY-MM のハイフン除去
    $targetYm = preg_replace( "/-/u" , "", $targetYm);
    // YYYY-MM のハイフン除去
    $releaseYm = preg_replace( "/-/u" , "", $releaseYm);

    $result['status'] = '稼働中';      // ステータス
    $result['openYm'] = $releaseYm;    // リリース月
    //$result['closeYm'] = ;            // 閉鎖月
	$result['curYm'] = date("Ym");      // 今月

    // 指定月が来月以降の場合
   if($targetYm > date("Ym")) {
        $result['status'] = '来月';
   }
    // 指定月がリリース月より前の場合
   if($targetYm < $releaseYm) {
        $result['status'] = '稼働前';
   }
   
    return $result;
}

/**
 *
 * サイトの稼働状況取得（指定年から）
 *
 * レポート表示の際に、リリース年から今年までを
 * 有効なレポート対象の年として返す。
 *
 */
function getOpenStatusByYear($releaseY, $targetY) {

	$result['status'] = '稼働中';      	// ステータス
	$result['openY'] = $releaseY;    	// リリース年

	// 指定年が来年以降の場合
	if($targetY > date("Y")) {
		$result['status'] = '来年';
	}
	// 指定年がリリース年より前の場合
	if($targetY < $releaseY) {
		$result['status'] = '稼働前';
	}

	return $result;
}