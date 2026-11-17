<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Done {
    var $session;
    var $DtoAdminLog;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Done","Admin_Log_Done#execute");

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 日付のチェック
        if( ! preg_match("/^[0-9]+\/[0-9]+\/[0-9]+$/",$this->DtoAdminLog->getDeleteDate())) {
            $errorList->add("Admin_Log_Done::Error", "日付の引数が正しくありません。");
            return "error";
        }

        $this->MdbLog->deleteLog($this->DtoAdminLog);

        // Log
        $history_id = 108; // 108:ログ管理＞ログ削除
        $memo  = "";
        $memo  = $this->DtoAdminLog->getDeleteDate()."より前のログを削除";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);
        
        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoAdminLog(&$DtoAdminLog) {
        $this->DtoAdminLog =& $DtoAdminLog;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }
}
?>
