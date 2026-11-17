<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Usersearch {
    var $session;
    var $DtoUser;
    var $MdbUser;

    var $search;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Usersearchが実行されました","Admin_Config_Share_Usersearch#execute");

        $this->MdbUser->getSearchUser($this->DtoUser,$this->search);
        if (empty($this->DtoUser->userlist)) {
            return "nohit";
        }
        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setSearch($search)
    {
        $this->search = $search;
    }

    // getter
    function getSearch()
    {
        return $this->search;
    }
    function getResult(){
        return $this->DtoUser->userlist;
    }
}
?>