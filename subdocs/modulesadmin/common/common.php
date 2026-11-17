<?php
// バージョン（メジャー）
define("WEBFILE_MAJOR_VERSION",      "6");
// バージョン（マイナー）
define("WEBFILE_MINOR_VERSION",      "0");
// バージョン（メンテナンス）
define("WEBFILE_MAINTENANCE_VERSION", "0");

define("WEBFILE_VERSION", WEBFILE_MAJOR_VERSION . WEBFILE_MINOR_VERSION . WEBFILE_MAINTENANCE_VERSION);

// system envelope
require_once(MODULE_DIR."/common/array.php");
require_once(MODULE_DIR."/common/AccessTable.class.php");
require_once(MODULE_DIR."/common/AddrGroupUsersTable.class.php");
require_once(MODULE_DIR."/common/AddrGroupTable.class.php");
require_once(MODULE_DIR."/common/AddrTable.class.php");
require_once(MODULE_DIR."/common/CollectTable.class.php");
require_once(MODULE_DIR."/common/DownloadTable.class.php");
require_once(MODULE_DIR."/common/DownloadMailerTable.class.php");
require_once(MODULE_DIR."/common/Kp_newsTable.class.php");
require_once(MODULE_DIR."/common/Kp_news_readsTable.class.php");
require_once(MODULE_DIR."/common/UploadTable.class.php");
//require_once(MODULE_DIR."/common/FileTable.class.php");
require_once(MODULE_DIR."/common/FolderTable.class.php");
require_once(MODULE_DIR."/common/MChoiceTable.class.php");
require_once(MODULE_DIR."/common/UserTable.class.php");
require_once(MODULE_DIR."/common/LogTable.class.php");
require_once(MODULE_DIR."/common/AdminuserTable.class.php");
require_once(MODULE_DIR."/common/AdminmailTable.class.php");
require_once(MODULE_DIR."/common/DailyLogTable.class.php");
require_once(MODULE_DIR."/common/ConfigsTable.class.php");
require_once(MODULE_DIR."/common/GroupsTable.class.php");
require_once(MODULE_DIR."/common/Groups_accessesTable.class.php");
require_once(MODULE_DIR."/common/Groups_admin_usersTable.class.php");
require_once(MODULE_DIR."/common/Groups_usersTable.class.php");
require_once(MODULE_DIR."/common/DeleteDtTable.class.php");
require_once(MODULE_DIR."/common/SignTable.class.php");
require_once(MODULE_DIR."/common/TagTable.class.php");
require_once(MODULE_DIR."/common/TemplateTable.class.php");
require_once(MODULE_DIR."/common/VirusLogTable.class.php");
require_once(MODULE_DIR."/common/ResendMailTable.class.php");
require_once(MODULE_DIR."/common/UserSuperiorTable.class.php");
require_once(MODULE_DIR."/common/SuperiorSendTable.class.php");
require_once(MODULE_DIR."/common/SuperiorMultiTable.class.php");
require_once(MODULE_DIR."/common/SuperiorFileTable.class.php");
require_once(MODULE_DIR."/common/SectionTable.class.php");
require_once(MODULE_DIR."/common/SectionusersTable.class.php");
require_once(MODULE_DIR."/common/NewsTable.class.php");

require_once(MODULE_DIR."/common/ArchiveTable.class.php");
require_once(MODULE_DIR."/common/ArchiveAddrTable.class.php");
require_once(MODULE_DIR."/common/ArchiveFileTable.class.php");
require_once(MODULE_DIR."/common/ArchiveExportTable.class.php");
require_once(MODULE_DIR."/common/DomainTable.class.php");
require_once(MODULE_DIR."/common/FileformatTable.class.php");

// 一斉配信機能
require_once(MODULE_DIR."/common/DeliveryTable.class.php");
require_once(MODULE_DIR."/common/DeliveryFileTable.class.php");
require_once(MODULE_DIR."/common/DeliveryToTable.class.php");

// 二要素認証機能
require_once(MODULE_DIR."/common/TwoStepAuthBrowserTable.class.php");
//管理者側：二要素認証機能
require_once(MODULE_DIR . "/common/AdminTwostepauthTable.class.php");
require_once(MODULE_DIR . "/common/BackupcodeTable.class.php");
require_once(MODULE_DIR . "/common/AdminTwostepauthBrowserTable.class.php");

// レポートメール機能
require_once(MODULE_DIR."/common/ReportMailTable.class.php");

// お気に入りフォルダ
require_once(MODULE_DIR."/common/FavoriteFoldersTable.class.php");

//容量の推移通知
require_once(MODULE_DIR . "/common/CapacityLogTable.class.php");

