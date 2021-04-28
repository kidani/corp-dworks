/*!
 * 共通処理
 *
 * @author     : kidani@wd-valley.com
 * @copyright  : Wood Valley Co., Ltd.
 *
 */

/*-------------------------------------------------
 ユーティリティー
-------------------------------------------------*/
// function getParamList()
// function getParam(paramKey)
// function buildQuery(paramList, delKey)
// function getQuery(delKey)
// function serverLog(detail, title, file)
// function hasZenkaku(str)
// function countCharLength(str)
// function jstrlen(str)
// function getPathInfo(filePath)
// function getDirName(path)
// function getBaseName(path)
// function getFileName(path)
// function getExtName(path)
// function replaceExtName(path, ext)
// function deleteNameTail(fname, strDel)
// function addNameTail(fname, strAdd)
// function getImagePath(objImage, incParam)
// function replaceLink(atags, srcString, dstString)

// GETパラメータを配列化
function getParamList() {
    var parameter = location.search;
    parameter = parameter.substring( 1, parameter.length );
    // URIデコード
    var arr = decodeURIComponent( parameter ).split('&');
    var params = {};
    for (var n in arr) {
        if (arr[n].match(/^([^=]+)=(.*)$/)) {
            params[RegExp.$1] = RegExp.$2;
        }
    }
    return params;
}

// 指定されたキーのパラメータ値を取得
function getParam(paramKey) {
    var paramList = getParamList();
    if (paramList[paramKey]) {
        return paramList[paramKey];
    }
}

// 配列からクエリ文字列を生成
function buildQuery(paramList, delKey) {
    var arr = $.extend(true, {}, paramList);
    if (delKey) {
        // 除外するパラメータありの場合
        if (arr[delKey]) {
            delete arr[delKey];
        }
    }
    return $.param(arr);
}

// 指定パラメータを除いたクエリ取得
function getQuery(delKey) {
    var query = location.search;
    query = query.substring(1);   // 「?」の除去
    if (delKey) {
        reg = new RegExp('&*' + delKey + '=[^&]+', 'g');
        query = query.replace(reg, "");
    }
    // 先頭の&除去
    reg = new RegExp('^&');
    query = query.replace(reg, "");
    return query;
}

/**
 *
 * サーバログ出力
 * 	   detail：詳細
 *     title：タイトル
 *     file：ファイル名
 * 用例
 *     serverLog(param, 'パラメータエラー', 'check.log')
 *
 */
function serverLog(detail, title, file) {
    var response;

    $.ajax({
        url: "/?p=ServerLog",
        type: "POST",
        data: {
            "detail": detail
            , "title": title
            , "file": file
        },
        dataType: "json",
        async: true,
        timeout: 10000
    }).done(function(data, status, xhr) {
        if (!data) {
            console.error("serverLog実行エラー");
        }
        response = data;
    }).fail(function(xhr, status, error) {
        console.error("serverLog実行エラー（通信エラー）");
    }).always(function(arg1, status, arg2) {
    });
    return response;
}

/**
 *
 * 全角文字を含むかチェック
 *
 */
function hasZenkaku(str) {
    var len = 0;
    str = escape(str);
    for (i = 0; i < str.length; i++, len++) {
        if (str.charAt(i) === "%") {
            if (str.charAt(++i) === "u") {
                // 全角文字なら先頭が「%uXXXX」になる。
                return true;
            }
            i++;
        }
    }
    return false;
}

/**
 *
 * 全角カタカナのみかチェック
 *
 */
function isZenkakuKanaOnly(str){
    if (!str) return false;
    if (str.match(/^[ァ-ヶー]*$/)){    //"ー"の後ろの文字は全角スペースです。
        return true;
    } else {
        return false;
    }
}

/**
 *
 * 文字数カウント
 *
 * 文字数（全角：2、半角：1で計算）
 *
 */
function countCharLength(str) {
    var biteCnt = 0;
    for (var i in str) {
        // 文字のコード取得
        var c = str.charCodeAt(i);
        if ( (c >= 0x0 && c < 0x81) || (c === 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
            // Unicode の半角文字
            biteCnt += 1;
        } else {
            biteCnt += 2;
        }
    }
    return biteCnt;
}

/**
 *
 * 文字数カウント
 *
 * countCharLength よりこちらが正確かも。
 * 文字数（全角：2、半角：1で計算）
 *
 */
function jstrlen(str) {
    var len = 0;
    str = escape(str);
    for (i = 0; i < str.length; i++, len++) {
        if (str.charAt(i) === "%") {
            if (str.charAt(++i) === "u") {
                // 全角文字なら先頭が「%uXXXX」になる。
                i += 3;
                len++;
            }
            i++;
        }
    }
    return len;
}

// 先頭・末尾の連続する「半角空白・タブ文字・全角空白」を削除
String.prototype.jtrim = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, "");
};

// 先頭の連続する「半角空白・タブ文字・全角空白」を削除
String.prototype.ltrim = function() {
    return this.replace(/^[\s　]+/g, "");
};

// 末尾の連続する「半角空白・タブ文字・全角空白」を削除
String.prototype.rtrim = function() {
    return this.replace(/[\s　]+$/g, "");
};

// 改行を削除
String.prototype.lftrim = function() {
    return this.replace(/\r?\n/g, '');
};

