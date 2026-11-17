<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Search {
    var $DtoUser;
    var $MdbUser;
    var $DomainLogic;

    // request
    var $keyword;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Searchが実行されました","Admin_Config_Domain_Search#execute");

        echo $this->DomainLogic->getSearchUserJSON($this->MdbUser, $this->DtoUser, $this->keyword);
        exit;
    }
    // setter
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
    }
    function setKeyword($keyword){
        $this->keyword = $keyword;
    }

    // getter
}
?>