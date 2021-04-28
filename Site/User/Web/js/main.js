/**
 * 
 * ユーザー画面メイン処理
 * 
 * @author     : kidani@wd-valley.com
 * @copyright  : Wood Valley Co., Ltd.
 * 
 */

/*************************************************
 共通処理
 *************************************************/
// デバッグモード
WV_DEBUG = parseInt(paramJs.wvDebug);

// デバイス別テンプレート切替閾値
scrChgSize = paramJs.scrChgSize;

// ページ識別名取得
// pageName = getParam('p');    // これだとリダイレクト時に識別不可
pageName = paramJs.pageName;

// サーバ側クエリ（GET、POSTパラメータ）
srvQuery = paramJs.query;

// ログイン状態
userLogin = paramJs.userLogin;

// スクリーン種別（閾値 scrChgSize で切替）
//   normal：PC用
//   small：SP用
scr = paramJs.scr;

/**
 *
 * viewport 切り替え
 *
 * Safari の場合は常に拡大可能になっているので別処理が必要だが
 * とりあえず面倒なので非対応。
 *
 */
// 拡大可能にする（画像ズーム時などで利用）
function viewportScalableOn() {
    $("meta[name='viewport']").attr('content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=3.0, user-scalable=1');
}
// 拡大不可にする（通常の設定）
function viewportScalableOff() {
    $("meta[name='viewport']").attr('content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0');
}

// エラー出力
window.onerror = function(errMsg, url, lineNumber) {
    // if (WV_DEBUG) serverLog(errMsg + ", file=" + url + ":" + lineNumber, 'エラー検知');
    window.log(errMsg + ", file=" + url + ":" + lineNumber);
}
$(function(){
    //------------------------------------------------
    // ウィンドウ幅に応じた切り替え処理
    //------------------------------------------------
    // ロード時
    changeScreenSize();

    /**
     *
     * ウィンドウ幅変更時
     *
     * 特定の幅以下になったらリロード
     *
     */
    $(window).on("resize", function() {
        changeScreenSize();
    });

    /**
     *
     * デバイス別テンプレート切替
     *
     */
    function changeScreenSize() {
        // 画面横幅が scrChgSize 以上の場合（通常：768px）
        if (window.matchMedia( '(min-width:' + scrChgSize + 'px)' ).matches) {
            if (scr !== 'normal') {
                // SP用テンプレート以外ならPC用へ切替

                // 既存のクエリを付加
                //     POST値は付加できないので注意！
                //     POST値まで網羅するならPHP側から渡す必要あり。
                var addQuery = getQuery('scr');
                location.href = '?scr=normal&' + addQuery;
            }
            // 画面横幅が scrChgSize 未満の場合
        } else {
            if (scr && scr !== 'small') {
                // SP用テンプレート以外ならSP用へ切替
                var addQuery = getQuery('scr');
                location.href = '?scr=small&' + addQuery;
            }
        }
    }

    /**
     *
     * meta タグ書き換え
     *
     */
    $meta = {};
    $meta.robots = $('[name="metaRobots"]').val();
    $meta.title = $('[name="metaTitle"]').val();
    $meta.description = $('[name="metaDescription"]').val();
    $meta.keywords = $('[name="metaKeywords"]').val();
    $meta.image = $('[name="metaImage"]').val();
    $meta.url = $('[name="metaUrl"]').val();
    if ($meta.robots !== undefined) {
        var $metaRobots = $('meta[name=robots]');
        if ($metaRobots.val() !== undefined) {
            $metaRobots.attr('content', $meta.robots);
        } else {
            // タグごと head の先頭に追加 <meta name="robots" content="noindex">
            $('head').prepend($('<meta name="robots" content="' + $meta.robots + '">'));
        }
    }
    if ($meta.title !== undefined) {
        $('head title').text($meta.title);
        $("meta[property='og:title']").attr('content', $meta.title);
    }
    if ($meta.description !== undefined) {
        $('meta[name=description]').attr('content', $meta.description);
        $("meta[property='og:description']").attr('content', $meta.description);
    }
    if ($meta.keywords !== undefined) {
        $('meta[name=keywords]').attr('content', $meta.keywords);
    }
    if ($meta.image !== undefined) {
        $("meta[property='og:image']").attr('content', $meta.image);
    }
    if ($meta.url !== undefined) {
        $("link[rel='canonical']").attr('href', $meta.url);
        $("meta[property='og:url']").attr('content', $meta.url);
    }

	/**
	 * ページ内リンクでスムーズスクロール
	 * 	id="hoge" で指定したタグまでスクロール
	 */
	$('a[href^="#"]').on('click', function(){
		var Target = $(this.hash);
		var TargetOffset = $(Target).offset().top - 150;
		var Time = 700;
		$('html, body').animate({
			scrollTop: TargetOffset
		}, Time);
		return false;
	});

});