/**
 *
 * ファイルのフルパスを分離（PHPのpathinfo関数に該当）
 *
 * ファイルのフルパス、URLから、ディレクトリ名、ファイル名、
 * ファイル名（拡張子除く）、ファイル拡張子を取得
 *
 */
function getPathInfo(filePath) {
    var pathParts = [];
    pathParts.dirName = filePath.substring(0, filePath.lastIndexOf('/')+1);
    pathParts.baseName = filePath.substring(filePath.lastIndexOf("/")+1, filePath.length);
    pathParts.filename = pathParts.baseName.substring(0, pathParts.baseName.indexOf("."));
    pathParts.extension = pathParts.baseName.substring(pathParts.baseName.lastIndexOf(".")+1, pathParts.baseName.length);
    return pathParts;
}

/**
 * ディレクトリ名を取得
 */
function getDirName(path) {
    var pathParts = getPathInfo(path);
    return pathParts['dirName'];
}
/**
 * ファイル名を取得
 */
function getBaseName(path) {
    var pathParts = getPathInfo(path);
    return pathParts['baseName'];
}
/**
 * 拡張子を除いたファイル名を取得
 */
function getFileName(path) {
    var pathParts = getPathInfo(path);
    return pathParts['fileName'];
}

/**
 * 拡張子を取得
 */
function getExtName(path) {
    var pathParts = getPathInfo(path);
    return pathParts['extension'];
}

/**
 * 拡張子を変更
 */
function replaceExtName(path, ext) {
    dir = getDirName(path);
    file = getFileName(path);
    return dir + file + '.' + ext;
}

/**
 * 指定文字をファイル名末尾から削除
 */
function deleteNameTail(fname, strDel) {
    // パラメータ付きなら分割
    var arr = fname.split('?');
    // 固定文字列「_s」等指定なら replace で処理可能
    // str = fname.replace(/^(.*)_s(\.\w+)$/g, "\$1\$2");
    // メタキャラクタを事前にエスケープ（これ重要！）
    strDel = strDel.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
    reg = new RegExp(strDel + '(.\\w+)$');
    return arr[0].replace(reg, "$1");
}

/**
 * 指定文字をファイル名末尾に追加
 */
function addNameTail(fname, strAdd) {
    // パラメータ付きなら分割
    var arr = fname.split('?');
    // メタキャラクタを事前にエスケープ（これ重要！）
    strAdd = strAdd.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
    reg = new RegExp('(.\\w+)$');
    return arr[0].replace(reg, strAdd + "$1");
}

/**
 * 画像の相対パス取得
 */
function getImagePath(objImage, incParam) {
    var srcPath = objImage.attr('src');
    var arr = [];
    if (!incParam) {
        // パラメータを除去する場合
        arr = srcPath.split('?');
    } else {

        arr[0] = srcPath;
    }
    return arr[0];
}

/**
 *
 * リンクURL置換
 *
 * a > href の特定文字列を置換
 *
 * atags: a タグのリスト
 *
 */
function replaceLink(atags, srcString, dstString) {
    $.each(atags, function(index, anchor) {
        // atags.attr('href'); は有効だが、anchor.attr('href'); は
        // 何故か not available なので anchor.href を直接変更した。
        var curLink = anchor.href;
        anchor.href = curLink.replace(srcString, dstString);
    });
}

/*-------------------------------------------------
 日時
-------------------------------------------------*/
/**
 *
 * 日時表示をフォーマット
 *
 * YYYY/MM/DD HH:MM に変換。
 *
 */
function formatDate(date) {
    var year = date.getFullYear();                          // 年
    var month = ('0' + (date.getMonth() + 1)).slice(-2);    // 月
    var day = ('0' + date.getDate()).slice(-2);             // 日
    //var week = date.getDay();                             // 曜日
    //var weekNames = ['日', '月', '火', '水', '木', '金', '土'];
    //return year + '年' + month + '月' + day + '日(' + weekNames[week] + ')';  // 2016年04月30日(土)
    var hour = ('0' + date.getHours()).slice(-2);           // 時
    var min = ('0' + date.getMinutes()).slice(-2);          // 分
    // return year + '/' + month + '/' + day;                                   // 2016/04/30
    return year + '/' + month + '/' + day + ' ' + hour + ':' + min;         // 2016/04/30 09:28
}

/*-------------------------------------------------
 バリデーション
-------------------------------------------------*/
// function wvValidateForm(parts)                               ：フォームに含まれる複数要素の入力値バリデーション（中で wvValidate をループさせるためのラッパー）
// function wvValidate(elemNameVal, chkItem, checkCallback)     ：特定のname属性を持つ要素の値をチェック
// function addValidateEvent(parts, checkCallback)              ：バリデーションイベント追加（入力値変更監視）
// function deleteValidateEvent(parts)                          ：バリデーションイベント削除
// function checkChangeValue(element, chkItem, checkCallback)   ：テキスト入力変更時処理
// function mailAddrCheck(mailAddress)                          ：メールアドレス入力値チェック
// function validateStartEndTime()                              ：開始～終了時間の論理チェック
// function setErrorMessage(obj, message)                       ：エラーメッセージ表示
// function clearErrorMessage(obj)                              ：エラーメッセージ削除
// function wvValidateCheckbox(parts)                           ：チェクボックス選択値変更時処理 TODO：不要？

