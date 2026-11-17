<?php

/**
 * MapleのBaseディレクトリの設定
 */
define('BASE_DIR', dirname(dirname(__FILE__)));

/**
 * Debugフィルターを発動させるかどうかの設定
 */
define('DEBUG_MODE', 0);

/**
 * Mapleの設定の読込み
 **/
require_once BASE_DIR . "/webapp/config/maple.inc.php";

/**
 * 関連ファイルの読込み
 */
require_once MODULE_DIR . "/common/common.php";

/**
 * 独自設定
 **/
if (defined('TEST_MODE') && TEST_MODE == 1) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    define('LOG_LEVEL', LEVEL_TRACE);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    define('LOG_LEVEL', LEVEL_FATAL);
}
$_SERVER['REQUEST_METHOD'] = 'POST';
define('ACTION_KEY',         'action');
define('CONFIG_FILE',        'maple.ini');
define('DEFAULT_ACTION',     'admin_check_folder');

/**
 * フレームワーク起動
 **/
$controller = new Controller();
$controller->execute();

/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
