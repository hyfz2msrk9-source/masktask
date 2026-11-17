<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ履歴エクスポート(全件)
 * @updater     Hiroko Yanai（2018/04/12）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Log_Csv {
    var $session;
    var $DtoUser;
    var $UserLogic;
    var $MdbUser;
    var $MdbLog;
    var $MdbUpload;
    var $MdbCollect;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $MdbMchoice;
    var $DtoLog;
    var $DtoUpload;
    var $DtoCollect;
    var $MdbFolder;
    var $DtoFolder;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Log_Csv","Admin_User_Log_Csv#execute");

        if ($this->DtoUser->getUser_id()) {
            // ユーザ情報取得
            $where = array("user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'","del_flg='0'");
            if (!$this->MdbUser->getUserInfo2($where, $this->DtoUser)) {
                return "input";
            }
            $this->DtoLog->setUser_id($this->DtoUser->getUser_id());
            $this->MdbLog->getLogList($this->DtoLog);
            if ($this->DtoLog->getLogList()) {
                $this->userLogic->showlist2(
                    $this->DtoLog,
                    $this->MdbUpload,$this->DtoUpload,
                    $this->MdbCollect,$this->DtoCollect,
                    $this->MdbMchoice,$this->MdbDownload,
                    $this->MdbFolder,$this->DtoFolder,
                    $this->MdbDownloadMailer,
                    $this->MdbDeliveryto,$this->MdbDeliveryfile
                );
            }
        }
        $this->userLogic->log_output($this->DtoLog,$this->DtoUser);

        // Log
        $history_id = 105; // 105:ユーザ管理＞ユーザの操作履歴＞エクスポート
        $memo  = "";
        $memo  = "対象ユーザ\n";
        $memo .= "　アカウント：".$this->DtoUser->getAccount()."\n";
        $memo .= "　氏名：".$this->DtoUser->getUser_name()." ";
        $memo .= "(".$this->DtoUser->getUser_kana().")\n ";
        $memo .= "　メールアドレス：".$this->DtoUser->getMailaddr()."\n";
        $memo .= "　容量：".($this->DtoUser->getMax_file_size()/1024/1024)."MB\n";
        $memo .= "　メモ：".$this->DtoUser->getMemo()."\n";
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
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setUserLogic($UserLogic) {
        $this->userLogic = $UserLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
    function setMdbUpload($MdbUpload){
    	$this->MdbUpload = $MdbUpload;
    }
    function setMdbCollect($MdbCollect){
    	$this->MdbCollect = $MdbCollect;
    }
    function setMdbDownload($MdbDownload){
    	$this->MdbDownload = $MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer) {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbMchoice($MdbMchoice){
    	$this->MdbMchoice = $MdbMchoice;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }
    function setDtoUpload($DtoUpload){
    	$this->DtoUpload = $DtoUpload;
    }
    function setDtoCollect($DtoCollect){
    	$this->DtoCollect = $DtoCollect;
    }
    function setMdbFolder($MdbFolder){
    	$this->MdbFolder = $MdbFolder;
    }
    function setDtoFolder($DtoFolder){
    	$this->DtoFolder = $DtoFolder;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
}
?>