/**
 *
 * フォーム入力値のバリデーション
 *
 * parts            ：チェックするフォームの name 属性値とチェックする項目名のリスト。
 *     項目名の詳細については wvValidate 参照。
 *    ＜例＞
 *    var parts =
 *        {
 *              namae		: ['require', 'max20']							// 名前
 *            , mailAddress	: ['require', 'email', 'max66']					// メールアドレス
 *            , detail		: ['require', 'max500']  						// 内容
 *            , tel			: ['numOnly', 'min10', 'max11']                 // 電話番号
 *        };
 *
 * checkCallback   ：コールバック関数
 *     parts の形式に対応していないチェック処理の場合、コールバック関数にてチェック処理を行う。
 *     例：つりフリのクレジットカード登録バリデーションで、addValidateEvent(parts, checkYM);
 *         により wvValidateForm にコールバック関数を渡している。
 *
 */
function wvValidateForm(parts, checkCallback) {
    // 全部品のチェック
    $result = true;
    $.each(parts, function(key, chkItem) {

        if (!wvValidate(key, chkItem, checkCallback)) {
            $result = false;
            console.log('チェック結果失敗：' + key + ' - ' + chkItem);
        } else {
            console.log('チェック結果成功：' + key + ' - ' + chkItem);
        }
    });
    return $result;
}

/**
 *
 * 特定のname属性を持つ要素の値をチェック
 *
 * elemNameVal	    ：name属性名（例：name="nameVal"）
 * chkItem		    ：チェックする項目名の配列（require、max20等）
 *     require          ：必須
 *     maxNN            ：最大文字数
 *     minNN            ：最小文字数
 *     fixNN            ：固定文字数
 *     numOnly          ：半角数字
 *     numDotOnly       ：半角数字、小数点
 *     numEnOnly        ：半角英数字
 *     numRangeMinNN	：最小数値
 *     numRangeMaxNN	：最大数値
 *     zenKanaOnly      ：全角カナ
 *     email            ：Eメール
 *     year             ：年（未対応）
 *     month            ：月（1～12）
 * checkCallback   ：コールバック関数
 *     例：つりフリのクレジットカード登録バリデーションで、addValidateEvent(parts, checkYM);
 *         により wvValidate にコールバック関数を渡している。
 *
 * 戻り値              	：true／false
 *
 */
