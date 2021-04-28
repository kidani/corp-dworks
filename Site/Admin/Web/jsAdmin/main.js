/**
 * 
 * 管理画面メイン処理
 * 
 * ユーザー画面・管理画面共通の処理は
 * User/common.js を参照。
 * 
 * @author     : kidani@wd-valley.com
 * @copyright  : Wood Valley Co., Ltd.
 * 
 */

/*************************************************
 グローバル変数定義
 *************************************************/
// 現在のスクリーン種別（閾値 scrChgSize で切替）
//   normal：PC用
//   small：SP用
scr = paramJs.scr;

// デバイス別テンプレート切替閾値
scrChgSize = paramJs.scrChgSize;

// ページ識別名取得
pageName = getParam('p');

/*************************************************
 全ページ共通処理
 *************************************************/
//------------------------------------------------
// ロード時処理
//------------------------------------------------
$(function(){
    /**
     * アコーディオンメニューを最前面に
     *     入退会レポートの年・月ボタンの背面に来るので修正
     */
    $('.menu-second-level').css('z-index', '1000');

    // Bootstrap ツールチップ
    $('[data-toggle="tooltip"]').tooltip();

    // Bootstrap ポップオーバー（未使用）
    // $("[data-toggle=popover]").popover();

    /**
     *
     * 日付＋時刻のカレンダーを設定
     *
     */
    {
        // 公開開始日時
        if ($('[name="openTime"]').length) {
            $('[name="openTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
            $('[name="openTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        }
        if ($('[name="openTimeFrom"]').length) {
            $('[name="openTimeFrom"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
            $('[name="openTimeTo"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        }

        // 公開終了日時
        if ($('[name="closeTime"]').length) {
            $('[name="closeTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
            $('[name="closeTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        }

        // 登録日時
        if ($('[name="insTimeFrom"]').length) {
            $('[name="insTimeFrom"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
            $('[name="insTimeTo"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        }
        // 最終ログイン日時
        if ($('[name="lastLoginTimeFrom"]').length) {
            $('[name="lastLoginTimeFrom"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
            $('[name="lastLoginTimeTo"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        }
    }
});

//------------------------------------------------
// イベント処理
//------------------------------------------------
$(function(){

    /**
     * 削除ボタン押下時
     */
    $(".btn-del").click(function() {
        if (!confirm('削除してよろしいですか？')) {
            return false;
        }	else {
            return true;
        }
    });

    /**
     *
     * 施設詳細画像アップロード（切り抜きあり）
     *
     * これが画像アップロード全体の最新版。
     * メルカリ形式になっている。
     * 画像削除時にサンプル画面が消えてしまうバグあり。
     *
     * FIXME：ユーザー側 main.js と統合して common.js に移動すること！
     *
     */

    var $openUploadImgModal = $(".openUploadImgModal");
    if ($openUploadImgModal.length) {

        /**
         * 画像変更ダイアログ表示
         */
        var popUpImgModal = function(obj) {
            // 画像種別（カバー／プロフィール）
            var imgType = obj.attr('data-imgType');
            // 画像番号
            var imgNo = 1;
            if (obj.attr('data-imgNo') !== undefined) {
                imgNo = obj.attr('data-imgNo');
            }
            // サンプル画像URL
            var noImageUrl = obj.attr('data-noImg');
            // 現在の画像URL
            // トラバーシングに注意！階層が2重なのでタグ構成を厳密にすること。
            var curImgUrl;
            if (imgType === 'school') {
                curImgUrl = obj.children('img').attr('src');
            } else {
                curImgUrl = obj.parent('div').prev('img').attr('src');
            }

            // モーダルにセット
            var $modal = $('.uploadImgModal');
            $modal.attr('data-imgType', imgType);   // イメージタイプ
            $modal.attr('data-imgNo', imgNo);       // イメージ番号
            $modal.attr('data-noImg', noImageUrl);  // サンプル画像URL
            var $objImg = $('img', $modal);
            $objImg.attr('src', curImgUrl);         // 現在の画像URL

            // ボタン表示制御
            var imageUrlBeforeOrg = getImagePath($objImg, false);
            if (imageUrlBeforeOrg === noImageUrl) {
                // 現在の画像にサンプルがセットされている場合
                $('#execDelete').hide();        // 削除ボタン非表示
                // 「ファイル選択」ダイアログまで開く
                // $('[name="uploadData"]').trigger('click');
            }

            // 黒背景オープン
            $('.wv-modal-back').fadeIn(300);
            // モーダルオープン
            $modal.fadeIn(300);

            // 更にサンプル画像なら追加操作確定なので
            // 「ファイルを選択」を開く。
            if (imageUrlBeforeOrg === noImageUrl) {
                // 現在の画像にサンプルがセットされている場合
                $('[name="uploadData"]').trigger('click');
            }

        };

        /**
         * 画像変更ダイアログ表示
         */
        $openUploadImgModal.click(function() {
            popUpImgModal($(this));
        });

        /**
         * モーダルのクローズボタン押下時処理
         */
        $('#closeModal').click(function(){
            closeModal();
        });

        /**
         * モーダルクローズ時の処理
         */
        function closeModal() {
            resetModal();
            $("#loading").fadeOut(100);
            $('.wv-modal-back').fadeOut(300);
            $('.uploadImgModal').fadeOut(300);
        }

        /**
         * モーダルのパーツ初期化
         */
        function resetModal() {
            var $objImg = $('img', $('.uploadImgModal'));
            // Cropper削除
            $objImg.cropper('destroy');
            // type="file" に設定されたデータを破棄。
            $('[name="uploadData"]').val('');
            // 画像切り抜きの注記非表示
            $('.editCaution').hide();
            // 変更ボタン表示
            $('#execSelect').show();
            // 登録ボタン非表示
            $('#execUpload').hide();
            // 削除ボタン表示
            $('#execDelete').show();
        }

        /**
         *
         * モーダルの変更ボタン押下時処理
         *
         * 選択したファイルをプレビュー表示する。
         * HTML5 File API によりプレビュー可能になった。
         *
         */
        $('#execSelect').on("click", function(){
            // 「ファイルを選択」ボタンの見た目変更
            $('[name="uploadData"]').trigger('click');
        });
        $('[name="uploadData"]').on("change", function(){

            // 変更対象の画像タグ
            var $modal = $('.uploadImgModal');
            var $objImg = $('img', $modal);

            // Cropper削除
            $objImg.cropper('destroy');

            var noImageUrl = $(this).attr('data-noImg');
            if ($objImg.attr('src') === noImageUrl) {
                // 削除ボタン非表示
                $('#execDelete').hide();
            }

            // 登録ボタン非表示
            $('#execUpload').hide();

            // ファイル未選択ならクリア
            //     一度画像選択してから、また開いキャンセルすると
            //     未選択状態でここを通るので注意！
            var uploadFile = $(this).prop('files')[0];
            if (!uploadFile) {
                resetModal();
                return;
            }
            // 画像以外ならクリア
            if (!uploadFile.type.match('image.*')) {
                resetModal();
                return;
            }
            // 5MB（5000000）オーバーならクリアしてアラート
            if (uploadFile.size > 5000000) {
                resetModal();
                alert('5MB以下のファイルを選択して下さい。');
                return;
            }

            // 画像表示
            var canvasData;
            var cropBoxData;
            var reader = new FileReader();
            reader.onload = function() {
                // 読込み成功の場合

                // プレビュー表示
                //     base64のバイナリコードがセットされるので注意！
                $objImg.attr('src', reader.result);

                // 登録ボタン表示
                $('#execUpload').show();

                // 編集操作についての注記表示
                $('.editCaution').show();

                // アスペクト比
                var aspectRatio = null;
                var imgType = $modal.attr('data-imgType');
                if (imgType === 'cover') {
                    // カバー 1920 x 600（PC専用の場合） → 不採用
                    // aspectRatio = 3.2;
                    // カバー 800 x 800（PC／SP兼用にして表示時にトリミングするよう変更）
                    aspectRatio = 1;
                } else if (imgType === 'prof') {
                    // アイコン 340 x 340
                    aspectRatio = 1;
                } else if (imgType === 'school') {
                    // 施設 800 x 800
                    aspectRatio = 1;
                }

                // Cropper作成
                $objImg.cropper({
                    aspectRatio: aspectRatio       // アスペクト比
                    , background: false        		// 背面の柄模様消去
                    , modal: false             		// 背面グレー色消去
                    , autoCropArea: 1				// 切り取り枠の画像フィット率（1で最大）
                    , ready: function() {
                        // 作成完了

                        // キャンバスデータ取得
                        canvasData = $(this).cropper('getCanvasData');
                    }
                    , cropmove: function() {
                        // 画像切り取り枠のドラッグによる移動、サイズ変更時

                        // 切り取り枠データ取得
                        cropBoxData = $(this).cropper('getCropBoxData');

                        // ドラッグ移動によるはみ出しを矯正
                        //     画像の実サイズよりキャンバスの方が微妙に大きいみたいなので、ちょっとはみ出てしまう点に注意！
                        if (cropBoxData.left < canvasData.left) {
                            // 左横にはみ出た場合
                            $(this).cropper('setCropBoxData', {left: canvasData.left});
                        }
                        if (cropBoxData.left + cropBoxData.width > canvasData.left + canvasData.width) {
                            // 右横にはみ出た場合
                            var diff = (cropBoxData.left + cropBoxData.width) - (canvasData.left + canvasData.width);
                            $(this).cropper('setCropBoxData', {left: cropBoxData.left - diff});
                        }

                        // サイズ変更によるはみ出しを矯正
                        if (cropBoxData.width > canvasData.width) {
                            // 横からはみ出たので内側に戻す。
                            $(this).cropper('setCropBoxData', {width: canvasData.width});
                        }
                    }
                });
            };
            reader.onerror = function(e){
                // 読込み失敗の場合
            };
            // 読込み実行
            reader.readAsDataURL(uploadFile);

            // 変更ボタン非表示
            $('#execSelect').hide();
            // 削除ボタン非表示
            $('#execDelete').hide();
        });

        /**
         *
         * 登録ボタン押下時処理
         *
         * 画像アッププロード
         *
         */
        $('#execUpload').on('click', function(){

            // ローディング開始
            var $loading = $("#loading");
            $loading.fadeIn();
            $loading.css('z-index', '9000');

            // アップロードファイル
            var form = $('#formImgUpload').get()[0];
            var formData = new FormData(form);

            // ファイルサイズチェック
            var fsize = $('[name="uploadData"]').prop('files')[0].size;
            if (fsize > 31457280) {
                // upload_max_filesize（32MB）を超える場合エラー
                closeModal();
                alert('画像の容量は30MB以内にして下さい');
                return false;
            }

            // 切り抜きデータ
            var $modal = $('.uploadImgModal');
            // 必ず img タグに設置した ID を指定すること。
            // img には base64 のバイナリコードがセットされるのだが、ここを通る際には
            // Cropper 側で、uploadImgModal 配下に別の img タグを2つ追加しているためおかしなことになる。
            // var $objImg = $('img', $modal);  // これは失敗する。（img タグが3つになってる！）
            var $objImg = $('#uploadImgForCropper');
            var imgData = $objImg.cropper('getData');

            imgData = {
                croplength: {
                    width	: Math.round(imgData.width)
                    , height: Math.round(imgData.height) }
                , cropPoint: {
                    x: Math.round(imgData.x)
                    , y: Math.round(imgData.y) }
            };
            formData.append('width', imgData.croplength.width);
            formData.append('height', imgData.croplength.height);
            formData.append('x', imgData.cropPoint.x);
            formData.append('y', imgData.cropPoint.y);

            var imgType = $modal.attr('data-imgType');
            var imgNo = $modal.attr('data-imgNo');

            var reqUrl = '?p=UploadCropImg';
            formData.append('imgType', imgType);
            if (imgType === 'school') {
                // 施設写真の場合
                formData.append('imgNo', imgNo);
                formData.append('schoolId', $('[name="schoolId"]').val());
            }
            // serverLog(reqUrl, 'Ajax 画像アップロード開始');
            $.ajax({
                  url: reqUrl
                , type: 'POST'
                , data: formData
                , dataType: 'json'
                , async: true
                , processData: false
                , contentType: false
                , timeout: 10000
            }).done(function(data, status, xhr) {
                // console.log(JSON.stringify(xhr));
                // $('#debugOutput').text(JSON.stringify(xhr));
                if (!data || data.result === 'error') {
                    // if (WV_DEBUG) console.log('リサイズ失敗');
                    alert('画像の登録に失敗しました。');
                    closeModal();
                } else {
                    // 画像変更
                    var now = new Date().getTime();		// キャッシュ回避
                    var targetImg, imgUrlOld;
                    if (imgType === 'school') {
                        targetImg = $('.uploadImgTop [data-imgNo="' + imgNo + '"]').children('img');
                        $($('.uploadImgTop [name="uploadImg[]"]')[imgNo]).val(data.detail.imgUrl);
                    }
                    imgUrlOld = targetImg.attr('src');      // 変更前のURL保持
                    targetImg.attr('src', data.detail.imgUrl + '?upd=' + now);
                    // 削除ボタン表示
                    $('#execDelete').show();

                    // 末尾にサンプル画像追加
                    var reg = new RegExp(/AddImage.png/);       // サンプル画像
                    if (imgUrlOld.match(reg)) {
                        // サンプル画像を変更した場合は追加なので末尾にサンプル画像追加
                        var $addImageList = $($(".uploadImgTop")[0]).clone();
                        $addImageList.hide();
                        $addImageList.appendTo("ul.imgThumb");
                        imgNo = parseInt(imgNo)+1;
                        $('figure', $addImageList).attr('data-imgNo', imgNo);
                        var noImgPath = $('figure', $addImageList).attr('data-noImg');
                        $('img', $addImageList).attr('src', noImgPath);
                        $('input', $addImageList).val(noImgPath);
                        $addImageList.show();
                        // イベント追加（これ重要！）
                        $('.openUploadImgModal', $addImageList).click(function() {
                            popUpImgModal($(this));
                        });
                    }
                }
            }).fail(function(xhr, status, error) {
                console.error('エラー : ' + status + ' : ' + error);
                console.log(JSON.stringify(xhr));
                // $('#debugOutput').text(JSON.stringify(xhr));
                // serverLog(JSON.stringify(xhr), '画像アップロード fail');
                closeModal();
            }).always(function(arg1, status, arg2) {
                closeModal();
            });
        });

        /**
         *
         * ファイル削除
         *
         */
        $('#execDelete').on("click", function(){

            var $modal = $('.uploadImgModal');
            var $objImg = $('img', $modal);
            var postParam = {};

            // 画像種別（カバー／プロフィール／施設）
            imgType = $modal.attr('data-imgType');
            postParam.imgType = imgType;

            // schoolId 追加
            var schoolId, imgNo;
            if (imgType === 'school') {
                schoolId = $('[name="schoolId"]').val();
                imgNo = $('.uploadImgModal').attr('data-imgNo');
                postParam.schoolId = schoolId;
                postParam.imgNo = imgNo;
            }
            $.ajax({
                url: '?p=DeleteImg'
                , type: "POST"
                , data: postParam
                , dataType: "json"
                , async: true
                , timeout: 10000
            }).done(function(data, status, xhr) {
                if (!data || data.result === 'error') {
                    if (WV_DEBUG) console.log('ファイル削除失敗');
                    alert('ファイルが削除できません。');
                    return;
                }
                // モーダル側画像入替え
                var noImageUrl = $modal.attr('data-noImg');
                $objImg.attr('src', noImageUrl);

                // 削除ボタン非表示
                $('#execDelete').hide();

                // リストから削除
                // FIXME：削除時にサンプル画面が消えてしまうバグあり。
                var $imageList = $('li.uploadImgTop');
                $($imageList[imgNo]).remove();
                $imageList = $('li.uploadImgTop');  // 再取得

                // 連番ファイル名の振り直し
                var len = $imageList.length;
                var now = new Date().getTime();		// キャッシュ回避
                $imageList.each(function(key, val){
                    if (key === (len - 1)) return;     // 最後はサンプル画像なのでスルー
                    var imgNo = key+1;
                    $('figure', $(this)).attr('data-imgNo', imgNo);
                    var imgUrl = 'images/school/' + schoolId + '/' + imgNo + '.jpg' + '?upd=' + now;
                    $('img', $(this)).attr('src', imgUrl);
                    $('input', $(this)).val(imgUrl);
                });

                // モーダル閉じる
                closeModal();
            }).fail(function(xhr, status, error) {
                console.error('エラー : ' + status + ' : ' + error);
            }).always(function(arg1, status, arg2) {
            });
        });

        /**
         *
         * 画像の並べ替え
         *
         */
        // ドラッグ＆ドロップでソート可能に
        $('.imgThumb').sortable({
            // サンプル画像はドラッグ不可
            cancel: '.imgThumb li:last-child'
        });

        // sortstop イベントをバインド
        $('.imgThumb').bind('sortstop', function(e, ui){
            var $rows = $('.imgThumb figure');
            // 配置変更情報を配列で取得
            var imgNoList = {};     // index=0 が未定義だとややこしくなるので配列でやらないこと！
            $.each($rows, function(key, val) {
                // imgNoList[変更後のファイル名] = 変更前のファイル名 になる！
                imgNoList[key+1] = $(val).attr('data-imgNo');
            });
            console.log(imgNoList);
            // 配置変更情報
            var postParam = {};
            postParam.imgNoList = imgNoList;
            // 配置変更をサーバに反映
            postParam.schoolId = $('[name="schoolId"]').val();
            $.ajax({
                url: '?p=SortImg',
                type: 'POST',
                data: postParam,
                dataType: 'json',
                async: true,
                timeout: 10000
            }).done(function(data, status, xhr) {
                if (!data || data.result === 'error') {
                    console.log('画像の並べ替え失敗');
                    alert('画像の並べ替え失敗');
                } else {
                    console.log('画像の並べ替え成功');

                    // 画像のディレクトリを取得
                    var srcImgUrl = $('img', $($rows[0])).attr('src');
                    var dstImgDir = getDirName(srcImgUrl);
                    var now = new Date().getTime();		// キャッシュ回避

                    // ブラウザ側も連番ファイル名の振り直し
                    $.each($rows, function(key, val) {
                        // ----------------------------------------------------------------------------------------------------
                        //     <li class="list-inline-item uploadImgTop">
                        //         <figure class="openUploadImgModal" data-imgType="school"
                        //                 data-noImg="%% $sampleImgPath %%" data-imgNo="%% $key %%">
                        //             <img src="%% $val.main %%" class="wv-wpx-100"></figure>
                        //         <input type="hidden" form="form-school" name="uploadImg[]" value="%% $val.main %%">
                        //     </li>
                        // ----------------------------------------------------------------------------------------------------
                        if (key === ($rows.length - 1)) return;     // 最後はサンプル画像なのでスルー
                        var dstImgNo = key+1;
                        $targetList = $(this).parents('.uploadImgTop');
                        $('figure', $targetList).attr('data-imgNo', dstImgNo);
                        // 画像URL
                        var dstImgUrl = dstImgDir + dstImgNo + '.jpg?upd=' + now;
                        $('figure img', $targetList).attr('src', dstImgUrl);
                        $('input', $targetList).val(dstImgUrl);
                    });
                }
            }).fail(function(xhr, status, error) {
                console.error('エラー : ' + status + ' : ' + error);
            }).always(function(arg1, status, arg2) {
            });
        });
    }
});

/*************************************************
 各ページ別処理
 *************************************************/
$(function(){
    
    /**
     *
     * トップ画面
     *
     */
    if (!pageName || pageName === 'Top') {
        /* アイコンメニュークリック時 */
        $('.top-icon-menu li').click(function(){
            window.location=$(this).children('p').children('a').attr('href');
            return false;
        });
    }

    /**
     *
     * サイト基本情報画面
     *
     */
    if (pageName === 'BaseInfo') {
        // 切替ボタン押下時処理
        $(".btn-system-status").click(function() {
            var curStatus = $(".lbl-system-status span").text();
            var message;
            if (curStatus === '稼働中') {
                message = 'メンテナンスに切替えてよろしいですか？';
            }
            if (confirm(message)) {
                if (confirm('本当によろしいですか？')) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     *
     * 施設データ登録（サイト同期）／施設データ登録（CSV同期）
     *
     */
    if (pageName === 'School/Convert' || pageName === 'School/SyncCsv') {

        // 保育施設新規追加
        $('.siteAdd').on("click", function(e){
            if (confirm('施設を新規追加していいですか？')){
                var schoolIdWork = $('.idWork', $(this).parents('.schoolTop')).text();
                $.ajax({
                    url: '?p=AddSchool&idWork=' + schoolIdWork
                    , type: 'GET'
                    , data: {}
                    , dataType: 'json'
                    , async: true
                    , processData: false
                    , contentType: false
                    , timeout: 10000
                }).done(function(data, status, xhr) {
                    // console.log(JSON.stringify(xhr));
                    if (!data || data.result === 'error') {
                        alert('エラーが発生しました。');
                    } else {
                        // alert('施設を新規追加しました。');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('エラー : ' + status + ' : ' + error);
                    console.log(JSON.stringify(xhr));
                }).always(function(arg1, status, arg2) {
                });
            } else {
                return false;
            }
        });

        // 保育園閉鎖
        $('.siteClose').on("click", function(e){
            // closeTime をセット
            if (confirm('保育園を閉鎖していいですか？')){
                var schoolId = $('.schoolId', $(this).parents('.schoolTop')).text();
                $.ajax({
                      url: '?p=UpdateSchool&schoolId=' + schoolId + '&type=close'
                    , type: 'GET'
                    , data: {}
                    , dataType: 'json'
                    , async: true
                    , processData: false
                    , contentType: false
                    , timeout: 10000
                }).done(function(data, status, xhr) {
                    // console.log(JSON.stringify(xhr));
                    if (!data || data.result === 'error') {
                        alert('エラーが発生しました。');
                    } else {
                        // alert('保育園を閉鎖にしました。');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('エラー : ' + status + ' : ' + error);
                    console.log(JSON.stringify(xhr));
                }).always(function(arg1, status, arg2) {
                });
            } else {
                return false;
            }
        });

        // 保育園の名前変更
        $('.changeSchoolName').on("click", function(e){
            // closeTime をセット
            if (confirm('施設名を変更していいですか？')){
                var schoolId = $('.schoolId', $(this).parents('.schoolTop')).text();
                var newSchoolName = $('[name=newSchoolName]', $(this).parents('.changeDbData')).val();
                $.ajax({
                    url: '?p=UpdateSchool'
                    , type: 'POST'
                    , data: {
                        "schoolId": schoolId,
                        "name": newSchoolName,
                        "type": "changeName",
                    }
                    , dataType: "json"
                    , async: true
                    , timeout: 10000
                }).done(function(data, status, xhr) {
                    // console.log(JSON.stringify(xhr));
                    if (!data || data.result === 'error') {
                        alert('エラーが発生しました。');
                    } else {
                        // alert('保育園の名前を変更しました。');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('エラー : ' + status + ' : ' + error);
                    console.log(JSON.stringify(xhr));
                }).always(function(arg1, status, arg2) {
                });
            } else {
                return false;
            }
        });

        // 緯度・経度取得
        $('.getLatLng').on("click", function(e){
            // カウンタ
            var itemCount = $('.dataSchool').length;
            // ローディング開始
            var $loadingBack = $('.wv-modal-back');
            $loadingBack.fadeIn(300);
            var $loading = $("#loading");
            $loading.fadeIn();
            $loading.css('z-index', '9000');
            var resultFlg = true;
            $('.dataSchool').each(function(key, val){
                if (!resultFlg) {
                    return;
                }
                // こちらでも処理をスリープしないとタイムアウトになるので注意！
                var schoolId = $(this).attr('data-schoolId');
                var schoolName = $(this).attr('data-schoolName');
                var latitude = $(this).attr('data-latitude');
                var $copyResult = $('.copyResult', $(this).parents('.schoolTop'));
                if (latitude) {
                    itemCount--;
                    console.log('itemCount：' + itemCount);
                    // ローディング終了
                    if (itemCount === 0) {
                        $loading.fadeOut(100);
                        $loadingBack.fadeOut(300);
                    }
                } else {
                    $(this).delay(10000).queue(function() {
                        // if (key > 2) { return; }         // テスト用
                        $.ajax({
                            url: "?p=UpdateLatLng",
                            type: "POST",
                            data: {
                                "schoolId": schoolId,
                                "schoolName": schoolName,
                            },
                            dataType: "json",
                            async: false,       // true だと timeout が頻発するので false にした。
                            timeout: 10000,
                        }).done(function(data, status, xhr) {
                            if (!data) {
                                console.error(data.detail);
                                resultFlg = false;
                                return;
                            } else if (data.result === 'error') {
                                console.error(data.detail);
                                alert(data.detail);
                                resultFlg = false;
                                return;
                            }
                            var text = data.detail.lat + ',' + data.detail.lng;
                            $copyResult.text(text);
                            itemCount--;
                            console.log('itemCount：' + itemCount);
                            // ローディング終了
                            if (itemCount === 0) {
                                $loading.fadeOut(100);
                                $loadingBack.fadeOut(300);
                            }
                        }).fail(function(xhr, status, error) {
                            // console.error("通信エラー発生");
                            console.log("通信エラー発生");
                            itemCount--;
                            console.log('itemCount：' + itemCount);
                            // ローディング終了
                            if (itemCount === 0) {
                                $loading.fadeOut(100);
                                $loadingBack.fadeOut(300);
                            }
                        }).always(function(arg1, status, arg2) {

                        });
                        $(this).dequeue();
                    });
                }
            });
        });
    }

    /**
     *
     * お知らせ通知（アプリPUSH通知）画面
     *
     */
    if (pageName === 'Support/NativeNotify') {

        /**
         *
         * 送信対象ユーザー
         *
         */
        // ロード時
        var radioVal = $("input[name='sendType']:checked").val();
        if (radioVal === 'one') {
            $('[name="userId"]').show();
        } else {
            // 「送信対象ユーザーID」は非表示
            $('[name="userId"]').hide().val('');
        }
        // ラジオボタンON／OFF時
        $('input[name="sendType"]').on("click", function(e){
            var sendType = $(this).val();
            if (sendType === 'one') {
                $('[name="userId"]').show();
            } else {
                $('[name="userId"]').hide().val('');
            }
        });

    }

    /**
     *
     * メール配信画面
     *
     */
    if (pageName === 'SendMail/Detail') {

        // 配信開始日時
        $('[name="sendStartTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});
        $('[name="sendStartTime"]').datetimepicker({theme:'dark', lang: 'ja', defaultTime:'00:00'});

        // 今すぐ配信ボタン押下時処理
        $(".btnSendNow").click(function() {
            if (confirm('今すぐ配信してよろしいですか？')){
                // ローディング開始
                var $loading = $("#loading");
                $loading.fadeIn();
                $loading.css('z-index', '9000');
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     *
     * 施設詳細画面
     *
     */
    {
        // 緯度・経度の地図を表示ボタン押下時処理
        var $mapAnnotation = $('.map-move');
        $('#btn-map').on("click", function(e){
            var $mapCanvas = $("#map-canvas");
            var latitude = $('[name=latitude]').val();          // INTにしないこと！
            var longitude = $('[name=longitude]').val();        // INTにしないこと！
            var zoom = parseInt($('[name=zoom]').val());        // INT指定必須
            if (latitude) {
                // Google Map 表示
                showGoogleMap(latitude, longitude, zoom, 'map-canvas', 'ROADMAP', '施設', true);
                // キャンバスサイズ調整
                $mapCanvas.show();
                $mapCanvas.css("height", "300px");
                // 注記表示
                $mapAnnotation.show();
            }
        });

        // キーワードの地図を表示ボタン押下時処理
        var $mapKeyword = $('[name=mapKeyword]');
        $('#btn-mapKeyword').on("click", function(e){
            var $mapCanvas = $("#map-canvas");
            var keyword = $mapKeyword.val();
            if (!keyword) {
                var message = '住所や施設の名称を入力して下さい。';
                setErrorMessage($mapKeyword, message);
                return;
            }
            new google.maps.Geocoder().geocode({'address': keyword}, callbackRender);
            $mapCanvas.show();
            // キャンバスサイズ調整
            $mapCanvas.css("height", "300px");
            // 注記表示
            $mapAnnotation.show();
        });

        // エラーメッセージクリア
        $mapKeyword.on("blur change", function(e){
            var keyword = $mapKeyword.val();
            if (keyword) {
                clearErrorMessage($mapKeyword);
            }
        });
    }

});




