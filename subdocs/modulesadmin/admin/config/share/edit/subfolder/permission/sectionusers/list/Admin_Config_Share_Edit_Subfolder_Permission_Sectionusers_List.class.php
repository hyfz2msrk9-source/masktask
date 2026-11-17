<?php
require_once( MODULE_DIR."/common/common.php");

class Admin_Config_Share_Edit_Subfolder_Permission_Sectionusers_List {
    var $session;
    var $DtoFlexi;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoUser;
    var $MdbUser;
    var $SectionLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Subfolder_Permission_Sectionusers_Listが実行されました","Admin_Config_Share_Edit_Subfolder_Permission_Sectionusers_List#execute");

        // 数字チェック
        if (!is_numeric($this->DtoSection->getSection_id())) {
            exit();
        }

        // 所属ユーザ一覧取得
        if ($this->MdbSection->getInfo($this->DtoSection)) {
            $this->DtoSectionusers->setSection_id($this->DtoSection->getSection_id());
            $this->MdbSectionusers->getFlexiSectionUserList($this->DtoSectionusers,$this->DtoFlexi);
            echo $this->SectionLogic->getFlexiSectionUserList($this->DtoSectionusers,
                                                              $this->DtoFlexi);
        }
        exit();
    }

    // setter
    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoFlexi(&$DtoFlexi) {
        $this->DtoFlexi =& $DtoFlexi;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoSectionusers($DtoSectionusers) {
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
}
?>