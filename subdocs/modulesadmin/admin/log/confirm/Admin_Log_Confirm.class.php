<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Confirm {
    var $session;
    var $deleteYear;
    var $deleteMonth;
    var $deleteDay;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Confirm","Admin_Log_Confirm#execute");

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 日付のチェック
        if( ! preg_match("/^[0-9]+\/[0-9]+\/[0-9]+$/",$this->getDeleteDate())) {
            $errorList->add("Admin_Log_Confirm::Error", "日付の引数が正しくありません。");
            return "error";
        }

        $this->DtoLog->setDeleteDate($this->getDeleteDate());
        $this->MdbLog->deleteLogCount($this->DtoLog);
        
        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDeleteYear($deleteYear) {
        $this->deleteYear = $deleteYear;
    }
    function setDeleteMonth($deleteMonth) {
        $this->deleteMonth = $deleteMonth;
    }
    function setDeleteDay($deleteDay) {
        $this->deleteDay = $deleteDay;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }

    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoUser->getAccount();
    }
    function getDeleteDate() {
        return $this->deleteYear."/" .$this->deleteMonth."/".$this->deleteDay;
    }
    function getDeleteCount() {
        return $this->DtoLog->getCount();
    }
    function getSpan_value() {
        return "";
    }
}
?>