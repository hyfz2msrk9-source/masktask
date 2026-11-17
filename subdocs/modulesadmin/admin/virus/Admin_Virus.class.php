<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Virus {
    var $session;
    var $DtoVirusLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Virus executed.","Admin_Virus#execute");

        return "success";
    }
    //setter
    function setSession($session) {
        $this->session = $session;
    }
    function getVirus_check_size_limit() {
        return StrChange::CapacityUnit(VIRUS_CHECK_SIZE_LIMIT*1024*1024);
    }
    function setDtoVirusLog($DtoVirusLog) {
        $this->DtoVirusLog = & $DtoVirusLog;
    }

    //getter
    function getLogList()
    {
        if ($this->DtoVirusLog) { // Admin_Virus_ListでDtoVirusLogがセットされている
            return $this->DtoVirusLog->getViruslogList();
        }
    }
}
?>