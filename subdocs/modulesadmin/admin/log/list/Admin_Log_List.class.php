<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_List {
    var $session;

    var $MdbLog;
    var $DtoFlexi;
    var $AdminLogLogic;
    
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_List executed.","Admin_Log_List#execute");

        $this->AdminLogLogic->getFlexiLogList($this->DtoFlexi);
        exit();
    }
    /*******************************
     * setter
     *******************************/
    function setSession(&$session) {
        $this->session =& $session;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setAdminLogLogic($AdminLogLogic) {
        $this->AdminLogLogic = $AdminLogLogic;
    }
}
?>
