<?php

/**
 *
 * サイトマップXML
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

require_once ('XML/Serializer.php');

class Sitemap {

	/**
	 *
	 * サイトマップ作成
	 *
	 */
	public function make($path, $rdf, $rootName = 'urlset') {
		$options = array(
			"mode"        		=> "simplexml" ,
			"indent"      		=> " ",
			"linebreak"   		=> "\n",
			"typeHints"   		=> false,
			"addDecl"     		=> true,
			"encoding"    		=> "UTF-8",
			"rootName"    		=> $rootName,
			"rootAttributes" 	=> array(
				"xmlns" => "http://www.sitemaps.org/schemas/sitemap/0.9"
			)
		);
		// urlset sitemapindex
		$serializer = new XML_Serializer($options);
		if (!$serializer->serialize($rdf)) {
			showError($sitemapList, 'sitemap.xml 作成失敗');
			return false;
		}
		$xml = $serializer->getSerializedData();
		file_put_contents($path, $xml);
		// echo $xml;
		return true;
	}

	/**
	 *
	 * sitemap.xml（ルートサイトマップ）作成用の RDF 取得
	 *
	 */
	public function getRdf($sitemapList) {
		$rdf = array();
		foreach ($sitemapList as $key => $xmlFileName) {
			// URLの & → &amp; に置換されるが、W3C Recommendation なので問題ない。
			$xmlUrl = SITE_URL . "sitemap/{$xmlFileName}";
			$xmlPath = WVFW_ROOT . "/Site/User/Web/sitemap/{$xmlFileName}";
			if (!file_exists($xmlPath)) {
				errorLog("サイトマップファイル「{$xmlPath}」なし検知");
			} else {
				// 最終更新時間取得
				// lastmod：最終更新日時（YYYY-MM-DDThh:mmTZD 形式）
				$timeStamp = date('c', filemtime($xmlPath));
				$rdf['sitemap'][] = array(
					"loc" 				=> $xmlUrl,
					"lastmod" 			=> $timeStamp,
				);
			}
		}
		return $rdf;
	}

}















