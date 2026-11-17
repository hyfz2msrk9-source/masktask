<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Access_Csv {

    var $session;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Csvが実行されました", "Admin_Config_Access_Csv#execute");

        return "success";
    }
    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    //************************************************************
    // getter
    //************************************************************

}
?>