// Pear
require_once('String/Random.php');
require_once('Mail/RFC822.php');
require_once('Net/IPv4.php');
if (defined("PEAR_MAIL_USE")) {
    require_once('Mail.php');
}else {
    require_once(MODULE_DIR."/common/MailSend.class.php");
}
// ライブラリ
require_once(MODULE_DIR."/common/StrChange.class.php");
require_once(MODULE_DIR."/common/password.class.php");
require_once(MODULE_DIR."/common/CrossBrowser.class.php");
require_once(MODULE_DIR."/common/Proxy.class.php");
require_once(MODULE_DIR."/common/FileUtil.class.php");
require_once(MODULE_DIR."/common/db_common.class.php");
require_once(MODULE_DIR."/common/ZipCreator.class.php");

// ライセンス
{
    if (function_exists("ioncube_file_is_encoded") && ioncube_file_is_encoded()) {
        if (is_array(ioncube_license_properties()))
        foreach(ioncube_license_properties() as $key => $val2) {
            $val = strval($val2["value"]);
            if ($key === 'MAXUSER') {
                define("MAX_USER",$val);
            }
            if ($key === 'APPURL') {
                define("APP_URL",$val);
            }
            if ($key === 'APPURLINSIDE') {
                define("APP_URL_INSIDE",$val);
            }
            if ($key === 'APPURLOUTSIDE') {
                define("APP_URL_OUTSIDE",$val);
            }
            if ($key === 'APPPATH') {
                define("APP_PATH",$val);
            }
            if ($key === 'SYSMAXSIZE') {
                define("SYS_MAX_SIZE",$val);
            }
            if ($key === 'SHAREGROUPFLG') {
                define("SHARE_GROUP_FLG",$val);
            }
            if ($key === 'SHAREMAXCAPACITY') {
                define("SHARE_MAX_CAPACITY",$val);
            }
            if ($key === 'SUPERIOROPTIONFLG') {
                define("SUPERIOR_OPTION_FLG",$val);
            }
            if ($key === 'EXTRASPACEFLG') {
                define("EXTRA_SPACE_FLG",$val);
            }
            if ($key === 'ADFLG') {
                define("AD_FLG",$val);
            }
            if ($key === 'EVIARCHIVEFLG') {
                define("EVI_ARCHIVE_FLG",$val);
            }
            if ($key === 'EVIARCHIVEMAXSIZE') {
                define("EVI_ARCHIVE_MAX_SIZE",$val);
            }
            if ($key === 'EVIARCHIVEEXPORTMAXSIZE') {
                define("EVI_ARCHIVE_EXPORT_MAX_SIZE",$val);
            }
            if ($key === 'VIRUSCHECKFLG') {
                define("VIRUS_CHECK_FLG",$val);
            }
            if ($key === 'APIFLG') {
                define('API_FLG',$val);
            }
            if ($key === 'APIMAXUSER') {
                define('API_MAX_USER',$val);
            }
            if ($key === 'CHINESEFLG') {
                define("CHINESE_FLG",$val);
            }
            if ($key === 'CLIENTAUTHFLG') {
                define("CLIENT_AUTH_FLG",$val);
            }
            if ($key === 'DELIVERYFLG') {
                define("DELIVERY_FLG",$val);
            }
            if ($key === 'DELIVERYMAILMAXLIMIT') {
                define("DELIVERY_MAIL_MAX_LIMIT",$val);
            }
            if ($key === 'DELIVERYFILEMAXLIMIT') {
                define("DELIVERY_FILE_MAX_LIMIT",$val);
            }
            if ($key === 'GATHERINGFLG') {
                define("GATHERING_FLG", $val);
            }
            if ($key === 'OUTLOOKADDINFLG') {
                define("OUTLOOK_ADDIN_FLG",$val);
            }
        }
    }

    // ライセンス読み込み後に system.phpを読み込む
    include_once(MODULE_DIR."/common/system.php");

    //ユーザ登録数
    if (!defined("MAX_USER")) {
        define("MAX_USER",0);
    }

    //共有オプション
    if (!defined("SHARE_GROUP_FLG")) {
        define("SHARE_GROUP_FLG",0);
    }
}