/*************************************************
 パーツ別処理
*************************************************/
/*************************************************
 ページ別処理
*************************************************/
/*-------------------------------------------------
 トップ画面
-------------------------------------------------*/
$(function(){
    var header = $('.navbar');

    if (!pageName || pageName === 'Top') {
        // トップページの場合

        // スクロールでメニューの透過解除
        var mainVisualHeight = 50;
        $(window).scroll(function () {
            $(this).scrollTop() > mainVisualHeight ?  header.css('background', '#469c55'): header.css('background', 'none');
        });

        // SPのメニューボタンクリックで透過解除
        $('.navbar-toggler').on("click", function(e){
            header.css('background', '#469c55');
        });

        /**
         *
		 * メインバナークロスフェード
		 *
         */
		// 1番目の画像以外を非表示
		$('.imgSlide img:gt(0)').hide();
		setInterval(function() {
			$('.imgSlide :first-child').fadeOut().next('img').fadeIn()
				.end().appendTo('.imgSlide');
		}, 5000);

        // ロゴ（Wood Valley）
        $('.animeWvLogo').fadeIn(1000).animate({
            'top': '35%'
        },{
            duration: 1500,
            queue: false
        });

        // キャッチ（optimize the ...）
        $('.animeWvCatch').fadeIn(3000);

        /**
         *
         * ロード時とウィンドウサイズ変更時処理
         *
		 * ロゴの表示位置調整
		 *
         */
        $(window).on('load resize', function(){
            // センターに移動
            var posLeft = ($('.boxCrossFade').width() - $('.animeWvLogo').width())/2;
            $('.animeWvLogo').css('left', posLeft);
        });

        // サービスアイコン
        // マウスオーバー／アウトでのサイズ変更
        $('.sicon').on("mouseover", function(e){
            $(this).animate({
                fontSize: '120px'
            }, 500);
        });
        $('.sicon').on("mouseout", function(e){
            $(this).animate({
                fontSize: '100px'
            }, 500);
        });

    } else {
        // トップページ以外の場合
        // header.css('background', '#0b4819');
        header.css('background', '#469c55');
    }
});

/*-------------------------------------------------
 問合せ画面
-------------------------------------------------*/
/**
 *
 * サイト問合せフォーム
 *
 */
$(function(){
    if (pageName === 'Contact/Contact') {

        // バリデーションイベント登録
        var parts =
            {
                namae		: ['require', 'max20'],					// 名前
                mailAddress	: ['require', 'email', 'max66'],		// メールアドレス
                detail		: ['require', 'min20', 'max500']  		// 内容
                // tel		: ['numOnly', 'min10', 'max11']         // ハイフンなしの半角数字10～11桁（日本ではこれ以外存在しない）
            };
        addValidateEvent(parts);

        // サブミット時処理
        $('form').on('submit', function(e) {
            if (!wvValidateForm(parts)){
                alert('入力値に誤りがあります。');
                return false;
            }
            var answer = confirm('入力した内容で送信していいですか？');
            if (answer !== true){
                return false;
            }
        });
    }
});

// Google reCAPTCHA 認証チェック
// 送信ボタン有効化
function clearcall(code) {
    if(code !== ""){
        $(':submit').removeAttr("disabled");
    }
}



