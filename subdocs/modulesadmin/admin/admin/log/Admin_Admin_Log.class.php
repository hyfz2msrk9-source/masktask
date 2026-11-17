<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者履歴
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Log {

    var $session;
    var $DtoAdminuser;
    var $AdminuserLogic;

    var $MdbAdminuser;
    var $AdminuserDto;
    var $MdbLog;
    /*
    var $MdbUpload;
    var $MdbCollect;
    var $MdbDownload;
    */
    var $MdbMchoice;
    var $DtoLog;
    /*
    var $DtoUpload;
    var $DtoCollect;
    */
    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Log","Admin_Admin_Log#execute");

        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);

        if($this->DtoAdminuser->getAdmin_id()=="") {
            //存在しないID
            return "input";
        }

        $this->DtoLog->setAdmin_id($this->DtoAdminuser->getAdmin_id());
        $this->MdbLog->getLogAdminList($this->DtoLog,$this->AdminuserDto);
        if ($this->DtoLog->getLogList()) {
            $this->AdminuserLogic->showlist($this->DtoLog,$this->MdbMchoice);
            /*
            $this->AdminuserLogic->showlist($this->DtoLog,
                                        $this->MdbUpload,$this->DtoUpload,
                                        $this->MdbCollect,$this->DtoCollect,
                                        $this->MdbMchoice,$this->MdbDownload);
            */
        }

        return "success";
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
    function setAdminuserDto($AdminuserDto){
        $this->AdminuserDto = $AdminuserDto;
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
    /*
    function setDtoUpload($DtoUpload){
    	$this->DtoUpload = $DtoUpload;
    }
    function setDtoCollect($DtoCollect){
    	$this->DtoCollect = $DtoCollect;
    }
    */
    function setCommonList($CommonList) {
    	$this->CommonList = $CommonList;
    }

    /************************************
    * getter
    *************************************/

    function getAccount() {
        return $this->DtoAdminuser->getAccount();
    }
    function getAdmin_id() {
        return $this->DtoAdminuser->getAdmin_id();
    }
    function getLog_num() {
    	return $this->AdminuserDto->getLog_num();
    }
    function getLog_num_list() {
    	return  $this->CommonList->getList($this->MdbMchoice,"log_num");
    }
    function getLogList() {
        return $this->DtoLog->getLogList();
    }
    function getLogSpan() {
        $log_span = $this->MdbLog->getLogSpan2(NULL,$this->DtoAdminuser->getAdmin_id());
        $ret = array();
        foreach($log_span as $val){
            foreach($val as $key2 => $val2){
                $ret[$key2] = $val2;
            }
        }
        return $ret;
    }
}
?>
