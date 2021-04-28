<?php

/**
 *
 * データベース関連
 *
 * @author      kidani@wd-valley.com
 * @copyright   Wood Valley Co., Ltd. All rights reserved.
 *
 */

/**
 * データベース処理
 */
class Db extends PDO {

    /**
     * DB接続（データベースハンドル）
     */
    protected static $dbh;

    /**
     * 現在接続中のDB情報
     */
    protected $curConnectInfo;

    /**
     *
	 * コンストラクタ
     *     Singleton にしたいので、new で複数インスタンスを生成しないよう注意。
     *     __construct は protected にできない。
	 *
     */
    public function __construct($connectInfo = null) {
        if ($connectInfo) {
        	// DB情報を保持
        	$this->curConnectInfo = $connectInfo;
        } elseif (!$this->curConnectInfo) {
	        showError('エラー：DB接続情報を取得できません。');
	       return;		
		}
        // DB接続
        //     データソース名：mysql:host=hoge.com; dbname=dev-fuga
        //     PHP 5.3.6からDSNでcharsetを指定可能。
        $dsn = "mysql:host=" . $this->curConnectInfo['host'] . "; dbname=" . $this->curConnectInfo['name']. "; charset=utf8;";
		parent::__construct($dsn, $this->curConnectInfo['user'], $this->curConnectInfo['pass']);
    }

