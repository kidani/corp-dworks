<?php

/**
 *
 * バリデーション（入力チェック）
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Validation {

	/**
	 *
	 * 文字数チェック
	 *
	 * bin2hex では全角1文字が6バイト、半角1文字が2バイトでカウントされる。
	 * （abcde：10バイト、あいう：18バイト）
	 *
	 * $minCnt ：任意なら0をセット。
	 *
	 * ＜例＞
	 * ---------------------------------------------------------------------------------
	 * // ニックネーム
	 * if (!$validation->checkStringCnt($input['nickname'], 1, 20)) {
	 * 	return 'ニックネームは1文字以上～20文字以内で入力してください。';
	 * }
	 * ---------------------------------------------------------------------------------
	 *
	 */
	public function checkStringCnt($input, $minCnt, $maxCnt, $zenkaku = true) {
		if ($zenkaku) {
			// 全角文字数指定の場合
			$minCnt = 6 * $minCnt;
			$maxCnt = 6 * $maxCnt;
		} else {
			// 半角文字数指定の場合
			$minCnt = 2 * $minCnt;
			$maxCnt = 2 * $maxCnt;
		}
		$length = strlen(bin2hex($input));
		if ($length < $minCnt || $length > $maxCnt) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * 日付フォーマットチェック
	 *
	 * 文字種	：半角数字のみ
	 * 桁数		：8（YYYYMMDD形式、スラッシュ等の区切り文字なし）
	 *
	 * --------------------------------------------------------------------------
	 * if (!$input['birthday']) {
	 * 	return '生年月日を入力してください。';
	 * } elseif ($message = $validation->checkDate($input['birthday'])) {
	 * 	return $message;
	 * }
	 * --------------------------------------------------------------------------
	 *
	 */
	public function checkDate($input) {
		// 文字種チェック
		if (!preg_match("/^[0-9]+$/", $input)) {
			return '生年月日は半角数字のみで入力してください。';
		}
		// 桁数チェック
		$len = mb_strlen($input, 'UTF-8');
		if ($len !== 8) {
			return '生年月日は8桁で入力してください。';
		}
		// 日付としての有効性チェック
		$checkDate = date("Ymd", strtotime($input));
		if ($input !== $checkDate) {
			return '生年月日は有効な日付を入力してください。';
		}
		return null;
	}

	/**
	 *
	 * 月フォーマットチェック
	 *
	 * 文字種	：半角数字のみ
	 * 桁数		：6（YYYYMM形式、スラッシュ等の区切り文字なし）
	 *
	 * --------------------------------------------------------------------------
	 * if (!$input['month']) {
	 * 	return '年月を入力してください。';
	 * } elseif ($message = $validation->checkMonth($input['month'])) {
	 * 	return $message;
	 * }
	 * --------------------------------------------------------------------------
	 *
	 */
	public function checkMonth($input, $checkDigit = true) {
		// 文字種チェック
		if (!preg_match("/^[0-9]+$/", $input)) {
			return '生年月日は半角数字のみで入力してください。';
		}
		// 桁数チェック
		$len = mb_strlen($input, 'UTF-8');
		if ($len !== 6) {
			return '生年月日は6桁で入力してください。';
		}
		// 月としての有効性チェック
		// 19XX年代、20XX年のみを有効とする場合
		if ($checkDigit && !preg_match("/^(19|20)$/", $input)) {
			return '正しい月を入力してください。';
		}
		return null;
	}

	/**
	 *
	 * 日時フォーマットチェック
	 *
	 * YYYY/MM/DD hh:mm 形式、スラッシュ区切り
	 *
	 * --------------------------------------------------------------------------
	 * if (!$validation->checkDateTime($input['datetime'])) {
	 * 	   return '配信開始日時は「YYYY/MM/DD hh:mm」で入力して下さい。';
	 * }
	 * --------------------------------------------------------------------------
	 *
	 */
	function checkDateTime($datetime) {
		if (!preg_match("#^([0-9]{4})\/([0-9]|0[0-9]|1[012])\/([0-9]|0[1-9]|[12][0-9]|3[01]) ([01]?[0-9]|2[0-3]):([0-5][0-9])$#"
				, $datetime, $matches)) {
			return false;
		} else {
			$year = $matches[1];
			$month = $matches[2];
			$day = $matches[3];
			if (!checkdate($month, $day, $year)) {
				// 存在しない日付
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 電話番号フォーマットチェック
	 *
	 * 文字種	：半角数字のみ
	 * 桁数		：10～11（ハイフン等の区切り文字なし）
	 *
	 */
	public function checkTel($tel) {
		// 文字種チェック
		if (!preg_match("/^[0-9]+$/", $tel)) {
			return '電話番号に半角数字以外は入力しないで下さい。';
		}
		// 桁数チェック
		$len = mb_strlen($tel, 'UTF-8');
		if (!($len >= 10 && $len <= 11)) {
			return '電話番号の桁数は10～11桁（ハイフンなし）で入力して下さい。';
		}

		// 認証コードありの場合
		//// 電話番号重複チェック
		///      退会済み含め同じ電話番号は登録不可
		//if (!$this->checkTelNumber($tel)) {
		//	return'この電話番号は認証に使用できません。';
		//}
		//
		//// 認証コード再発行までの時間制限チェック
		//if (self::$userInfo['tel']) {
		//	if (self::$userInfo['tokenMakeTime']) {
		//		$tokenMakeTime = self::$userInfo['tokenMakeTime'];
		//		$targetTime = strtotime("{$tokenMakeTime} +5 minutes");
		//		if ($targetTime > strtotime("now")) {
		//			// 5分以内の再発行不可
		//			return '電話番号を再登録する場合は、前回の登録より5分以上間隔を空けて下さい。';
		//		}
		//	}
		//}
		//
		//// 認証コード発行回数制限チェック
		//if (intval(self::$userInfo['authCheckCnt']) >= 3) {
		//	return '電話番号の登録・認証を申請可能な上限回数を超えました。<br>'
		//					. '再申請可能にするにはお問い合わせフォームよりご連絡下さい。';
		//}

		return null;
	}

	/**
	 *
	 * メールアドレスのフォーマットチェック
	 *
	 * 文字種	：半角英数字と一部記号（.!#%&\-_）のみ
	 * ※docomoメールアドレス専用文字許可（.など）
	 *
	 */
	function checkMail($str) {
		$divCnt = explode('@', $str);
		if (preg_match("/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-z]+(\.[!#%&\-_0-9a-z]+)+$/", $str)
			&& count($divCnt) === 2) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * 郵便番号フォーマットチェック
	 *
	 * 文字種	：半角数字
	 * 桁数		：7桁（ハイフン等の区切り文字なし）
	 *
	 */
	public function checkZip($zip) {
		// 文字種チェック
		if (!preg_match("/^[0-9]+$/", $zip)) {
			return '郵便番号に半角数字以外は入力しないで下さい。';
		}
		// 桁数チェック
		$len = mb_strlen($zip, 'UTF-8');
		if ($len !== 7) {
			return '郵便番号は7桁（ハイフンなし）で入力して下さい。';
		}
		return null;
	}

}















