<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Virus_List {
    var $session;
    var $DtoFlexi;
    var $MdbVirusLog;
    var $DtoVirusLog;
    var $VirusLogic;
    var $VirusDto;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Virus executed.","Admin_Virus#execute");

        // 一覧取得
        $this->VirusLogic->getFlexiList($this->MdbVirusLog, $this->DtoVirusLog,$this->DtoFlexi, $this->VirusDto);
        echo $this->VirusLogic->getFlexiList2($this->MdbVirusLog, $this->DtoVirusLog,$this->DtoFlexi, $this->VirusDto);
        exit();
    }

    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setMdbVirusLog($MdbVirusLog) {
        $this->MdbVirusLog = & $MdbVirusLog;
    }
    function setDtoVirusLog($DtoVirusLog) {
        $this->DtoVirusLog = & $DtoVirusLog;
    }
    function setVirusLogic($VirusLogic) {
        $this->VirusLogic = & $VirusLogic;
    }
    function setVirusDto($VirusDto) {
        $this->VirusDto = & $VirusDto;
    }

    //getter
    function getLogList()
    {
        return $this->DtoVirusLog->getViruslogList();
    }
}
?>