// APP_URL
if (!defined("APP_URL")) {
    // 現在のURLを作成
    $url = "";
    if (@$_SERVER['HTTPS']) {
        $url .= "https://";
    }
    else {
        $url .= "http://";
    }
    $url .= $_SERVER['HTTP_HOST'];
    // APP_URL_* に現在のURLがあるかチェック
    //  ⇒ある：APP_URLに$urlをセット、⇒ない：APP_URL_INSIDE > APP_URL_OUTSIDEの一番左のURLをセット
    $urlex = @explode(",",APP_URL_INSIDE.",".APP_URL_OUTSIDE);
    $defaultval = "";
    if (is_array($urlex)) foreach($urlex as $val) {
        $val = trim($val);
        if ( $defaultval == "" && $val != "") {
            $defaultval = $val;
        }
        if ( $val == $url) {
            $defaultval = $val;
            break;
        }
    }
    if ($defaultval != "") {
        define("APP_URL",$defaultval);
    }
}
// APP_URL_INSIDE, APP_URL_OUTSIDE
if (!defined("APP_URL_INSIDE")) {
    define("APP_URL_INSIDE", APP_URL);
}
if (!defined("APP_URL_OUTSIDE")) {
    define("APP_URL_OUTSIDE", APP_URL);
}

//ログインのフォーム名を指定
if (!defined('LOGIN_FORM_NAME')) {
    define('LOGIN_FORM_NAME',"login");
}

// 定数設定
define("USER_LOGIN_URL", "https://ml.key-p.com/wf/");    //会員ログインURL

define( "USER_PASS_LEN",6);            //システムが発行するパースワードの長さ
define( "DL_PASS_LEN",10);            //ダウンロードに発行されるパスワードの長さ
define( "INIT_PAY_LIMIT",14);          //初期登録支払う期限
define( "AFFERIATE_UP_SIZE",10485760);  //紹介キャンペーン　アップサイズ
define( "HANSHIN_SE1_CP_2005",10485760); //優勝記念

// 有料ユーザのアップロード制限量(M) ※64bitサーバでは最大6000まで指定可
if (!defined("UPLOAD_LIMIT_1")) {
    define( "UPLOAD_LIMIT_1", 500 );
}
define( "UPLOAD_LIMIT_2", 50 );        // 無料ユーザのアップロード制限量(M)
define( "MAX_AD_FILE",2);              //広告画像ファイル制限量(M)

define( "SUPORT_MAIL", "wf@key-p.com");  // 問い合わせ・サポートメール送信先
if (!defined("SYS_FROM")) {
    define( "SYS_FROM" , "wf@key-p.com");    // システムからのメールFromアドレス
}
if (!defined("MAIL_DOMAIN") || !MAIL_DOMAIN) {
    define("MAIL_DOMAIN","webfile.jp");
}
define( "SYS_RETURN_PATH","wf-error@".MAIL_DOMAIN); //エラー処理をするメールアドレス

define( "CHECKSUM_LENGTH", 20);        // ダウンロードURLのチェックサムの長さ

define( "EVI_TIMEOUT_MAIL_SUBJECT",           "[".SYS_NAME."] アーカイブ:タイムアウト通知");
define( "EVI_AUTONOTICE_MAIL_SUBJECT",        "[".SYS_NAME."] アーカイブ:アーカイブ容量超過のお知らせ");
define( "EVI_EXPORT_AUTONOTICE_MAIL_SUBJECT", "[".SYS_NAME."] アーカイブ:エクスポート容量超過のお知らせ");
define( "EVI_ARCHIVE_ERROR_MAIL_SUBJECT",     "[".SYS_NAME."] アーカイブ:アーカイブに失敗しました");
define( "EVI_EXPORT_MAIL_SUBJECT",            "[".SYS_NAME."] アーカイブ:エクスポート完了のお知らせ");
define( "EVI_EXPORT_ERROR_MAIL_SUBJECT",      "[".SYS_NAME."] アーカイブ:エクスポートに失敗しました");
define( "USER_INFORM_SUBJECT",      "[".SYS_NAME."] ユーザ情報のお知らせ");
define( "UNLOCK_MAIL_SUBJECT",           "[".SYS_NAME."] ファイルのロック解除のお知らせ");
define( "CAPACITY_ALERT_SUBJECT", "[".SYS_NAME."] システム利用容量警告");
define("CAPACITY_NOTICE_SUBJECT", "[" . SYS_NAME . "] フォルダ使用容量についてのお知らせ");
define( "CONTINUOUSLOGIN_MAIL_SUBJECT",     "[".SYS_NAME."] 連続ログイン通知"); // 2017/12/08 @yanai 機能追加
define( "REJECTION_MAIL_SUBJECT",           "[".SYS_NAME."] 承認待ちメールの上長却下通知");
define( "REJECTION_MAIL_SUBJECT_EN",        "[".SYS_NAME."] Notification to which superior rejected approval waiting mail");
define( "REJECTION_MAIL_SUBJECT_CN",        "[".SYS_NAME."] 待批邮件被上级驳回的通知");
define( "REJECTION_MAIL_SUBJECT_TCN",       "[".SYS_NAME."] 待批郵件被上級駁回的通知");
define( "TWO_STEP_CODE_MAIL_SUBJECT",         "[" . SYS_NAME . "] 二要素認証の確認コードのお知らせ");

