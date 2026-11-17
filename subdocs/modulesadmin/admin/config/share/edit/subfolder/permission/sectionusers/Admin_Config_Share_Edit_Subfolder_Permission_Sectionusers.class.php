<?php
require_once( MODULE_DIR."/common/common.php");

class Admin_Config_Share_Edit_Subfolder_Permission_Sectionusers {
    var $session;
    var $DtoUser;
    var $DtoSection;
    var $MdbSection;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Subfolder_Permission_Sectionusersが実行されました","Admin_Config_Share_Edit_Subfolder_Permission_Sectionusers#execute");

        // 数値じゃない
        if (!is_numeric($this->DtoSection->getSection_id())) {
            return "error";
        }
        
        // 部署情報を取得
        if (!$this->MdbSection->getInfo($this->DtoSection)) {
            return "error";
        }
        
        return "success";
    }

    // setter
    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }

    // getter
    function getSection_id() {
        return $this->DtoSection->getSection_id();
    }
    function getSection_name() {
        return $this->DtoSection->getSection_name();
    }

}
?>