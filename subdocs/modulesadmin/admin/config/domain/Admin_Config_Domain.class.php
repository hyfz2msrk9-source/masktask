<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain {
    var $session;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domainが実行されました","Admin_Config_Domain#execute");

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }

    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
}
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
?>