define( "ADDR_LIST_NUM",10); //アドレス帳一覧の表示する件数

define( "LOG_LIST_NUM",10); //履歴一覧の表示する件数

define( "AFFERIATE_REPORT_START_YEAR",2005);//アフィリエイト月別表示　開始年
define( "AFFERIATE_REPORT_START_MONTH",8);//アフィリエイト月別表示　開始月

//メールアドレス大文字小文字区別 1:区別する 0:区別しない
if (!defined('MAIL_CAP_OPTION')) {
    define('MAIL_CAP_OPTION',"0");
}

//管理者メール送信　詳細一覧の表示する件数
define( "ADMIN_MAIL_DETAIL_LIST",10);
//管理者メール送信　履歴一覧の表示する件数
define( "ADMIN_MAIL_HISTROY_LIST",10);
//管理者メール送信　履歴一覧のサブジェクトの長さ
define("ADMIN_MAIL_HISTORY_LIST_SUBJECT_LEN",26);
//管理者メール送信　サブジェクトの代わり文字
define("ADMIN_MAIL_HISTORY_LIST_SUBJECT_CHANGE","････");

//"用テンプレート"の名称
define("FOR_TEMPLATE","用テンプレート");
//"用"の名称
define("FOR_STR","用");
//ダウンロードの日本語名称
define("DOWNLOAD_STR","配布");
//アップロードの日本語名称
define("UPLOAD_STR","収集");
//配布・収集用の日本語名称
define("SENDCOLLECT_STR","配布・収集");
//配布用 ワンタイムパスワード用の日本語名称
define("DOWNLOAD_ONETIMEPASSWORD_STR",DOWNLOAD_STR."ワンタイムパスワード");
//配布用 ワンタイムパスワード用の日本語名称
define("UPLOAD_ONETIMEPASSWORD_STR",UPLOAD_STR."ワンタイムパスワード");
//配布用 ワンタイムパスワード用の日本語名称
define("DOWNLOAD_ONETIMEPASSWORD_SMS_STR","ワンタイムパスワード(SMS)");
//配布用 ワンタイムパスワード用の日本語名称
define("UPLOAD_ONETIMEPASSWORD_SMS_STR",UPLOAD_STR."ワンタイムパスワード(SMS)");
//二要素認証の確認コード用の日本語名称
define("CONFIRMATION_CODE_STR","二要素認証確認コード");
//ヘッダーの日本語名称
define("HEADER_STR","ヘッダー");
//フッターの日本語名称
define("FOOTER_STR","フッター");

// TEMPLATE_PATH
if (!defined("TEMPLATE_PATH_EN")) {
    define("TEMPLATE_PATH_EN", TEMPLATE_PATH."/en");
}
if (!defined("TEMPLATE_PATH_CN")) {
    define("TEMPLATE_PATH_CN", TEMPLATE_PATH."/cn");
}
if (!defined("TEMPLATE_PATH_TCN")) {
    define("TEMPLATE_PATH_TCN", TEMPLATE_PATH."/tcn");
}

// SMSにおける最大文字数
if (!defined("SMS_MAX_MESSAGE_COUNT")) {
    define("SMS_MAX_MESSAGE_COUNT", 68);
}

// GMail メール送信者ガイダンス対応
define("LIST_UNSUBSCRIBE_POST", "List-Unsubscribe=One-Click");
define("LIST_UNSUBSCRIBE", "<" . APP_URL . APP_PATH . "/index.php?action=unsubscribe>");
/************************************
 * 以下　管理画面の作成用
 ************************************/
//ユーザ管理画面　ユーザ一覧の表示する件数
define("USER_LIST_NUM",10);
//ユーザ管理画面 ユーザ一覧の表示するデフォルト件数(10,20,30,50,100から選択する。初期値は30)
if (!defined("USER_PER_PAGE_DEFAULT")) {
  define("USER_PER_PAGE_DEFAULT", 30);
}
//CSVファイルでユーザ追加の時、CSVファイルサイズの制限(MB)
define("MAX_UPLOAD_CSV_FILE",2*1024*1024);
//ファイル削除可選　開始月
define("FILE_DEL_START_YEAR",2006);
//ログファイルの保管
define("HISTORY_PATH", "/history");
//EVIの定期削除
if (defined("EVI_ARCHIVE_FLG") && EVI_ARCHIVE_FLG == 1) {
    define("EVI_PID_PATH", WEBAPP_DIR . "/../htdocs/pid"); // ロックファイル保存場所
    if (!defined("EVI_ARCHIVE_MAX_SIZE_B")) {
        define("EVI_ARCHIVE_MAX_SIZE_B", EVI_ARCHIVE_MAX_SIZE*1024*1024*1024);  //Evi-アーカイブ Byte変換(変更不要)
    }
    if (!defined("EVI_ARCHIVE_EXPORT_MAX_SIZE_B")) {
        define("EVI_ARCHIVE_EXPORT_MAX_SIZE_B", EVI_ARCHIVE_EXPORT_MAX_SIZE*1024*1024*1024);  //Evi-エクスポート Byte変換(変更不要)
    }
    // Evi エクスポート ログファイルパス
    define("EVI_LOG_PATH", LOG_DIR.'/evi.log');
 }
