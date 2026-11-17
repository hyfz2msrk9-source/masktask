<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_Edit_List {
    var $session;
    var $DtoFlexi;
    var $SuperiorLogic;
    var $UserDto;
    var $MdbUser;
    var $DtoUser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Virus executed.","Admin_Virus#execute");

        // 一覧取得
        $this->SuperiorLogic->getUserSuperiorFlexiList($this->DtoFlexi,$this->MdbUser,$this->DtoUser,$this->UserDto);
        echo $this->SuperiorLogic->getUserSuperiorFlexiList2($this->DtoFlexi, $this->UserDto, $this->DtoUser);
        exit();
    }

    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setSuperiorLogic($SuperiorLogic) {
        $this->SuperiorLogic = & $SuperiorLogic;
    }
    function setUserDto($UserDto) {
        $this->UserDto = & $UserDto;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
}
?>