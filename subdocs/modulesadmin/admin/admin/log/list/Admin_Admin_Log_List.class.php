<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者履歴(JSON)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Log_List {

    var $session;

    var $DtoAdminuser;
    var $AdminuserLogic;
    var $AdminuserDto;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoLog;
    var $DtoFlexi;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Log_List","Admin_Admin_Log_List#execute");

        $uid = $this->DtoAdminuser->getAdmin_id();
        if( preg_match("/[^0-9]/",$uid) ) {
            //Not Numeric
            echo '{"total":0,"page":"","rows":{}}';
            exit();
        }

        // ログリスト取得
        $this->DtoLog->setAdmin_id($this->DtoAdminuser->getAdmin_id());
        if( $this->DtoAdminuser->getAdmin_id()=="") {
            echo '{"total":0,"page":"","rows":{}}';
            exit();
        }
        $this->MdbLog->getFlexiLogAdminList($this->DtoLog, $this->DtoFlexi, $this->AdminuserDto);
        echo $this->AdminuserLogic->flexishowlist(
                                                  $this->DtoLog, $this->DtoFlexi,
                                                  $this->AdminuserDto, $this->MdbMchoice
                                                  );
        exit();
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
    function setAdminuserDto($AdminuserDto){
        $this->AdminuserDto = $AdminuserDto;
    }
    function setMdbLog($MdbLog){
        $this->MdbLog = $MdbLog;
    }
    function setMdbMchoice($MdbMchoice){
        $this->MdbMchoice = $MdbMchoice;
    }
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setDtoFlexi($DtoFlexi){
        $this->DtoFlexi = $DtoFlexi;
    }
}
?>
