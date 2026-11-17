<?php
//error_reporting(E_ALL);
error_reporting(0);

// common.phpを読み込む
define("WEBAPP_DIR", realpath(dirname(__FILE__) . "/../../../webapp"));
define("MAPLE_DIR", realpath(dirname(__FILE__) . "/../../../maple"));
define("LOG_DIR", WEBAPP_DIR . "/logs");
define("MODULE_DIR", WEBAPP_DIR . "/modules");
set_include_path(get_include_path() . PATH_SEPARATOR . MAPLE_DIR . "/lib/pear");
require_once(MODULE_DIR . "/common/common.php");

// 文字列を取得しておく
if(strstr(basename($_SERVER['PHP_SELF'],".html"),"-en")){
    $sys_name = SYS_NAME_EN;
    $sys_name_sub = SYS_NAME_SUB_EN;
    $saml_name = SAML_NAME_EN;
    $saml_button_name = SAML_BUTTON_NAME_EN;
} else if(strstr(basename($_SERVER['PHP_SELF'],".html"),"-cn")){
    $sys_name = SYS_NAME_CN;
    $sys_name_sub = SYS_NAME_SUB_CN;
    $saml_name = SAML_NAME_CN;
    $saml_button_name = SAML_BUTTON_NAME_CN;
} else if(strstr(basename($_SERVER['PHP_SELF'],".html"),"-tcn")){
    $sys_name = SYS_NAME_TCN;
    $sys_name_sub = SYS_NAME_SUB_TCN;
    $saml_name = SAML_NAME_TCN;
    $saml_button_name = SAML_BUTTON_NAME_TCN;
} else {
    $sys_name = SYS_NAME;
    $sys_name_sub = SYS_NAME_SUB;
    $saml_name = SAML_NAME;
    $saml_button_name = SAML_BUTTON_NAME;
}

// output_handler定義
function webfile_output_handler($buffer) {
    global $sys_name;
    global $sys_name_sub;
    global $saml_name;
    global $saml_button_name;
    $buffer = str_replace("WebFile", $sys_name, $buffer);
    $buffer = str_replace("ASP", $sys_name_sub, $buffer);
    $buffer = str_replace("[##SAML_NAME##]", $saml_name, $buffer);
    $buffer = str_replace("[##SAML_BUTTON_NAME##]", '[' . $saml_button_name . ']', $buffer);

    return $buffer;
}
// outputバッファ開始
ob_start("webfile_output_handler");
define("WF_HELP_PATH",realpath(dirname(__FILE__)."/../"));
// メニューの切替フラグ 0:WFユーザ 1:外部
$customer_flg = 0;
?>
