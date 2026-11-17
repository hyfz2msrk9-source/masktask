<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Logout {
    
    var $session;
    var $DtoLog;
    var $MdbLog;
    var $MdbUser;
    var $DtoUser;
    var $UserLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Logoutが実行されました", "Admin_Logout#execute");

        // Log
        $history_id = 21; // 21:ログアウト
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo("");
        $this->MdbLog->Logging($this->DtoLog);

        $this->session->remove();
        $this->session->close();

        return "success";
    }
    
    function setSession($session) {
        $this->session =& $session;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = &$MdbUser;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = &$DtoUser;
    }
    function setUserLogic(&$UserLogic) {
        $this->UserLogic = &$UserLogic;
    }
    function getOver_max_user() {
        if(MAX_USER != 0) {
            $cnt = $this->UserLogic->getUserActiveCont($this->DtoUser,$this->MdbUser);
            if(MAX_USER <= $cnt) {
                return 1;
            }
        } else {
            return 0;
        }
    }
}
/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>
