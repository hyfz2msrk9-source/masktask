<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Autoupdate {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Autopudateが実行されました","Admin_Config_AutoUpdate#execute");

        if(!defined("AUTO_UPDATE_FLG") || (defined("AUTO_UPDATE_FLG") && AUTO_UPDATE_FLG == '0')){
            return "error";
        }

        return "success";
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    /***************************************************
     * getter
     **************************************************/
    function getValue() {
        return $this->MdbConfigs->getDirectValue("mail_addr");
    }
}

?>