// Eviアーカイブエクスポート時(自動・手動)、分割ファイル1つあたりのサイズ(MB)
// 1800以下で設定してください
if (!defined("EVI_FILE_SIZE_MB")) {
    define("EVI_FILE_SIZE_MB", 1800);
}
// Evi自動エクスポートのテスト用定数
// 0:本番、1:テスト
if (!defined("TEST_EVI_REGULAR_EXPORT")) {
    define("TEST_EVI_REGULAR_EXPORT", 0);
}

// DB
define("DB_TYPE","mysqli");
define("DB_CHARSET","utf8mb4");
define("DNS",DB_TYPE."://".DB_USER.":".DB_PASS."@".DB_HOST."/".DB_NAME. "?charset=". DB_CHARSET);

// サーバチェック
if (!defined("ZIPDL_TYPE")) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windowsサーバ
        define("ZIPDL_TYPE", '0');
    } else {
        // Linuxサーバ
        define("ZIPDL_TYPE", '1');
    }
}

// IPアドレス取得時に、WebサーバがProxy構成かを考慮
if (! defined("REVERSE_PROXY_FLG")) {
    define("REVERSE_PROXY_FLG", 0);
}

// 上長承認オプション default0:OFF
if (!defined("SUPERIOR_OPTION_FLG")) {
    define("SUPERIOR_OPTION_FLG", 0);
}
// 上長最大登録数
if (!defined('MAX_SUPERIOR')) {
    define('MAX_SUPERIOR',5);
}
if (!defined('DEFAULT_CAN_LOGIN_WITHOUT_SUPERIOR')) {
    define('DEFAULT_CAN_LOGIN_WITHOUT_SUPERIOR', 1); //上長なしでのログイン許可のデフォルト。1なら許可
}

// マニュアルとヘルプの表示可否
if (!defined("MANUAL_DL_FLG")) {
    define("MANUAL_DL_FLG", 0);
}
// 管理者管理機能ON/OFF 0:OFF 1:ON
if (!defined("ADMIN_MANAGER_FLG")) {
    define("ADMIN_MANAGER_FLG",1);
 }

// ユーザアカウントロック 失敗回数
if (!defined("ACCOUNT_LOCK_CNT")) {
    define("ACCOUNT_LOCK_CNT",0);
}
// ユーザアカウントロック 解除分
if (!defined("ACCOUNT_LOCK_TIME")) {
    define("ACCOUNT_LOCK_TIME",0);
}
// 管理者アカウントロック 失敗回数
if (!defined("ADMIN_ACCOUNT_LOCK_CNT")) {
    define("ADMIN_ACCOUNT_LOCK_CNT",0);
}
// 管理者アカウントロック 解除分
if (!defined("ADMIN_ACCOUNT_LOCK_TIME")) {
    define("ADMIN_ACCOUNT_LOCK_TIME",0);
}
// 上書きフラグ
if (!defined("OVERWRITE_FLG")) {
    define("OVERWRITE_FLG",0);
}
// Evi
if (!defined("EVI_ARCHIVE_FLG")) {
    define("EVI_ARCHIVE_FLG",0);
}
if (!defined("COM_PHP_PATH")) {
    define("COM_PHP_PATH",PHP_PATH);
}
// 拡張容量
if (!defined("EXTRA_SPACE_FLG")) {
    define("EXTRA_SPACE_FLG",0);
}
define("EVI_PROC_TIMEOUT", 300); // 待機中のプロセスのタイムアウト秒

//シェルパス
define('WF_SHELL_PATH', WEBAPP_DIR . '/../bin/');

// 共有フォルダの管理ユーザ
if (!defined("SHARE_ADMIN_MUST_FLG")) {
    define("SHARE_ADMIN_MUST_FLG",0);
}

