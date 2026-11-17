<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Leadersearch {
    var $session;
    var $DtoUser;
    var $MdbUser;

    var $search3;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Leadersearchが実行されました","Admin_Config_Share_Leadersearch#execute");

        $this->MdbUser->getSearchUser($this->DtoUser,$this->search3);
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
    function setSearch3($search3)
    {
        $this->search3 = $search3;
    }

    // getter
    function getSearch3()
    {
        return $this->search3;
    }
    function getResult(){
        return $this->DtoUser->userlist;
    }
}
?>