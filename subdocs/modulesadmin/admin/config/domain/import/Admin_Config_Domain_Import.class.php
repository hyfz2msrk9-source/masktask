<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Import {

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Importが実行されました","Admin_Config_Domain_Import#execute");
        return "success";
    }
    // setter
    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
}
?>