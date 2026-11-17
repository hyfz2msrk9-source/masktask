<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Edit_Subfolder_Permission_Userlist {

    // maple:action
    var $session;
    var $DtoUser;
    var $DtoFolder;
    var $DtoGroups;
    var $FolderLogic;
    var $DtoFlexi;

    //request
    var $type;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Subfolder_Permission_Userlistが実行されました","Admin_Config_Share_Edit_Subfolder_Permission_Userlist#execute");

        $group_id = $this->session->getParameter("id");
        $sub_folder_id = $this->session->getParameter("sub_folder_id");

        //権限リストのデータ
        $ret = $this->FolderLogic->displayPowerlistByShareFolderAtJSON($this->DtoFlexi, $sub_folder_id);
        exit();
    }

    // Setter
    function setSession(&$session) {
        $this->session = & $session;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setDtoFolder(&$DtoFolder) {
        $this->DtoFolder = & $DtoFolder;
    }
    function setDtoGroups(&$DtoGroups) {
        $this->DtoGroups = & $DtoGroups;
    }
    function setFolderLogic(&$FolderLogic) {
        $this->FolderLogic = & $FolderLogic;
    }
    function setDtoFlexi(&$DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setType($type) {
        $this->type = $type;
    }

    // Getter
    function getSession() {
        return $this->session;
    }
    function getDtoUser() {
        return $this->DtoUser;
    }
    function getDtoFolder() {
        return $this->DtoFolder;
    }
    function getDtoGroups() {
        return $this->DtoGroups;
    }
    function getFolderLogic() {
        return $this->FolderLogic;
    }
    function getDtoFlexi() {
        return $this->DtoFlexi;
    }
}
?>
