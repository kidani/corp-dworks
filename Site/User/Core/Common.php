<?php

/**
 * ユーザー画面共通の処理
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

/**
 *
* PF向けのURL変換処理
 *
 * Smarty のブロック関数プラグイン登録
 *
 * $params  ：%%urlConvert%% ～ %%/urlConvert%% 等の識別子を指定
 * $content ：URLエンコード対象の文字列
 *
 */
function smartyLinkUrlConvert($params, $content)
{
    return linkUrlConvert($content);
}

// 引継ぐパラメータのみのクエリを取得
//    prefCode      ：都道府県 
//    cityCode   	：市区町村
//    stationCode   ：駅・路線
//    schoolId      ：学校
function setTakeOverQuery($param, $targetKey = array('prefCode', 'cityCode', 'stationCode', 'prefCode', 'schoolId')) {
	foreach ($targetKey as $key => $value) {
		if (isset($param[$value]) && $param[$value]) {
			$query[$value] = $param[$value];
		}
	}
	if ($query) {
		return http_build_query($query);
	}
}














