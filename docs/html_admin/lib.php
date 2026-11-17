<?php
//error_reporting(E_ALL);
error_reporting(0);

// system.phpを読み込む
require_once(realpath(dirname(__FILE__)."/../../../webapp/modules/common/system.php"));

// set define
if (!defined("API_CLIENT_NAME")) define("API_CLIENT_NAME", "Synchrorer");
if (!defined("BACKUPCLIENT_NAME")) define("BACKUPCLIENT_NAME", "バックアップエージェント");

// 文字列を取得しておく
if(strstr(basename($_SERVER['PHP_SELF'],".html"),"-en")){
    $sys_name = SYS_NAME_EN;
    $sys_name_sub = SYS_NAME_SUB_EN;
} else {
    $sys_name = SYS_NAME;
    $sys_name_sub = SYS_NAME_SUB;
}
// output_handler定義
function webfile_output_handler($buffer) {
    global $sys_name;
    global $sys_name_sub;
    $buffer = str_replace("WebFile", $sys_name, $buffer);
    $buffer = str_replace("ASP", $sys_name_sub, $buffer);
    return $buffer;
}
// outputバッファ開始
ob_start("webfile_output_handler");
define("WF_HELP_PATH",realpath(dirname(__FILE__)."/../"));
$path = "../"; 
?>
