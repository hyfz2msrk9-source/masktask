<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者履歴エクスポート(月)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Log_Csv_Month {
    var $session;
    var $DtoAdminuser;
    var $AdminuserLogic;
    var $MdbAdminuser;
    var $MdbLog;
    /*
    var $MdbUpload;
    var $MdbCollect;
    var $MdbDownload;
    */
    var $MdbMchoice;
    var $DtoLog;
    var $month;
    /*
    var $DtoUpload;
    var $DtoCollect;
    */

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Log_Csv_Month","Admin_Admin_Log_Csv_Month#execute");

        if ($this->DtoAdminuser->getAdmin_id()) {
            $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);
            $this->DtoLog->setAdmin_id($this->DtoAdminuser->getAdmin_id());
            $this->MdbLog->getLogAdminListAll($this->DtoLog, substr($this->month,0,4)."-".substr($this->month,4,2));
            if ($this->DtoLog->getLogList()) {
                $this->AdminuserLogic->showlist($this->DtoLog,
                                           $this->MdbMchoice);
                                        /*
                                        $this->MdbUpload,$this->DtoUpload,
                                        $this->MdbCollect,$this->DtoCollect,
                                        $this->MdbMchoice,$this->MdbDownload);
                                        */
            }
        }
        $this->AdminuserLogic->log_output($this->DtoLog,$this->DtoAdminuser,$this->month);

        // Log
        $history_id = 110; // 110:ユーザ管理＞管理者管理＞管理者の操作履歴＞エクスポート
        $memo  = "";
        $memo  = "対象ユーザ\n";
        $memo .= "　アカウント：".$this->DtoAdminuser->getAccount()."\n";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoAdminuser($DtoAdminuser) {
        $this->DtoAdminuser = $DtoAdminuser;
    }
    function setAdminuserLogic($AdminuserLogic) {
        $this->AdminuserLogic = $AdminuserLogic;
    }
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
    /*
    function setMdbUpload($MdbUpload){
    	$this->MdbUpload = $MdbUpload;
    }
    function setMdbCollect($MdbCollect){
    	$this->MdbCollect = $MdbCollect;
    }
    function setMdbDownload($MdbDownload){
    	$this->MdbDownload = $MdbDownload;
    }
    */
    function setMdbMchoice($MdbMchoice){
    	$this->MdbMchoice = $MdbMchoice;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }
    function setMonth($month) {
        $this->month = $month;
    }
    /*
    function setDtoUpload($DtoUpload){
    	$this->DtoUpload = $DtoUpload;
    }
    function setDtoCollect($DtoCollect){
    	$this->DtoCollect = $DtoCollect;
    }
    */
}
?>
