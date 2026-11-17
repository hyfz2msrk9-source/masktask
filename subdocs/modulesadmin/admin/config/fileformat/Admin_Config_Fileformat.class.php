<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Fileformat {
    var $session;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Filedelが実行されました","Admin_Config_Fileformat#execute");

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
    function getList_Type_Name_Options() {
        return GlobalArray::getList_Type_Name_Options();
    }
    function getFileformat_type() {
        return $this->MdbConfigs->getDirectValue("fileformat_type");
    }
}