<?php
require_once(MODULE_DIR. "/common/common.php");

class Admin_Config_Fileformat_List {
    var $DtoFileformat;
    var $FileformatLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Fileformat_Listが実行されました","Admin_Config_Fileformat_List#execute");

        echo $this->FileformatLogic->getFileformatList($this->DtoFileformat);

        exit;
    }

    // setter
    function setDtoFileformat($DtoFileformat) {
        $this->DtoFileformat = $DtoFileformat;
    }
    function setFileformatLogic($FileformatLogic) {
        $this->FileformatLogic = $FileformatLogic;
    }
}