function wvValidate(elemNameVal, chkItem, checkCallback) {

    var message;
    var target = $('[name=' + elemNameVal + ']');
    if (!target.length) {
        // チェックボックスの場合配列
        target = $("[name='" + elemNameVal + "[]']");
    }

    // 要素の種類判別
    // target.attr('name')	        ：name属性
    // target.prop('tagName')	    ：要素名（select, textarea はこれで判定）
    if (!target.length) {
        // serverLog(chkItem, '評価対象要素なし検知');
        // parts に登録した要素が場合により不要で非表示の場合もありそうなので、
        // 処理としては正常終了にしておく。
        return true;
    }

    $.each(chkItem, function(key, item) {
        // item：require、max20 等の各チェック項目

        // フォーム部品のタイプ取得
        var partsType;
        if (target[0]) {
            // target.attr('type')  ：type属性
            // target[0].type       ：フォーム部品のタイプ？（text, textarea, select-one ... など type 属性がない textarea なども含む）
            partsType = target[0].type;
        }
        var targetVal;
        var targetLen;
        if (partsType === 'text' || partsType === 'textarea'|| partsType === 'password' || partsType === 'number') {
            targetVal = target.val();
            // 先頭スペース削除
            targetVal = targetVal.ltrim();
            // 末尾スペース削除
            // 末尾スペースは入力したい場合もありそうなのでとりあえず制限しない！
            // if (partsType !== 'textarea') {
            //     // textarea では改行が削除されるので除外
            //     targetVal = targetVal.rtrim();
            // }
            target.val(targetVal);
            targetLen = targetVal.length;
        } else if (partsType === 'select-one') {
            // セレクトボックス
            targetVal = target.val();
        } else if (partsType === 'checkbox') {
            // チェックボックス
            // var checkCount = $("[name='" + elemNameVal + "[]']:checked").length;     // 選択されたチェック数
            // targetVal = target.prop('checked');      // NG：これだと先頭のチェックが選択されたかどうかのみ取得
            // targetVal = target.attr('checked');      // これなら true／false は取得可能
            // targetVal = target.is(':checked');       // これなら true／false は取得可能
            // 配列のまま取得する場合 → 後処理が面倒になるので不採用
            // targetVal = [];
            // var $chkList = $("[name='" + elemNameVal + "[]']:checked");
            // $chkList.each(function(key, val){
            //     targetVal.push(val);        // チェックONの値のみセット
            // });
            // チェックされた最初の値だけ取得（後処理が楽なのでこれを採用）
            var targetVal;
            $targetCheck = $("[name='" + elemNameVal + "[]']");
            if ($targetCheck.length) {
                // 配列の場合
                targetVal = $("[name='" + elemNameVal + "[]']:checked").val();
            } else {
                // 単体の場合
                $targetCheck = $("[name='" + elemNameVal + "']");
                targetVal = $("[name='" + elemNameVal + "']:checked").val();
            }
        } else if (partsType === 'radio') {
            // ラジオボタン
            targetVal = $('[name=' + elemNameVal + ']:checked').val();
            if (targetVal === undefined) {
                // 未選択の場合 undefined になる。
                targetVal = null;
            }
        } else  {
            serverLog(target, 'フォーム部品のタイプ不正検知');
        }
        if (item === 'require') {
            // 必須チェック
            //     IE、Safari が required 属性に未対応なので必要。
            if (!targetVal) {
                message = '必須入力です';
                setErrorMessage(target, message);
                return false;   // each からの break のみ
            }
        } else if (!targetVal) {
            return false;
        }
        var regMatch = null;
        if (regMatch = item.match(/^max([0-9]+)$/)) {
            // 文字数チェック（最大値）
            var maxCount = parseInt(regMatch[1]);
            if(targetLen > maxCount) {
                message = maxCount + '文字以内で入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (regMatch = item.match(/^min([0-9]+)$/)) {
            // 文字数チェック（最小値）
            var minCount = parseInt(regMatch[1]);
            if(targetLen < minCount) {
                message = minCount + '文字以上で入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (regMatch = item.match(/^fix([0-9]+)$/)) {
            // 文字数チェック（既定値）
            var fixCount = parseInt(regMatch[1]);
            if(targetLen !== fixCount) {
                message = fixCount + '文字で入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (item === 'email') {
            // メールアドレスのフォーマットチェック
            // if (!targetVal.match(/^[A-Za-z0-9]+[\w-]+@[\w\.-]+\.\w{2,}$/)) {
            if (!mailAddrCheck(targetVal)) {
                message = 'メールアドレスの形式が不正です。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (item === 'numOnly') {
            // 半角数字
            if (!targetVal.match(/^(?:[0-9]+)*$/)) {
                message = '半角数字のみで入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (item === 'numDotOnly') {
            // 半角数字と小数点
            if (!targetVal.match(/^(?:[.0-9]+)*$/)) {
                message = '半角数字と小数点のみで入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (item === 'numEnOnly') {
            // 半角英数字
            if (!targetVal.match(/^(?:[A-Za-z0-9]+)*$/)) {
                message = '半角英数字のみで入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }

        if (regMatch =item.match(/^numRangeMin([0-9]+)$/)) {
            // 数値チェック（最小値）
            var minNum = parseInt(regMatch[1]);
            if(parseInt(targetVal) < minNum) {
                message = minNum + '以上を入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }

        if (regMatch =item.match(/^numRangeMax([0-9]+)$/)) {
            // 数値チェック（最大値）
            var maxNum = parseInt(regMatch[1]);
            if(parseInt(targetVal) > maxNum) {
                message = maxNum + '以下を入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
        if (item === 'year') {
            // 年チェック
        }
        if (item === 'month') {
            // 月チェック
            if(parseInt(targetVal) < 1 || parseInt(targetVal) > 12) {
                message = '月が不正です。';
                setErrorMessage(target, message);
                return false;
            }
        }
        // zenKanaOnly
        if (item === 'zenKanaOnly') {
            // 全角カナチェック
            if(!isZenkakuKanaOnly(targetVal)) {
                message = '全角カナのみで入力して下さい。';
                setErrorMessage(target, message);
                return false;
            }
        }
    });
    // コールバック関数処理
    if (!message && checkCallback) {
        // name 属性値を渡す
        // エラー表示はコールバック関数側で行う。
        message = checkCallback(elemNameVal, message);
    }
    if (message) {
        // 1件でもエラーなら false を返す。
        return false;
    } else {
        // エラー表示クリア
        clearErrorMessage(target);
    }
    return true;
}

/**
 *
 * フォームのイベント登録
 *
 * blur, change, keyup のバリデーションイベント登録
 *
 * parts            ：name 属性、対応するチェックする項目名の配列（require、max20等）
 * checkCallback   ：コールバック関数
 *     例：つりフリのクレジットカード登録バリデーションで、addValidateEvent(parts, checkYM);
 *         により wvValidateForm にコールバック関数を渡している。
 *
 */
function addValidateEvent(parts, checkCallback) {
    //------------------------------------------------
    // イベント登録
    //------------------------------------------------
    $.each(parts, function(key, chkItem) {
        var $target = $('[name=' + key + ']');
        if (!$target.length) {
            // 複数チェックボックス対策
            $target = $("[name='" + key + "[]']");
        }
        if (!$target.length) return;    // なければ除外

        //------------------------------------------------
        // blur, change イベント
        //------------------------------------------------
        // テキストボックス：フォーカスアウトで発生
        // セレクトボックス：選択確定時に発生
        // チェックボックス：チェック変更時に発生
        $target.on('blur change ', function() {
            // 各部品のチェック
            if (wvValidate(key, chkItem, checkCallback)) {
                // 問題なしの場合
            }
        });
        //------------------------------------------------
        // keyup イベント
        //------------------------------------------------
        // テキストボックス、テキストエリアの場合のみ登録
        // テキストの変更をリアルタイムで監視する。
        var partsType = $target[0].type;        // var attrType = $target.attr('type'); だと textarea が取得できないので不採用。
        if (partsType === 'text' || partsType === 'password' || partsType === 'textarea' || partsType === 'number') {
            $(function() {
                $target.each(function() {
                    // keyup イベント発生時に起動する関数登録
                    $(this).bind('keyup', checkChangeValue(this, chkItem, checkCallback));
                });
            });
        }
    });
}

/**
 *
 * フォームのイベント削除
 *
 * addValidateEvent で登録したイベント削除
 *
 */
function deleteValidateEvent(parts) {
    $.each(parts, function(key, chkItem) {

        var target = $('[name=' + key + ']');

        // 全てのイベント削除
        target.unbind();

        /* 個別に削除
        // blur, change イベント削除
        target.off('blur change ', function() {

        // keyup イベント削除
        var partsType = target[0].type;
        if (partsType === 'text' || partsType === 'textarea') {
            $(function() {
                target.each(function() {
                    // keyup イベント発生時に起動する関数登録
                    $(this).unbind('keyup');
                });
            });
        }
        */
    });
}

/**
 * テキストの変更監視
 *    keyup前後でテキスト変更ありならバリデ―ションを実行
 */
function checkChangeValue(element, chkItem, checkCallback){
    var curVal, oldVal = element.value;
    return function(){
        curVal = element.value;
        if(oldVal !== curVal){
            // console.log("現在値：" + oldVal + " → " + curVal);
            // 値変更ありなので差し替え
            oldVal = curVal;
            // 対象部品のチェック
            wvValidate(element.name, chkItem, checkCallback);
        }
        // メールアドレスと電話番号のどちらか必須チェック
        //if ($("#contact").length > 0) {
        //	if (element.name === 'mailAddress' || element.name === 'tel') {
        //		chkRequireMailOrTel(element.name);
        //	}
        //}
    };
}

// メールアドレスフォーマットチェック
function mailAddrCheck(mailAddress) {
    //if (!mailAddress.match(/^[A-Za-z0-9]+[\w-]+@[\w\.-]+\.\w{2,}$/)) {  // docomo 非対応
    if (!mailAddress.match(/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-z]+(\.[!#%&\-_0-9a-z]+)+$/)) {
        return false;
    }
    return true;
}

/**
 *
 * 開始～終了時間の論理チェック
 *
 */
function validateStartEndTime() {

    var $startTime = $('[name=startTime]');
    var $endTime = $('[name=endTime]');

    clearErrorMessage($startTime);
    clearErrorMessage($endTime);

    var startTime = $startTime.val();
    var endTime = $endTime.val();

    if (startTime && !endTime) {
        setArrowMessage($endTime, '終了時間を選択して下さい。');
        return false;
    } else if (!startTime && endTime) {
        setArrowMessage($startTime, '開始時間を選択して下さい。');
        return false;
    }
    if (startTime && startTime === endTime) {
        setArrowMessage($endTime, '開始時間と終了時間が同じです。');
        return false;
    }
    if (startTime > endTime) {
        setArrowMessage($endTime, '終了時間が開始時間より前です。');
        return false;
    }
    return true;
}

/**
 *
 * バリデーションNG時の処理
 *
 * obj 		：jQueryオブジェクト
 * message	：メッセージ
 *
 */
function setErrorMessage(obj, message) {

    /* 【吹き出し方式】
        // 既にエラー表示済みなら抜ける。
        // 分かり難くなるので2重にエラー表示しない。
        if (obj.next('.arrow-box').is(':visible')) {
            return;
        }
        // 吹き出しメッセージ表示
        setArrowMessage(obj, message);
    */

    // 背景色変更
    var formType = obj[0].type;
    if (formType === 'checkbox' || formType === 'radio') {
        // チェックボックス、ラジオボタンは背景色設定不可なので親を変更
        obj.parent().addClass('wvInvalid');
    } else {
        obj.addClass('wvInvalid');
    }

    // メッセージ表示
    var formGroup = obj.parents('.form-group');
    var errMessage = $('.formErrorMessage', formGroup);
    if (errMessage.length) {
        // 既にタグ追加済みの場合（または事前に設置してある場合）
        // チェックボックス、ラジオボタンは別処理なので注意！
        // チェックボックス、ラジオボタンは同じグループに name が複数存在するので、
        // obj.next に配置するとメッセージ表示が冗長になる。
        // これを回避するため .formErrorMessage タグを事前に配置しておくこと。
        errMessage.text(message);
        errMessage.show();
    } else {
        // 該当部品の直後にタグ追加
        obj.after('<div class="formErrorMessage"></div>');
        errMessage = obj.next('.formErrorMessage');
        errMessage.text(message);
        errMessage.show();
    }
}

/**
 * バリデーションOK時の処理
 */
function clearErrorMessage(obj) {

    /* 【吹き出し方式】
        // 吹き出し非表示
        //     直前の兄弟要素が obj.selector である .arrow-box を非表示。
        //     $('[name=hoge]').selector は [name=hoge] だが
        //     $(this).selector は取得不可なので注意！（objに$(this)を設定してコールしないこと！）

        // 何故か obj.selector が取得できなくなり動作しなくなったので変更！
        // $(obj.selector + ' + .arrow-box').css("display", "none");

        obj.next('.arrow-box').css("display", "none");
    */

    // 背景色を戻す
    var formType = obj[0].type;
    if (formType === 'checkbox' || formType === 'radio') {
        obj.parent().removeClass('wvInvalid');
    } else {
        obj.removeClass('wvInvalid');
    }

    // メッセージ削除
    var errMessage = obj.next('.formErrorMessage');
    if (!errMessage.length) {
        // next になければ .form-group 配下にあるか探す。
        // チェックボックス、ラジオボタンなどはここを通る。
        var formGroup = obj.parents('.form-group');
        errMessage = $('.formErrorMessage', formGroup);
    }
    if (errMessage.length) {
        errMessage.text('');
        errMessage.hide();
    }
}

/**
 *
 * TODO：チェックボックスのバリデーション
 *
 * 必須チェックだけしているが、もっと簡単にチェックできるはず！
 *
 */
function wvValidateCheckbox(parts) {
    var selectCheckbox = false;
    parts.each(function(index, checkbox) {
        if ($(checkbox).prop('checked') === true) {
            selectCheckbox = true;
            return false;   // JSのbreakに該当するので注意！
        }
    });
    if (!selectCheckbox) {
        // 1件も選択ない場合
        return false;
    }
    return true;
}

/*-------------------------------------------------
 UIパーツ
-------------------------------------------------*/
// function modalError(message)
// function setArrowMessage(obj, message)
// function openHelpMessage(obj)
// function callbackRender(results, status)

/**
 *
 * ダイアログ（jQuery UI）
 *
 */
// function modalError(message) {
//     var $modal = $('#modal');
//     $modal.text(message);
//     $modal.dialog({
// 	      modal: true
// 	    , title: 'エラー'
// 	    , autoOpen: false
// 	    , buttons: {
// 	          "OK": function() {
// 	            $(this).dialog('close');
// 	        }
// 	    }
// 	});
// }

/**
 *
 * 吹き出しメッセージ表示
 *
 * obj 		：jQueryオブジェクト
 * message	：メッセージ
 * canClear ：手動削除の可／不可
 *
 */
//function setArrowMessage(obj, message, canClear) {
function setArrowMessage(obj, message) {
    var arrowBox;
    if (!obj.next('.arrow-box').length) {
        // 直後のタグない場合
        //     該当部品の直後にタグ追加
        obj.after('<span class="arrow-box"></span>');

        arrowBox = obj.next('.arrow-box');

        // 設置位置取得
        var offset = obj.offset();
        // パーツの左端
        var xPos = offset.left;
        // 	パーツの下端より10px下
        //     height() だと padding, margin 含まないので中央に配置されてしまう。
        var yPos = offset.top + obj.outerHeight() + 10;

        arrowBox.css("z-index", 1000);
        // offset の前に display しないと位置がおかしくなるので注意！
        arrowBox.css("color", "yellow");
        arrowBox.css("display", "inline-block");
        arrowBox.offset({top: yPos, left: xPos});
        arrowBox.text(message);
    } else {
        // 再表示
        arrowBox = obj.next('.arrow-box');
        arrowBox.text(message);
        arrowBox.css("display", "inline-block");
    }

    /**
     * 吹き出しクリックイベント登録
     *     入力時にリアルタイム処理してないパーツの場合
     *     入力の邪魔になる場合があるので削除。
     */
    //if (canClear) {
    obj.next('.arrow-box').on('click', function() {
        clearErrorMessage(obj);
    });
    //}
}

/**
 *
 * 吹き出しヘルプボタン押下時処理
 *
 */
$(function(){
    // ヘルプボタン
    var $popHelp = $('.pop-help');
    // 押下時
    $popHelp.click(function() {
        openHelpMessage($(this));
    });
    // マウスオーバー時
    $popHelp.on('mouseover', function(){
        openHelpMessage($(this));
    });
    // ヘルプ吹き出し
    $arrowBox = $('.arrow-box');
    // 押下時
    $arrowBox.click(function() {
        $(this).hide();
    });
    // マウスリーブ時
    $arrowBox.on('mouseleave', function(){
        $(this).hide();
    });
});

/**
 *
 * 吹き出しヘルプ表示
 *
 */
function openHelpMessage(obj) {
    var arrowbox = obj.next('.arrow-box');
    if (!arrowbox.length) {
        arrowbox = obj.parent().next('.arrow-box');
    }

    // 微調整用のパラメータセット
    var posAdjust = {};
    posAdjust.left = parseInt(arrowbox.attr('left') ? arrowbox.attr('left'): 0);
    posAdjust.top = parseInt(arrowbox.attr('top') ? arrowbox.attr('top'): 0);

    if (arrowbox.css('display') === 'block') {
        arrowbox.css("display", "none");
    } else {
        // 設置位置取得
        var offset = obj.offset();
        // パーツの左端より10px右寄り
        var xPos = offset.left-10 + posAdjust.left;
        //// 矢印が真ん中を指すよう調整
        //var xPos = offset.left + obj.innerWidth()/2 -(arrowbox.innerWidth()/2);
        // 	パーツの下端より10px下
        //     height() だと padding, margin 含まないので中央に配置されてしまう。
        var yPos = offset.top + obj.outerHeight() + 10 + posAdjust.top;
        arrowbox.css("z-index", 2000);
        // offset の前に display しないと位置がおかしくなるので注意！
        arrowbox.css("display", "inline-block");
        arrowbox.offset({top: yPos, left: xPos});
    }
}

/*-------------------------------------------------
 Google Map
-------------------------------------------------*/
/**
 * Google Map 表示
 */
function showGoogleMap(latitude, longitude, zoom, elemId, mapType, title, draggable) {

    // 緯度・経度
    var latlng = new google.maps.LatLng(latitude, longitude);

    // 出力タイプ
    if (mapType === 'ROADMAP') {
        // 通常の地図
        mapTypeId = google.maps.MapTypeId.ROADMAP;
    } else {
        // 航空写真、ストリートビュー
        mapTypeId = google.maps.MapTypeId.SATELLITE;
    }

    // 目印アイコンのドラッグ
    if (draggable === undefined) {
        draggable = false;
    }

    var options = {
        zoom: zoom
        , center: latlng
        , mapTypeId: mapTypeId
    };

    // 地図を表示するID要素の位置
    var posElem = new google.maps.Map(document.getElementById(elemId), options);

    // 地図作成
    var marker = new google.maps.Marker({
        map: posElem
        , position: latlng
        , title: title
        , draggable: draggable
        , icon: new google.maps.MarkerImage(				// マーカーを追加
            'images/marker_finger.png' 			        // url：読み込むマーカー画像パス
            , new google.maps.Size(48,48)					// size：画像サイズ
            , new google.maps.Point(0,0)  					// origin：画像表示の起点
            , new google.maps.Point(48,0) 					// anchor：画像のどの座標を指定した緯度経度にあてるか
        )
    });

    //------------------------------------------------
    // イベント登録
    //------------------------------------------------
    // FIXME：共通関数がHTMLタグに依存しないよう変更した方がいい。
    // if (pageName === 'Regist/Base') {
    // マーカードラッグで緯度・経度更新
    if ($('[name="latitude"]').length) {
        google.maps.event.addListener(marker, 'dragend', function(ev) {
            $('[name="latitude"]').val(ev.latLng.lat());
            $('[name="longitude"]').val(ev.latLng.lng());
        });
    }
    // ズーム変更でズーム更新
    if ($('[name="zoom"]').length) {
        google.maps.event.addListener(posElem, 'zoom_changed', function() {
            $('[name="zoom"]').val(posElem.getZoom());
        });
    }
    //}
}

/**
 *
 * Google Map 表示（ストリートビュー）
 *
 */
// function showGoogleStreetView(latitude, longitude, zoom, elemId, title) {
//
// 	// 緯度・経度
// 	var latlng = new google.maps.LatLng(latitude, longitude);
//
//
// 	options = {
// 	      zoom: zoom
// 		, center: latlng
// 	    , mapTypeId: google.maps.MapTypeId.SATELLITE
// 	};
//
// 	// 地図を表示するID要素の位置
// 	var posElem = new google.maps.Map(document.getElementById(elemId), options);
//
//     var stview = new google.maps.StreetViewPanorama(
//         document.getElementById(elemId), {
//             position : posElem.getCenter()
//         });
//     // ストリートビューをバインド
//     posElem.setStreetView(stview);
// }

/**
 *
 * Google Map 表示とイベント登録
 *
 * 地図表示ボタンからコールされる。
 * 微調整時の緯度、経度を取得するためにイベントも登録。
 *
 * @param results		：ジオコーダの結果
 * @param status 		：ジオコーディングのステータス
 *
 */
function callbackRender(results, status) {
    if(status == google.maps.GeocoderStatus.OK) {

        //------------------------------------------------
        // 地図表示
        //------------------------------------------------
        // フォームに入力された住所からGoogle Map APIで取得した結果を表示

        var options = {
            zoom: 18,										//
            center: results[0].geometry.location, 			// 指定住所から計算した緯度経度を指定
            mapTypeId: google.maps.MapTypeId.ROADMAP 		// 出力タイプの選択
        };
        var gmap = new google.maps.Map(document.getElementById('map-canvas'), options);

        // マーカーを表示
        var marker = new google.maps.Marker({
            map: gmap
            , position: results[0].geometry.location
            , title: "XXXXX"
            , draggable: true
            , icon: new google.maps.MarkerImage(
                'images/marker_finger.png'      	            // url：読み込むマーカー画像パス
                , new google.maps.Size(48,48)				    // size：画像サイズ
                , new google.maps.Point(0,0)  				    // origin：画像表示の起点
                , new google.maps.Point(48,0) 				    // anchor：画像のどの座標を指定した緯度経度にあてるか
            )
        });

        // 緯度、経度、ズームをセット
        var latlng = results[0].geometry.location;
        $('[name="latitude"]').val(latlng.lat());
        $('[name="longitude"]').val(latlng.lng());
        $('[name="zoom"]').val(gmap.getZoom());
        // 住所を取得（「日本, 」を削除）
        //$('[name="address"]').val(results[0].formatted_address.replace(/^日本, /, ''));

        //------------------------------------------------
        // イベント登録
        //------------------------------------------------
        // マーカードラッグで緯度・経度更新
        google.maps.event.addListener(marker, 'dragend', function(ev) {
            $('[name="latitude"]').val(ev.latLng.lat());
            $('[name="longitude"]').val(ev.latLng.lng());
        });

        // ズーム変更でズーム更新
        google.maps.event.addListener(gmap, 'zoom_changed', function() {
            $('[name="zoom"]').val(gmap.getZoom());
        });

    }
}

/*-------------------------------------------------
 アラート
-------------------------------------------------*/
$(function(){
    /**
     *
     * 登録確認アラートダイアログ
     *
     * 直下 span のメッセージを表示する。
     *
     */
    $('.confirmRegist').on("click", function(e){
        var message = $(this).next('span').text();
        if (!message) message = $(this).next('div').text();
        if (!message) message = '登録していいですか？';
        var answer = confirm(message);
        if (answer !== true){
            return false;
        }
        // ローディング開始
        $("#loading").fadeIn();
    });

    /**
     *
     * 削除確認アラートダイアログ
     *
     */
    $('.confirmDelete').on("click", function(e){
        var answer = confirm('削除していいですか？');
        if (answer !== true){
            return false;
        }
    });
});

/*-------------------------------------------------
 テスト
-------------------------------------------------*/
/**
 *
 * テストデータ設定
 *
 * 各ページ内に実装
 *
 * ＜例＞
 * ---------------------------------------------------------------
 * %% if $WV_DEBUG %%
 *    <div class="box-btn-test">
 *        <span class="btn-test">テストデータセット</span>
 *        <span class="btn-test resetForm">データリセット</span>
 *    </div>
 * %% /if %%
 * ---------------------------------------------------------------
 *
 */
$(function(){
    // ＜旧方式＞
    // ----------------------------------------------------------------------------------------------------
    // // テストデータ設定
    // $('.btn-test').on("click", function(e){
    //     // テキスト・セレクトボックス
    //     $('[name=tel]').val('08055479999');
    //     // ラジオボタン
    //     $("input[type=radio][name=rdo]").val(['hoge']);			    // hoge を選択
    //     // チェックボックス（単体形式）
    //     $('[name=chk]').prop('checked', true);                       // 全てON
    //     $("[name=chk]").val(['hoge', 'huga']);                       // hoge のみチェック
    //     // チェックボックス（配列形式）
    //     $('[name="week[]"]').prop('checked', false);                 // 全クリア
    //     $($('[name="week[]"]')).val(['hoge', 'huga']);               // hoge, huga をチェック
    //     $($('[name="week[]"]')[3]).prop('checked', true);            // 4番目をチェック
    //     // データクリア
    //     $('[name=namae]').val('');
    //     $('[name=kiyaku]').prop('checked', false);
    //     $($('[name="week[]"]')[3]).prop('checked', false);
    // });
    // ----------------------------------------------------------------------------------------------------
    // ＜HTML例（ボタン1個）＞
    // ----------------------------------------------------------------------------------------------------
    // %% if $WV_DEBUG %%
    // 	<div class="box-testInput">
    // 	    <div class="btn-test">テストデータセット</div>
    // 	    <div class="testInputVal">
    // 	        <div data-name="recomLevel">3</div>
    // 	        <div data-name="title">タイトル テキスト テキスト</div>
    // 	        <div data-name="detail">コメント テキスト テキスト テキスト テキスト テキスト</div>
    // 	        <div data-name="buyWhere">ネットオークション</div>
    // 	        <div data-name="buyReason">店頭で見て</div>
    // 	    </div>
    // 	</div>
    // %% /if %%
    // ----------------------------------------------------------------------------------------------------
    // ＜HTML例（ボタン複数）＞
    // ----------------------------------------------------------------------------------------------------
    // %% if $WV_DEBUG %%
    //     <div class="box-testInput">
    //         <span class="btn-test">kidani@wd-valley.com</span>
    //         <span class="btn-test">kidani39@gmail.com</span>
    //         <span class="btn-test">woodvy1@gmail.com</span>
    //         <span class="btn-test">woodvy2@gmail.com</span>
    //         <div class="testInputVal">
    //             <div data-name="mailAddress" class="targetBtnTest"></div>
    //             <div data-name="pass">test12</div>
    //         </div>
    //     </div>
    // %% /if %%
    // ----------------------------------------------------------------------------------------------------
    $('.box-testInput .btn-test').on("click", function(e){
        var $self = $(this);
        var $testBox = $('.testInputVal', $(this).parent('.box-testInput'));
        $testBox.children('div').each(function(key, val) {
            var targetKey = $(val).attr('data-name');
            var $targetObj = $('[name="' + targetKey + '"]');
            if (!$targetObj.length)
                return true;    // スキップ
            var partsType = $targetObj.attr('type');
            // フォームのタイプ
            if (!partsType) {
                // select-one, textarea
                partsType = $targetObj[0].type;
            }

            // 設定値
            var targetVal = $(val).text();
            if (!targetVal && $(val).hasClass('targetBtnTest')) {
                // 選択したボタンに設定されたテキストをセット
                // ログイン時のアカウント切替え等に利用。
                targetVal = $self.text();
            }

            // 値をセット
            if ($targetObj) {
                if (partsType === 'checkbox') {
                    // チェックボックス
                    // 例：$targetObj.val(['バス', 'エギング', '船']);
                    // HTML側には「['バス', 'エギング', '船'])」と記載する点に注意！
                    $targetObj.val(eval(targetVal));
                } else if  (partsType === 'radio') {
                    // ラジオボタン
                    // $('[name=buyWhere][value="XXXXX"]').prop("checked", true);       // eval 使わないなら value の値で指定して選択させる。
                    $targetObj.val(eval('["' + targetVal + '"]'));                      // この方式だと eval 使うしない？
                } else {
                    // テキストボックス、セレクトボックス、その他
                    $targetObj.val(targetVal);
                }
            }

        });
    });
});

/*-------------------------------------------------
 その他
-------------------------------------------------*/
// ブラウザバック時の load イベント有効化
//$(window).bind("unload",function(){});