//有効期限
if (!defined("TERM_OF_VALIDITY")) {
    define("TERM_OF_VALIDITY",1);
}

// WebFile Lite:ファイルの定期削除
if (!defined("AUTO_DEL_FILE_STOP_FLG")) {
    define("AUTO_DEL_FILE_STOP_FLG", 0);
}
// WebFile Lite:配布ダウンロードの最大人数
if (!defined("SENDS_COLLECT_MAX_NUMBER")) {
    define("SENDS_COLLECT_MAX_NUMBER", 0);
}
// WebFile Lite:履歴ログの自動削除日数
if (!defined("AUTO_DEL_LOG_TERM")) {
    define("AUTO_DEL_LOG_TERM", 0);
}

// AD連携
if (!defined("AD_FLG")) {
    define("AD_FLG",0);
}

// ADユーザ一括登録
if (!defined("AD_PACKAGE")) {
    define("AD_PACKAGE",0);
}
// ADユーザ一括登録の管理ユーザのOU(署名必須のOpenLDAP用)
if (!defined("ADMIN_OU")) {
    define("ADMIN_OU","");
}

// AD側で削除されて停止状態になったADアカウントがAD側で復活した場合、停止状態を解除するフラグ
if (!defined("AD_PACKAGE_RESTART")) {
    define("AD_PACKAGE_RESTART",0);
}

// CSV
if (!defined("USERCSV_ACCOUNT_CHANGE_FLG")) {
    define("USERCSV_ACCOUNT_CHANGE_FLG", 0);
}

//ファイルロック機能
if (!defined("FILE_LOCK_OPTION_FLG")) {
    // 2014/04/07@yano 共有グループ＆上書きがONの場合のみ有効にする
    if (OVERWRITE_FLG == 1 && SHARE_GROUP_FLG == 1) {
        define("FILE_LOCK_OPTION_FLG", 1);
    } else {
        define("FILE_LOCK_OPTION_FLG", 0);
    }
}

//共有フォルダの有効期限
if (!defined("SHARE_FOLDER_TERM_REQUIRED_FLG")) {
    define("SHARE_FOLDER_TERM_REQUIRED_FLG",0);
 }
if (!defined("SHARE_FOLDER_TERM_DEFAULT_SPAN")) {
    define("SHARE_FOLDER_TERM_DEFAULT_SPAN",0);
 }

// 追加する独自ヘッダ
if (!defined('MAIL_EXTRA_HEADERS')) {
    define("MAIL_EXTRA_HEADERS", "");
}
// SMTPサーバを指定してメール送信
if (defined('SMTP_FLG') && SMTP_FLG) {
    // 接続するサーバ名
    if (!defined('SMTP_HOST')) {
        define('SMTP_HOST', 'localhost');
    }
    // 接続するポート番号
    if (!defined('SMTP_PORT')) {
        define('SMTP_PORT', 25);
    }
    // SMTP認証を使用するかどうか
    if (!defined('SMTP_AUTH')) {
        define('SMTP_AUTH', FALSE);
    }
    // SMTP認証で使用するユーザ名
    if (!defined('SMTP_USERNAME')) {
        define('SMTP_USERNAME', '');
    }
    // SMTP認証で使用するパスワード
    if (!defined('SMTP_PASSWORD')) {
        define('SMTP_PASSWORD', '');
    }

    // SMTPログファイルパス
    define("SMTP_LOG_PATH", LOG_DIR.'/smtp.log');
}

//CSVユーザ追加パスワード必須チェック設定
if (!defined("PASSWORD_REQUIRED_FLG")) {
    define("PASSWORD_REQUIRED_FLG",0); //0:必須チェック有り
}

//ユーザ管理画面の利用容量エクスポートボタン
if (!defined("CAPA_EXPORT_FLG")) {
    define("CAPA_EXPORT_FLG", 1); // 0:表示しない、1:表示する
}

// メールによる自動更新
if (!defined("AUTO_UPDATE_FLG")) {
    define("AUTO_UPDATE_FLG",0);
}
//添付ファイル解凍用パスワードの頭部分
if (!defined("AUTO_UPDATE_PASS")) {
    define("AUTO_UPDATE_PASS", "");
}
//共有容量制限機能ON/OFF
if (! defined('SHARE_SPACE_FLG')) {
    define("SHARE_SPACE_FLG" , 0);
}
if(!defined("BACKUPCLIENT_NAME")){
    define("BACKUPCLIENT_NAME","バックアップマネージャー");
}

// ロゴ変更
if (!defined("CHANGE_LOGO_FLG")) {
    define("CHANGE_LOGO_FLG",1);
}

// 中国語
if (!defined("CHINESE_FLG")) {
    define("CHINESE_FLG",0);
}

