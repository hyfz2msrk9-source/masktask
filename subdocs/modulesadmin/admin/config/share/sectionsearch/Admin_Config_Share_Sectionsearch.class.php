<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Sectionsearch {
    var $session;
    var $DtoSection;
    var $MdbSection;

    var $search2;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Sectionsearchが実行されました","Admin_Config_Share_Sectionsearch#execute");

        $this->MdbSection->getSearchSection($this->DtoSection,$this->search2);
        if (empty($this->DtoSection->section_list)) {
            return "nohit";
        }
        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setSearch2($search2)
    {
        $this->search2 = $search2;
    }

    // getter
    function getSearch2()
    {
        return $this->search2;
    }
    function getResult(){
        return $this->DtoSection->section_list;
    }
}
?>