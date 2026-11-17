<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ履歴
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Log {
    var $session;
    var $DtoUser;
    var $UserLogic;
    var $MdbUser;
    var $UserDto;
    var $MdbLog;
    var $MdbUpload;
    var $MdbCollect;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $MdbMchoice;
    var $DtoLog;
    var $DtoUpload;
    var $DtoCollect;
    var $CommonList;
    var $MdbFolder;
    var $DtoFolder;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Log","Admin_User_Log#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // ユーザ情報取得
        $where = array("user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'","del_flg='0'");
        if(!$this->MdbUser->getUserInfo2($where, $this->DtoUser)) {
            return "input";
        }
        if($this->DtoUser->getUser_id()=="") {
            //存在しないID
            return "input";
        }
        // 既に削除されているユーザ
        if($this->DtoUser->getDel_flg() == '1') {
            return "input";
        }
        // 編集権限なし
        if($this->session->getParameter("login_admin_level") == "3"  &&  $this->DtoUser->getOwner_admin_id() != $this->session->getParameter("admin_id")) {
            $errorList->add("Admin_User_Edit_Confirm::error","このアカウントで追加したユーザではないため、表示できません。");
            return "input";
        }
        $this->DtoLog->setUser_id($this->DtoUser->getUser_id());
        $this->MdbLog->getLogList3($this->DtoLog,$this->UserDto);
        if ($this->DtoLog->getLogList()) {
            $this->userLogic->showlist2($this->DtoLog,
                                        $this->MdbUpload,$this->DtoUpload,
                                        $this->MdbCollect,$this->DtoCollect,
                                        $this->MdbMchoice,$this->MdbDownload,
                                        $this->MdbFolder,$this->DtoFolder,
                                        $this->MdbDownloadMailer,
                                        $this->MdbDeliveryto,$this->MdbDeliveryfile
                                    );
        }
        return "success";
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
    function setUserDto($UserDto){
        $this->UserDto = $UserDto;
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
    function setCommonList($CommonList) {
    	$this->CommonList = $CommonList;
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
    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoUser->getAccount();
    }
    function getUser_name() {
        return $this->DtoUser->getUser_name();
    }
    function getUser_id() {
        return $this->DtoUser->getUser_id();
    }
    function getLog_num() {
    	return $this->UserDto->getLog_num();
    }
    function getLog_num_list() {
    	return  $this->CommonList->getList($this->MdbMchoice,"log_num");
    }
    function getLogList() {
        return $this->DtoLog->getLogList();
    }
    function getLogSpan() {
        $log_span = $this->MdbLog->getLogSpan2($this->DtoUser->getUser_id());
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
