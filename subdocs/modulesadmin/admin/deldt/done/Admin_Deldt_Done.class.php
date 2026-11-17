<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Deldt_Done {
    var $session;
    var $Mdbdeletedt;
    var $DtoLog;
    var $MdbLog;
    var $ListLogic;
    var $deldts;
    var $okmsg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Deldt_Done","Admin_Deldt_Done#execute");

        $this->Mdbdeletedt->delete_DelDt();
        $this->Mdbdeletedt->upload_DelDt($this->deldts);

        $this->okmsg = "削除の日数を登録しました。";

        $this->MdbLog->getLogSpan($this->DtoLog);

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbdeletedt(&$Mdbdeletedt) {
        $this->Mdbdeletedt =& $Mdbdeletedt;
    }
    function setDeldts(&$deldts) {
        $this->deldts =& $deldts;
    }
    function setOkmsg(&$okmsg) {
        $this->okmsg =& $okmsg;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }
    function setLoguser(&$loguser){
        $this->loguser =& $loguser;
    }
    function setListLogic(&$ListLogic){
        $this->ListLogic =& $ListLogic;
    }


    /************************************
    * getter
    *************************************/
    function getDeldts(){
        return $this->deldts;
    }
    function getOkmsg(){
        return $this->okmsg;
    }
    function getLogSpan() {
        return $this->DtoLog->getLogspan();
    }
    function getLoguser(){
        return $this->ListLogic->getLoguser();
    }
    function getLoguser_flg(){
        return 1;
    }

}
?>