    /**
     *
	 * DB接続
	 * 
	 * @param    $connectInfo	：接続情報
	 * 戻り値   $dbh           ：データベースハンドル
	 * 
     */
    public static function connect($connectInfo = null) {
        if(!self::$dbh) {
            // 新規接続
            self::$dbh = new self($connectInfo);
			self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// エミュレーション無効化
			// self::$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } else {
            // 既に接続ありの場合
			if ($connectInfo && $connectInfo !== self::$dbh->curConnectInfo) {
                // 現在と異なる接続情報の場合
                //     現在の接続を変更
                self::$dbh = new self($connectInfo);
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }	
        return self::$dbh;
    }

    /**
     * 全データ取得
     */
    public static function selectAll($table, $sortColName = '', $sortType = 'ASC') {
        return self::selectRows($table, null, null, null, $sortColName, $sortType);
    }

    /**
     * 全データ数取得
     */
    public static function selectAllCnt($table) {
        $dbh = self::connect();
        try {
            $sql = "
                SELECT
                    count(*) as count
                FROM
                    $table
                ";
           	$sth = $dbh->prepare($sql);
    		$sth->execute();
            $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            showError($e);
            throw $e;
        }
        return $data[0]['count'];
    }

	/**
	 *
	 * カラムの最大値取得
	 *
	 * AUTO_INCREMENT と思われる値。
	 * 厳密には違うかもしれないので多用しないこと。
	 *
	 */
	public static function getNextInsertId($table, $colName) {
		$dbh = self::connect();
		try {
			$sql = "
                SELECT
                    MAX({$colName}) + 1 as id
                FROM
                    $table
                ";
			$sth = $dbh->prepare($sql);
			$sth->execute();
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $data[0]['id'];
	}

    /**
     * INT型指定でデータ取得
     */
    public static function selectRowsById($table, $colValue, $colName = 'id') {
        return self::selectRows($table, $colValue, $colName, 'int');
    }

    /**
     * STRING型指定でデータ取得
     */
    public static function selectRowsByString($table, $colValue, $colName) {
        return self::selectRows($table, $colValue, $colName, 'string');
    }

    /**
     * ソート順指定で1データ取得
     * TODO：動作未確認
     */
    public static function selectOneBySort($table, $id, $colName, $sortType = 'string') {
        return self::selectRows($table, null, null, null, $colName, $sortType, 1);
    }

    /**
     * Select 条件が単純なSQLでのデータ取得
     */
    private static function selectRows($table, $colValue, $colName, $type
        , $sortColName = null, $sortType = 'DESC', $limit = null) {

		$wherePh = ($colName ? " WHERE $colName = :colValue " : '');
		$sortPh = ($sortColName ? " ORDER BY :sortColName $sortType" : '');	
		$limitPh = ($limit ? " LIMIT :limit " : '');
		
		$dbh = self::connect();
        try {
            $sql = "
                SELECT
                    *
                FROM
                    $table
                $wherePh
                $sortPh
                $limitPh
            ";
            $sth = $dbh->prepare($sql);

            if ($colName) {
                if ($type == 'int') {
                    // PARAM_INT を指定してもINTへのキャストをしないと何故かエラーになるので注意！
                    $sth->bindValue(":colValue", intval($colValue), PDO::PARAM_INT);
                } else {
                    // PDO::PARAM_STR はなくても可。
                    $sth->bindValue(":colValue", $colValue, PDO::PARAM_STR);
                }
            }
            if ($sortColName) {
                $sth->bindValue(":sortColName", $sortColName, PDO::PARAM_STR);
            }
            if ($limit) {
                $sth->bindValue(":limit", intval($limit), PDO::PARAM_INT);
            }
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
	 * 機能：DB新規登録処理
	 *
	 * 備考：
	 *
	 * @param    $dbh            ：
	 * @param    $table          ：
	 * @param    $paramUpdate    ：
	 * 戻り値                  	：true／false
	 *
	 */
    public static function pdoInsert($table, $paramInsert) {
        $dbh = self::connect();
        try {
        	// パラメータ調整		
			self::formatSqlParam($paramInsert);
			
            // SQL文の組立て
            $insertKeys = '';
            $insertValues = '';
            foreach($paramInsert as $key => $value) {
                $insertKeys .= "`{$key}`, ";
                $insertValues .= ":{$key}, ";
            }
            $insertKeys = rtrim($insertKeys, ' ,');
            $insertValues = rtrim($insertValues, ' ,');
            $sql = "INSERT INTO $table ($insertKeys) VALUES ($insertValues)";

            //// 原文の例
            //$sql = "
            //    INSERT INTO
            //        news
            //        (
            //             title
            //            ,detail
            //            ,img
            //            ,thumbnail
            //            ,movie
            //            ,startTime
            //            ,endTime
            //            ,updTime
            //            ,insTime
            //        )
            //    VALUES
            //        (
            //             :title
            //            ,:detail
            //            ,:img
            //            ,:thumbnail
            //            ,:movie
            //            ,:startTime
            //            ,:endTime
            //            ,:updTime
            //            ,:insTime
            //        )
            //    ";
            $sth = $dbh->prepare($sql);
            // バインドの組立て
            foreach($paramInsert as $key => $value) {
                $sth->bindValue(":$key", $value['value'], $value['type']);
           }
			// NOT NULL 制約があっても登録自体は成功してしまうので注意！
			// 適当な初期値でDBに追加されるみたい。
            $result = $sth->execute();
        } catch (PDOException $e) {
            showError($e);
            throw $e;
        }
        return $result;
    }

	/**
	 *
	 * DB更新処理
	 *
	 * @param    $table         ：テーブル名
	 * @param    $paramUpdate   ：データ更新するカラム名と値のリスト
	 * @param    $paramWhere    ：WHERE句に指定する条件。
	 * 							：WHERE 文が AND のみの構成以外使えないので注意。
	 * 							：条件にマッチするものがなくても true が返るので注意。
	 * 戻り値                  	：true 更新件数
	 *                    		：false 処理失敗
	 *
	 */
    public static function pdoUpdate($table, $paramUpdate, $paramWhere) {
        $dbh = self::connect();
        try {
        	// パラメータ調整		
			self::formatSqlParam($paramUpdate);
			
            // SQL文の組立て
            $PhSet = '';
            foreach($paramUpdate as $key => $value) {
                $PhSet .= $key . ' = :' . $key .', ';
            }
            // $PhSet = rtrim($PhSet, ', ');                        // これでもOK
            $PhSet = preg_replace("/, $/" , "", $PhSet);

            $PhWhere = '';
            foreach($paramWhere as $key => $value) {
	        	// バインド指定の先頭に「where_」を付加する点に注意！
	        	//     $paramUpdate 側と項目名が同じ場合、どちらか一方が使われてしまうため。
				$PhWhere .= " {$key} = :where_{$key} and ";	
            }
            $PhWhere = preg_replace("/ and $/" , "", $PhWhere);

            $sql = "UPDATE $table SET $PhSet WHERE $PhWhere";
            $sth = $dbh->prepare($sql);

            // バインドの組立て
            foreach($paramUpdate as $key => $value) {
                $sth->bindValue(":$key", $value['value'], $value['type']);
           }
            foreach($paramWhere as $key => $value) {
				// 先頭に「where_」を付加する点に注意！
		        $sth->bindValue(":where_{$key}", $value['value'], $value['type']);
           }
            $result = $sth->execute();
            // 更新した行数取得
            $rowCount = $sth->rowCount();
        } catch (PDOException $e) {
            showError($e);
            throw $e;
        }
		if ($result) {
        	// where 条件にマッチするものがなくても $result == true が返るので注意。
            // 処理成功の場合、$rowCount を返す。
            $result = $rowCount;
        }
        return $result;
		//// 処理原型の例
		//	$sql = "
		//	    UPDATE
		//	        news
		//	    SET
		//	         title          = :title
		//	        ,detail         = :detail
		//	        ,img            = :img
		//	        ,thumbnail      = :thumbnail
		//	        ,movie          = :movie
		//	        ,startTime     = :startTime
		//	        ,endTime       = :endTime
		//	        ,updTime       = :updTime
		//	    WHERE
		//	        id = :id";
		//
		//	// datetime 型の設定
		//	$startTime = self::formatSqlTime($param['startTime']);
		//	$endTime = self::formatSqlTime($param['endTime']);
		//
		//	$sth = $dbh->prepare($sql);
		//	$sth->bindValue(':title',       $param['title']);
		//	$sth->bindValue(':detail',      $param['detail']);
		//	$sth->bindValue(':img',         $param['img']);
		//	$sth->bindValue(':thumbnail',   $param['thumbnail']);
		//	$sth->bindValue(':movie',       $param['movie']);
		//	$sth->bindValue(':startTime',   $startTime, is_null($startTime) ? PDO::PARAM_NULL : PDO::PARAM_STR);
		//	$sth->bindValue(':endTime',     $endTime, is_null($endTime) ? PDO::PARAM_NULL : PDO::PARAM_STR);
		//	$sth->bindValue(':updTime',     date("Y-m-d H:i:s"));
		//	$sth->bindValue(':id',          intval($param['id']), PDO::PARAM_INT);
		//
		//	$result = $sth->execute();
    }

	/**
	 *
	 * DB登録・更新処理
	 *
	 * 備考：既にあれば更新、なければ新規登録。更新時は insTime を除外する。
	 *
	 * $table         ：テーブル名
	 * $paramUpsert   ：データ登録・更新するカラム名と値のリスト
	 *                ：プライマリキーに指定した値を持つ行があれば更新、なければ新規登録する。
	 *                ：更新時は insTime を除外する点に注意。
	 *
	 * $result		  ：常に true が返る？
	 *
	 */
    public static function pdoUpsert($table, $paramUpsert) {
        $dbh = self::connect();
        try {

            // パラメータ調整
            self::formatSqlParam($paramUpsert);

            // SQL文の組立て
            $insertKeys = '';
            $insertValues = '';
            foreach($paramUpsert as $key => $value) {
                $insertKeys .= $key .', ';
                $insertValues .= ':' . $key .', ';
            }
            $insertKeys = rtrim($insertKeys, ' ,');
            $insertValues = rtrim($insertValues, ' ,');

            $PhSet = '';
			foreach($paramUpsert as $key => $value) {
				if ($key  === 'insTime') {
					// insTime は更新しない
					$PhSet .= $key . ' = IF(insTime is null, :insTime, insTime), ';
				} elseif (isset($value['option'])) {
					// UPDATEに既存値を使う場合のフレーズ追加用
					//     例：IF(amount is null, :amount, amount + :amount)
					$PhSet .= $key . ' = ' . $value['option'].', ';
				} else {
					$PhSet .= $key . ' = :' . $key .', ';
				}
			}
            $PhSet = preg_replace("/, $/" , "", $PhSet);

            // UPSERT
            //     INSERT INTO foo (code, name) VALUES ('0001', 'abc') ON DUPLICATE KEY UPDATE code='0001', name='def';
      		$sql = "INSERT INTO $table ($insertKeys) VALUES ($insertValues) ON DUPLICATE KEY UPDATE $PhSet";
           	$sth = $dbh->prepare($sql);

            // バインドの組立て
            foreach($paramUpsert as $key => $value) {
                $sth->bindValue(":$key", $value['value'], $value['type']);
           }
            $result = $sth->execute();
        } catch (PDOException $e) {
            showError($e);
            throw $e;
        }
        return $result;
    }

	/**
	 *
	 * 機能：DBデータ削除
	 *
	 * 備考：
	 *
	 * @param    $table      	 ：テーブル名
	 * @param    $paramWhere     ：WHERE句に指定する条件のリスト
	 * @param    $conjunction    ：WHERE句の接続詞（and／or）
	 *
	 */
	public static function pdoDelete($table, $paramWhere, $conjunction = 'and') {
        $dbh = self::connect();
        try {
            $PhWhere = '';
            foreach($paramWhere as $key => $value) {
				$PhWhere .= $key . ' = :' . $key . " {$conjunction} ";
            }
			$PhWhere = preg_replace("/ {$conjunction} $/" , "", $PhWhere);
            $sql = "DELETE FROM $table WHERE $PhWhere";
            $sth = $dbh->prepare($sql);
            foreach($paramWhere as $key => $value) {
                $sth->bindValue(":$key", $value['value'], $value['type']);
            }
            $result = $sth->execute();
        } catch (PDOException $e) {
            showError($e);
            throw $e;
        }
        return $result;
    }

	/**
	 *
	 * LIKE条件を指定して削除
	 *
	 */
	public static function pdoDeleteByLike($key, $filter) {
		$dbh = self::connect();
		try {
			$sql = " DELETE FROM Result WHERE {$key} like :{$key} ";
			$sth = $dbh->prepare($sql);
			$sth->bindValue(":{$key}", "'{$filter}'", PDO::PARAM_STR);
			$sth->execute();
			$result = $sth->execute();
		} catch (PDOException $e) {
			showError($e);
			throw $e;
		}
		return $result;
	}

	/**
    *
    * データ更新値の設定
    *
    *     日時型でデータなしならNULLをセット
    *     INT型、STRING型でデータなしならNULLをセット   
    *
    */
    private static function formatSqlParam(&$param) {
	    // 日時の場合、値がなければ value に NULL、type に PDO::PARAM_NULL を設定
	    // 日時の場合、値があれば type に PDO::PARAM_STR を設定（日時以外のNULL値はそのままで問題ない。）
	    foreach($param as $key => $value) {
	      	if ($value['type'] == 'datetime') {
	        	// 日時型データの場合
	        	//     datetime 型としてバインド可能なフォーマットに整形
		        if ($value['value'] == '' or $value['value'] == null) {
		         	$param[$key]['value'] = null;
		       		$param[$key]['type'] = PDO::PARAM_NULL;
		        } else {
		            // Postgre と違って "Y/m/d H:i:s" でも自動整形されるみたいたが念のため。
					$param[$key]['value'] = date("Y-m-d H:i:s", strtotime($value['value']));
					$param[$key]['type'] = PDO::PARAM_STR;
		        }
	    	} elseif (strval($value['value']) === '') {
				// 日時型以外で、値なしの場合、type を PDO::PARAM_NULL に変更
				// INTで0の場合は0を設定する点に注意！
	         	$param[$key]['value'] = null;
	       		$param[$key]['type'] = PDO::PARAM_NULL;
			}
	    }
    }
	
	/**
	 * 
	 * IN演算子のリスト作成
	 * 
	 * リストから作成する。
	 *
	 * @param    $keyList		：IN に指定するデータ値のリスト
	 * @param    $IsString		：データ型（true：文字列型／false：INT型）
	 * @param    trimSingleQuote：シングルクウォート削除
	 * 
	 */
	public static function makePhInFromList($keyList, $IsString = false, $trimSingleQuote = true) {
		$phIn = null;
		if ($trimSingleQuote) {
			foreach ($keyList as $key => $value) {
				// シングルクウォート削除（IN句のプレースホルダ化が面倒なので、ここで最低限のチェック）
				$keyList[$key] = preg_replace("/'/i", '', $value); 	
			}
		}
		// 文字列はシングルクウォートで囲む
		$quote = ($IsString ? "'" : "");
		foreach ($keyList as $key => $value) {
			$phIn .= "{$quote}{$value}{$quote},";
		}
		$phIn = rtrim($phIn, ',');
		return $phIn;
	}
	
	/**
	 * 
	 * IN演算子のリスト作成
	 * 
	 * スペース区切りの文字列から作成する。
	 *
	 * @param    $keyword		：キーワード
	 * @param    $IsString		：データ型（true：文字列型／false：INT型）
	 * 
	 */
	public static function makePhInFromString($keyword) {
		// 検索ワードのリスト化
		$keyword = formatSpace($keyword);		// 入力文字列の整形
		$keyList = explode(' ', $keyword);		// スペースで配列化
		return self::makePhInFromList($keyList);;
	}
	/**
	 * 
	 * 日時条件指定のSQL作成
	 * 
	 * $tableName		：テーブル名
	 * $param			：日時条件指定するキー：searchDayFrom、searchDayTo と値からなるリスト。
	 * $colTime			：日時条件指定するカラム名
	 * 
	 */	 
	public static function makeSqlTimeFromTo($tableName, $param, $colTime) {
		$phAnd = null;
		foreach ($param as $key => $value) {
			if ($value){
				if ($key === "{$colTime}From"){
					// 日時フォーマットチェック
					// if(strptime($value, '%Y/%m/%d %H:%M')){
					if(strptime($value, '%Y/%m/%d')){		
						$phAnd .= " and $tableName.$colTime >= '$value' ";
					}
				} elseif ($key === "{$colTime}To"){
					// 日時フォーマットチェック
					// if(strptime($value, '%Y/%m/%d %H:%M')){
					if(strptime($value, '%Y/%m/%d')){	
						$phAnd .= " and $tableName.$colTime < '$value' ";	
					}
				}
			}		
		}
		return $phAnd;
	}
	
	/**
	 * 日付条件指定のSQL作成
	 * 
	 * @param    $tableName		：テーブル名
	 * @param    $param			：日付条件指定するキー：searchDayFrom、searchDayTo と値からなるリスト。
	 * @param    $colTime		：日付条件指定するカラム名
	 * 
	 */	 
	public static function makeSqlDayFromTo($tableName, $param, $colTime) {
		$phAnd = null;
		foreach ($param as $key => $value) {
			if ($value){
				if ($key === 'searchDayFrom'){
					// 日付フォーマットチェック
					if(strptime($value, '%Y/%m/%d')){	
					 	// $phAnd .= " and $tableName.$colTime >= :$key ";    // bindValue の場合
						$phAnd .= " and $tableName.$colTime >= '$value' ";
					}
				} elseif ($key === 'searchDayTo'){
					// 日付フォーマットチェック
					if(strptime($value, '%Y/%m/%d')){
						// 指定日の1日後
						//     日付で同日だと同じ日の0時0分以上・以下になるので1日後に変更
						// $phAnd .= " and $tableName.$colTime < DATE_ADD(:$key, INTERVAL 1 DAY) ";    // bindValue の場合
						$phAnd .= " and $tableName.$colTime < DATE_ADD('$value', INTERVAL 1 DAY) ";	
					}
				}
			}		
		}
		return $phAnd;
	}

    /**
     *
     * 配列のバインド
     *
     * 検索条件のバインド等で頻用。
     *
     * 用例：
     *      // パラメータ設定
     *      if (isset($param['keyword']) && $param['keyword']) {
     *          // LIKE指定
     *          $phAnd .= " and keyword like :keyword ";
     *          $bind['keyword']['value'] = $param['keyword'];
     *          $bind['keyword']['type'] = 'like';
     *
     *          // PARAM_STR指定（datetime含む）
     *          $phAnd .= " and keyword = :keyword ";
     *          $bind['keyword']['value'] = $param['keyword'];
     *          $bind['keyword']['type'] = PDO::PARAM_STR;
     *     }
     *
     *     if (isset($param['keyNumber']) && $param['keyNumber']) {
     *          // PARAM_INT指定
     *          $phAnd .= " and keyNumber = :keyNumber ";
     *          $bind['keyNumber']['value'] = $param['keyNumber'];
     *          $bind['keyNumber']['type'] = PDO::PARAM_INT;
     *     }
     *
     *      // データベース呼出
     *      public function getData($bind, $phAnd) {
     *              ：
     *          $sth = $dbh->prepare($sql);
     *          if ($bind) Db::bindValueList($sth, $bind);
     *          $result = $sth->execute();
     *              ：
     */	
	public static function bindValueList($sth, $param) {
		if (!$param) return;
		foreach ($param as $key => $value) {
			if ($value['type'] === 'like'){
				// LIKEの条件指定
				$sth->bindValue(":{$key}", "%{$value['value']}%", PDO::PARAM_STR);
			} else {
				$sth->bindValue(":{$key}", $value['value'], $value['type']);	
			}		
		}
		return;	
	}	

	/**
	 *
	 * キーを変更
	 *
	 * $keyName で配列化する。
	 *
	 */
	public static function changeKey($data, $keyName) {
		$resultData = array();
		foreach ($data as $value) {
			$resultData[$value[$keyName]] = $value;
			unset($resultData[$keyName]);
		}
		return $resultData;
	}

    /**
	 * 
	 * 行番号を追加
	 * 
	 * @param    &$data			：対象データ
	 * @param    $page			：ページ番号
	 * 
     */
    public static function addSortNo(&$data, $page) {
		$pageNo = $page['curPageNo'];
		$countPerPage = $page['countPerPage'];
		if ($pageNo === 1) {
			$sortNo = 1;					
		} else {	
			$sortNo = ($pageNo-1)*$countPerPage + 1;	
		}
		foreach ($data as $key => $value) {
			$data[$key]['sortNo'] = $sortNo;
			$sortNo++;
		}
	}

	/**
	 *
	 * テーブル初期化
	 *
	 */
	public static function truncate($tableName) {
		$sql = " TRUNCATE {$tableName} ";
			$dbh = self::connect();
			try {
			$sth = $dbh->prepare($sql);
			$sth->execute();
		} catch (PDOException $e) {
			showError($e);
			return false;
		}
		return true;
	}
}

























