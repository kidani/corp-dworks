<?php

/**
 *
 * レポート
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

class Report {

	/**
	 *
	 * 月次レポート取得（リアルタイムレポート）
	 *
	 */
	public function getUserMonthlyReport($targetYm) {
		$dbh = Db::connect();
		try {
			$sql = "	
				select
					  dateData.ymd                  												-- 日付
					, ( CASE WHEN insData.insCount IS NOT NULL THEN insData.insCount ELSE 0 END ) 
						+ ( CASE WHEN appInsData.appInsCnt IS NOT NULL THEN appInsData.appInsCnt ELSE 0 END ) 
						as insCount   																-- インストール数
					, addData.addCount              												-- 入会数
					, delData.delCount              												-- 退会数
					-- , ROUND(buyData.buyAmount/buyData.buyUserCount, 0) as arppu                  -- 日次ARPPU
					-- , ROUND(buyData.buyAmount/dailyStats.effectiveUserCnt, 0) as arpu            -- 日次ARPU
					-- , ROUND(buyData.buyUserCount/dailyStats.effectiveUserCnt*100, 1) as buyRate  -- 課金率（課金UU数／その日の有効会員数）
					, reviewData.reviewCount              											-- 数
					, questionData.questionCount              										-- 質問数
					, answerData.answerCount              											-- 回答数
				from
					(
						-- 日付
						SELECT
							ADDDATE(concat(:targetYm, '-01'), wknum.number) as ymd
						FROM WorkNumber as wknum
						WHERE 
							ADDDATE(concat(:targetYm, '-01'), wknum.number)
						BETWEEN concat(:targetYm, '-01') AND LAST_DAY(concat(:targetYm, '-01'))
					) as dateData
					left join
					(
						-- インストール（UsersTemp）
						select
							  DATE_FORMAT(insTime, '%Y-%m-%d') as ymd
							, count(userId) as insCount
						from UsersTemp
						where
							DATE_FORMAT(insTime, '%Y-%m') = :targetYm
						group by ymd
					) as insData on insData.ymd = dateData.ymd
					left join
					(
						-- インストール（Users）
						-- アプリインストール後、Usersに移行済みのユーザー数
						select
							  DATE_FORMAT(appInsTime, '%Y-%m-%d') as ymd
							, count(userId) as appInsCnt
						from Users
						where
							DATE_FORMAT(appInsTime, '%Y-%m') = :targetYm
						group by ymd
					) as appInsData on appInsData.ymd = dateData.ymd   		        		    		    
					left join
					(
						-- 入会
						select
							  DATE_FORMAT(insTime, '%Y-%m-%d') as ymd
							, count(userId) as addCount
						from Users
						where
							status = '登録済'  
							AND DATE_FORMAT(insTime, '%Y-%m') = :targetYm
						group by ymd
					) as addData on addData.ymd = dateData.ymd
					left join
					(
						-- 退会
						select
							  DATE_FORMAT(updTime, '%Y-%m-%d') as ymd
							, count(userId) as delCount
						from Users
						where  
							status = '退会済'
							AND DATE_FORMAT(updTime, '%Y-%m') = :targetYm
						group by ymd
					) as delData on delData.ymd = dateData.ymd
					left join
					(
						-- 口コミ投稿
						select
							  DATE_FORMAT(insTime, '%Y-%m-%d') as ymd
							, count(userId) as reviewCount
						from Review
						where
							DATE_FORMAT(insTime, '%Y-%m') = :targetYm
						group by ymd
					) as reviewData on reviewData.ymd = dateData.ymd
					left join
					(
						-- 質問投稿
						select
							  DATE_FORMAT(insTime, '%Y-%m-%d') as ymd
							, count(userId) as questionCount
						from Question
						where
							DATE_FORMAT(insTime, '%Y-%m') = :targetYm
						group by ymd
					) as questionData on questionData.ymd = dateData.ymd
					left join
					(
						-- 回答投稿
						select
							  DATE_FORMAT(insTime, '%Y-%m-%d') as ymd
							, count(userId) as answerCount
						from Answer
						where
							DATE_FORMAT(insTime, '%Y-%m') = :targetYm
						group by ymd
					) as answerData on answerData.ymd = dateData.ymd
				order by dateData.ymd
				";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':targetYm', $targetYm, PDO::PARAM_STR);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data;
	}

	/**
	 *
	 * 月次レポート取得（バックアップレポート）
	 *
	 */
	public function getMonthlyReport($targetYm) {
		$dbh = Db::connect();
		try {
			$sql = "	
				SELECT
					  Report.reportDate			-- 日付
					, Report.insTime			-- 登録日時
					, Report.pv					-- PV数
					, Report.install			-- インストール数
					, Report.regist             -- 入会数
					, Report.resign             -- 退会数
					, Report.review             -- 数
					, Report.question           -- 質問数
					, Report.answer             -- 回答数
				FROM
					Report
				WHERE	
					Report.reportDate is not null
					AND DATE_FORMAT(Report.reportDate, '%Y-%m') = :targetYm
				ORDER BY 
					Report.reportDate asc
				";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':targetYm', $targetYm, PDO::PARAM_STR);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data;
	}

	/**
	 *
	 * 日次レポート取得（Reportテーブルから）
	 *
	 */
	public function getDailyReport($targetDay) {
		$dbh = Db::connect();
		try {
			$sql = "	
				SELECT
					  Report.reportDate			-- 日付
					, Report.insTime			-- 登録日時
					, Report.pv					-- PV数
					, Report.install			-- インストール数
					, Report.regist             -- 入会数
					, Report.resign             -- 退会数
					, Report.review             -- 数
					, Report.question           -- 質問数
					, Report.answer             -- 回答数
				FROM
					Report
				WHERE	
					Report.reportDate is not null
					AND DATE_FORMAT(Report.reportDate, '%Y-%m-%d') = :targetDay
				ORDER BY 
					Report.reportDate asc
				";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(':targetDay', $targetDay, PDO::PARAM_STR);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		if ($data) return $data[0];
	}

	/**
	 *
	 * 日次レポート取得
	 *
	 * Users 他各テーブルからのレポートを収集する。
	 * PV数は取得不可。
	 *
	 */
	public function getUserDailyReport($date = null) {
		if (!$date) {
			// 昨日をセット
			$date = getDay('-1');
		}
		$dateInfo = getDateInfo($date);
		$month = "{$dateInfo['year']}-{$dateInfo['month']}";

		// 対象月の月次レポート取得
		//     2重管理にならないよう、あえて月次レポートから抜粋するようにした。
		//     ＜取得例＞
		//     --------------------------------------------------
		//     [ymd] => 2019-02-07
		//     [insCount] => 0			// アプリインストール数
		//     [addCount] =>			// 入会数
		//     [delCount] =>			// 退会数
		//     [buyCount] =>			// 購入数
		//     [buyAmount] =>			// 購入額
		//     [buyUserCount] =>		// 購入UU数
		//     [arppu] =>				// 日次ARPPU
		//     --------------------------------------------------
		$data = $this->getUserMonthlyReport($month);
		foreach ($data as $key => $value) {
			if ($value['ymd'] === $date) {
				return $value;
			}
		}
		return null;
	}

	/**
	 *
	 * DAU数取得
	 *
	 * 現状DAUを把握するには、専用カラムの追加などが必要。
	 * lastLoginTime		：最後にログイン操作した時間なのでDAUではない。
	 * lastSessStartTime	：最後にセッションスタートした時間なのでDAUではない。
	 *
	 * 対応するとしたら、Users.accessDaily などカラム追加して、Users::setUpdateTime で
	 * アクセスされたタイミングでフラグセット → 24時ジャストに前日の accessDaily が
	 * 有効になっているユーザー数をカウントするなどか。
	 *
	 */	 
	//public function getDauCount($targetDay) {
	//	$dbh = Db::connect();
	//	try {
	//		$sql = "
	//			select
	//				count(userId) as count
	//			from Users
	//			where DATE_FORMAT(XXXXX, '%Y%m%d') = '$targetDay'
	//			";
	//		$sth = $dbh->prepare($sql);
	//		$sth->execute();
	//		$data = $sth->fetchAll(PDO::FETCH_ASSOC);
	//	} catch (PDOException $e) {
	//		showError($e);
	//		throw $e;
	//	}
	//	return $data[0]['count'];
	//}
	
}

