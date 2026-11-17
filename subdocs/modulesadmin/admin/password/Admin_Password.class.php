<?php
/**
 * パスワード変更
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KEYPOINT.INC
 * @access      public
 **/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Password {
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Passwordが実行されました","Admin_Password#execute");

        return "success";
    }

    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    function getPass_max_num() {
        return $this->MdbConfigs->getDirectValue("pass_max_num");
    }

    function getPass_min_num() {
        return $this->MdbConfigs->getDirectValue("pass_min_num");
    }

}
?>
