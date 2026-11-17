<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ履歴(JSON)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Log_List {
    var $session;

    var $DtoUser;
    var $MdbUser;
    var $UserLogic;
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
    var $MdbFolder;
    var $DtoFolder;
    var $DtoFlexi;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Log_List executed.","Admin_User_Log_List#execute");

        $uid = $this->DtoUser->getUser_id();
        if( preg_match("/[^0-9]/",$uid) ) {
            //Not Numeric
            echo '{"total":0,"page":"","rows":{}}';
            exit();
        }

        // ユーザ存在チェック
        $where = array("user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'","del_flg='0'");
        if(!$this->MdbUser->getExistCheck($where)) {
            echo '{"total":0,"page":"","rows":{}}';
            exit();
        }

        // ログリスト取得
        $this->DtoLog->setUser_id($this->DtoUser->getUser_id());
        $this->MdbLog->getFlexiLogList3($this->DtoLog, $this->DtoFlexi, $this->UserDto);
        echo $this->userLogic->flexishowlist2($this->DtoLog, $this->DtoFlexi,
                                              $this->UserDto, $this->MdbUpload,
                                              $this->DtoUpload, $this->MdbCollect,
                                              $this->DtoCollect, $this->MdbMchoice,
                                              $this->MdbDownload, $this->MdbDownloadMailer,
                                              $this->MdbFolder, $this->DtoFolder,
                                              $this->MdbDeliveryto,$this->MdbDeliveryfile);
        exit();
    }
    /*******************************
     * setter
     *******************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setUserLogic($UserLogic) {
        $this->userLogic = $UserLogic;
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
    function setDtoFlexi($DtoFlexi){
        $this->DtoFlexi = $DtoFlexi;
    }
    function setMdbDownloadMailer($MdbDownloadMailer) {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
}
?>
