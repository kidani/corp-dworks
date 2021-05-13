<?php

/**
 * ユーザー・管理画面共通設定
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class SiteConfig {

	// 設定
	public static $conf = array();

	/**
	 * コンストラクタ
	 */
	public function __construct() {

		if (self::$conf) return;

		// INI ファイル取得
		self::$conf = parse_ini_file(WVFW_ROOT . '/Site/Config/Config.ini', true);
		$confWork = self::$conf;

		//------------------------------------------------
		// デバッグ情報
		//------------------------------------------------
		// 実行モード確定
		// WV_DEBUG：システム全体のデバッグモード（0：無効／1：有効）
		//     ・デバッグ情報の表示／非表示
		//     ・テストデータ入力ボタンの表示／非表示
		//     ・テスト用と認識させる警告等の表示／非表示
		// SHOW_DEBUG_ERROR：エラー情報表示の切替（showError関数での画面遷移・エラー出力を制御）
		//     0：定型エラーメッセージを表示。本番用の設定だが、開発段階で本番用のエラー表示が
		//        どうなってるか確認する場合も利用可能。
		//     1：デバッグ用のエラー詳細を表示。開発では通常この設定。
		define('WV_DEBUG', $confWork['Debug']['debugMode']);
		define('SHOW_DEBUG_ERROR', $confWork['Debug']['showDebugError']);

		// PHPエラー出力制御（エラーレベル制御）
		// ログは php.ini で指定した「/var/log/php_errors.log」に出力される。
		if (WV_DEBUG) {
			error_reporting(E_ALL);							// 全て出力
			// error_reporting(E_ERROR);					// エラーのみ出力
			// error_reporting(E_ALL & ~E_NOTICE);			// 注意メッセージ（NOTICE）は除外
			// 推奨エラー（E_DEPRECATED）、注意メッセージ（NOTICE）、実行時エラー（E_WARNING）は除外
			// error_reporting(E_ALL & ~ E_DEPRECATED & ~ E_USER_DEPRECATED & ~ E_NOTICE & ~ E_WARNING);
			ini_set('display_errors', 1);					// PHPエラーをブラウザ表示（0：非表示、1：表示）
		} else {
			error_reporting(E_ERROR);		// エラーのみ出力
			ini_set('display_errors', 0);	// PHPエラーをブラウザ非表示
		}

		// デバッグ用IP登録
		if (isset($confWork['Debug']['ip'])
			&& in_array($_SERVER['REMOTE_ADDR'], $confWork['Debug']['ip'])) {
			define('DEBUG_IP', true);
		} else {
			define('DEBUG_IP', false);
		}

		//------------------------------------------------
		// 基本情報
		//------------------------------------------------
		// システム識別子
		// define('WVFW_ID', $confWork['Base']['WVFW_ID']);              	// WVフレームワーク（ルート階層のディレクトリ名）
		// define('SYSTEM_ID', $confWork['Base']['SYSTEM_ID']);          	// システムID（サイト・アプリ単位で付与）
		define('ENV_ID', $confWork['Base']['ENV_ID']);                		// 環境識別子（Dev／Stg／Prd 各環境は WVFW_ID/SYSTEM_ID/ENV_ID 配下に配置される。）

		// ドメイン
		if ($confWork['Base']['SUB_DOMAIN']) {
			$DOMAIN = "{$confWork['Base']['SUB_DOMAIN']}.{$confWork['Base']['ROOT_DOMAIN']}";
		} else {
			$DOMAIN = $confWork['Base']['ROOT_DOMAIN'];
		}
		define('DOMAIN', $DOMAIN);
		define('SUB_DOMAIN', $confWork['Base']['SUB_DOMAIN']);
		self::$conf['Base']['DOMAIN'] = DOMAIN;

		// URL
		define('SITE_URL', "{$confWork['Base']['PROTOCOL']}://" . DOMAIN . '/');
		self::$conf['SITE_URL'] = SITE_URL;

		// 管理画面URL
		$subDomainAdmin = $confWork['Base']['SUB_DOMAIN_ADMIN'];						// 管理画面サブドメイン
		$domainAdmin = "{$subDomainAdmin}.{$confWork['Base']['ROOT_DOMAIN']}";			// 管理画面ドメイン
		self::$conf['SITE_URL_ADMIN'] = "https://{$domainAdmin}/";						// 管理画面URL（HTTPSに注意！）

		// クエリ文字列
		// utm_source=google など広告経路トラッキング用
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
		define('SITE_URL_QUERY', $query);
		self::$conf['SITE_URL_QUERY'] = SITE_URL_QUERY;

		// DOC_ROOT ドキュメントルート
		// /var/www/html/FleaMarket/Fishing/Dev/Site/User/Web
		// define('DOC_ROOT', $_SERVER['DOCUMENT_ROOT']);

		// サイト名
		define('SITE_NAME', $confWork['Base']['SITE_NAME']);                      		// サイト名
		// define('SITE_CATCH', $confWork['Base']['SITE_CATCH']);					 	// サイトキャッチフレーズ
		// define('COPYRIGHT', $confWork['Base']['COPYRIGHT']);                      	// コピーライト

		//------------------------------------------------
		// 各種設定
		//------------------------------------------------
		// データベース
		// static 変数にデフォルトのDB接続情報を設定するため、ここで一度呼出している点に注意。
		Db::connect($confWork['Db']);
		// メール
		// $confWork['Mail'];
		// システム
		// $confWork['System'];
		// 決済
		// $confWork['Payment'];
		// Google
		// $confWork['Google'];
		// リリース制御
		// $confWork['VersionUp'];
		// 運営会社
		// $confWork['Company'];
		// その他
		// $confWork['Other'];
		// 消費税率
		define('ETAX', $confWork['Other']['exciseTax']);
	}
}