// 一斉配信機能
if (!defined("DELIVERY_FLG")) {
    define("DELIVERY_FLG", 0);
}
// 一斉配信の最大送信人数
if (!defined("DELIVERY_MAIL_MAX_LIMIT")) {
    define("DELIVERY_MAIL_MAX_LIMIT", 20000);
}
// 一斉配信の最大送信ファイル数
if (!defined("DELIVERY_FILE_MAX_LIMIT")) {
    define("DELIVERY_FILE_MAX_LIMIT", 10);
}

// ワンタイムパスワード
if (!defined("ONE_TIME_PASSWORD_LIMIT_MINUTES")) {
    define("ONE_TIME_PASSWORD_LIMIT_MINUTES", 10); // ワンタイムパスワードの有効期限（分）
}

// 二要素認証機能
if (!defined("TWO_STEP_AUTH_FLG")) {
    define("TWO_STEP_AUTH_FLG", 1);
}
// 管理者側二要素認証
if (!defined("ADMIN_TWO_STEP_AUTH_FLG")) {
    define("ADMIN_TWO_STEP_AUTH_FLG", 1);
}
if (!defined("ADMIN_TWO_STEP_AUTH_CRYPT_KEY")) {
    // 旧環境ですでに作成済みの場合はそのまま利用する。
    $crypt_key = hash('sha256', APP_URL . APP_PATH . ADMIN_PATH);
    define("ADMIN_TWO_STEP_AUTH_CRYPT_KEY", $crypt_key);
}
// 二要素認証の認証アプリ名
define('TWO_STEP_AUTH_AUTHENTICATOR_APPLICATION_NAME_ANDROID', 'Google 認証システム');
define('TWO_STEP_AUTH_AUTHENTICATOR_APPLICATION_NAME_IOS', 'Google Authenticator');

// ファイルの無害化
if (!defined("FILE_SANITIZE_FLG")) {
    define("FILE_SANITIZE_FLG", 0);
}
// 無害化前ファイル保持の変更機能
if (!defined("SHOW_SANITIZATION_SETTINGS_FLG")) {
    define("SHOW_SANITIZATION_SETTINGS_FLG", 0);
}

//MYSQLエラーログをLOG_DIRに出す
if (!defined("MYSQL_ERROR_LOG_FLG")) {
    define("MYSQL_ERROR_LOG_FLG",1);
}

//API
if (!defined("API_FLG")) {
    define("API_FLG", 0);
}
if (!defined("API_MAX_USER")) {
    define("API_MAX_USER",0);
}
// API名 (クライアントを利用せず、APIだけ利用する場合はAPIのまま)
// ※変更時はhelp lib.phpも合わせて変更する
if (!defined("API_CLIENT_NAME")) {
    define("API_CLIENT_NAME", "API");
}
// APIのパス
if (!defined("API_PATH")) {
    define("API_PATH", "/api/1.0");
}
// APIのbaseURL
if (!defined("API_BASEURL")) {
    define("API_BASEURL", APP_URL . APP_PATH . API_PATH);
}
// APIのタイムアウト時間(ms)
if (!defined("API_TIMEOUT")) {
    define("API_TIMEOUT", 5000);
}

// OUTLOOK_ADDIN
if (!defined("OUTLOOK_ADDIN_FLG")) {
    define("OUTLOOK_ADDIN_FLG", 0);
}
if (!defined("OUTLOOK_ADDIN_MANIFEST_FILE_PATH")) {
    define("OUTLOOK_ADDIN_MANIFEST_FILE_PATH", WF_PATH."/webapp/modules/common/outlook/addin/manifest.prod.xml");
}
//Chrome拡張
if (!defined("CHROME_ADDIN_FLG")) {
    define("CHROME_ADDIN_FLG", 0);
}

// 送信待機
if (!defined("SEND_WAIT_FLG")) {
   define("SEND_WAIT_FLG",1);
}

// クライアント認証機能
if (!defined("CLIENT_AUTH_FLG")) {
    define("CLIENT_AUTH_FLG",0);
}

// 通常ログイン
if (!defined("LOGIN_FLG")) {
    define("LOGIN_FLG", 1);
}

// Google SSO認証
if (!defined("SSO_FLG")) {
    define("SSO_FLG", 0);
}

/************************************************************
 * asp rms版用の対応(Poscoユーザcsvファイルをダウンロードできるように)
 ************************************************************/
if (!defined("POSCO_USER_CSV_FLG")) {
    define("POSCO_USER_CSV_FLG",0);
    define("POSCO_USER_EXPORT_DIR", ""); //csvファイル保管場所
    define("POSCO_USER_EXPORT_FILE", ""); //csvファイル名
}

