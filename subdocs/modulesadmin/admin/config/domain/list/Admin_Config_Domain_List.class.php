<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_List {
    var $DtoDomain;
    var $MdbDomain;
    var $DomainLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Listが実行されました","Admin_Config_Domain_List#execute");
        
        echo $this->DomainLogic->getUserDomainJSON($this->MdbDomain, $this->DtoDomain);
        exit;
    }
    // setter
    function setDtoDomain($DtoDomain){
        $this->DtoDomain = $DtoDomain;
    }
    function setMdbDomain($MdbDomain) {
        $this->MdbDomain = $MdbDomain;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
    }

    // getter
}
?>