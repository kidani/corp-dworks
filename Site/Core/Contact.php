<?php

/**
 *
 * 問合せ
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Contact {

	/**
	 *
	 * 問合せバリデーション（通常問合せ）
	 *
	 */
	public function validate($input) {

		$validation = new Validation();

		// 名前
		//     必須
		//     最大20文字以内
		if (!$input['namae']) {
			return '名前を入力してください。';
		} elseif (!$validation->checkStringCnt($input['namae'], 1, 20)) {
			return '名前は20文字以内で入力してください。';
		}

		// メールアドレス
		//     必須
		//     書式
		if (!$input['mailAddress']) {
			return 'メールアドレスを入力してください。';
		} elseif (!$validation->checkMail($input['mailAddress'])) {
			return 'メールアドレスの書式に間違いがあります。';

		}

		// 問合せ内容
		//     必須
		//     全角20～500文字
		if (!$validation->checkStringCnt($input['detail'], 20, 500)) {
			return '問合せ内容は20文字以上、500文字以内で入力してください。';
		}

		return null;
	}

}