/*************************************
 * ウイルスチェックのファイルサイズ制限
 ************************************/
// ウイルスチェックを行なうファイルのサイズ上限(MB)
// ※この容量を超えるファイルはウイルスチェックしない
if (!defined('VIRUS_CHECK_SIZE_LIMIT')) {
    define("VIRUS_CHECK_SIZE_LIMIT", 2000);
}
if (!defined('VIRUS_CHECK_FLG')) {
    define("VIRUS_CHECK_FLG", 0);
}

// 2017/12/04 @yanai 連続ログイン通知追加
// 連続ログイン計測時間
if (!defined("CONTINUOUS_LOGIN_SPAN_MINUTE")) {
    define("CONTINUOUS_LOGIN_SPAN_MINUTE", 1);
}
// 通知を行う連続ログイン回数
if (!defined("CONTINUOUS_LOGIN_NUM")) {
    define("CONTINUOUS_LOGIN_NUM", 20);
}
// 通知方法
if (!defined("CONTINUOUS_LOGIN_NOTICE_EACH_TIME_FLG")) {
    define("CONTINUOUS_LOGIN_NOTICE_EACH_TIME_FLG",0);
}

// 2018/03/09@yanai SAML2.0認証に対応
// 認証方式 SAML2.0
if (!defined("SAML_FLG")) {
    define("SAML_FLG", 0);
}
// X.509 Certificateのパス
if (!defined("SAML_IDP_X509CERTIFICATE_FILE")) {
    define("SAML_IDP_X509CERTIFICATE_FILE", "/var/wf-file/SAML/IdP_X509_Certificate.pem");
}

if (!defined("SMS_PASS_FLG")) {
    define("SMS_PASS_FLG", 0);
}
if (!defined("SESSION_SECURE")) {
    define("SESSION_SECURE", 1);
}

/************************************************************
 * var_dumpをpreタグ付きで出力　
 ************************************************************/
function v_dump($data)
{
    print "<pre>";
    var_dump($data);
    print "</pre>";
}

require_once(MODULE_DIR."/common/file_put_contents.php");
define("MAIL_TPL_ORG_DIR", "org");

// PHP80対応, each関数が消えたので再定義（ポインタ関連なので都度コード修正したくない）
if (!function_exists('each'))
{
    /**
     * php8-compatibility of each function.
     *
     * @see https://www.php.net/each
     * @param array
     * @return array
     */
    function each(&$arr)
    {
        $key = key($arr);
        $value = current($arr);
        $result = $key === null ? false : array($key, $value, 'key' => $key, 'value' => $value);
        next($arr);

        return $result;
    }
}

// PHP80対応, 定義されていない定数を参照するとエラーになる問題の対策
if (!defined("GATHERING_FLG")) {
    define("GATHERING_FLG", 0);
}
// 一斉収集の最大送信人数
if (!defined("GATHERING_MAIL_MAX_LIMIT")) {
    define("GATHERING_MAIL_MAX_LIMIT", 100);
}
// 一斉収集の最大送信ファイル数
if (!defined("GATHERING_FILE_MAX_LIMIT")) {
    define("GATHERING_FILE_MAX_LIMIT", 10);
}

//ロゴ
if (!defined('LOGO_PREFIX')) {
  define('LOGO_PREFIX', "");
}
if (file_exists(MODULE_DIR . "/../../htdocs/images/" . LOGO_PREFIX . "logo.gif")) {
    define("LOGO", APP_PATH . ADMIN_PATH . "/images/" . LOGO_PREFIX . "logo.gif");
} else {
    define("LOGO", APP_PATH . ADMIN_PATH . "/images/default_logo.gif");
}
// KPニュース設定表示
if (!defined("SHOW_KP_NEWS_CONFIG")) {
    define("SHOW_KP_NEWS_CONFIG", 1);
}
// 環境別カスタマイズ設定
if (!defined("FAVICON_PREFIX")) {
    define("FAVICON_PREFIX", '');
}
if (!defined("FTF_FLG")) {
    define("FTF_FLG", '');
}
// メールアドレス重複登録許可
if (!defined("DUPLICATE_EMAIL_ENABLE_FLG")) {
    define("DUPLICATE_EMAIL_ENABLE_FLG", 0);
}
if (!defined("USE_ENV_SEPARATION_FLG")) {
    define("USE_ENV_SEPARATION_FLG", 0);
}

// 手動パスワード変更の無効化
if (!defined("MANUAL_PASSWORD_CHANGE_DISABLED")) {
  define("MANUAL_PASSWORD_CHANGE_DISABLED", 0);
}

?>
