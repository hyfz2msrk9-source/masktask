<?php
/**
 * パスワード強制変更
 * @updater
 *
 * @package     WebFile
 * @version     3.16.0
 * @author      Hiroko Yanai（2018/10/01）
 * @copyright   KEYPOINT.INC
 * @access      public
 **/
require_once( MODULE_DIR . '/common/common.php');

class Admin_Password_Must {

    var $session;
    var $mailaddr;
    var $password;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Password_Mustが実行されました","Admin_Password_Must#execute");

        return "success";
    }

    function setSession($session) {
        $this->session =& $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
}
?>
