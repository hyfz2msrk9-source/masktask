<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - エクスポート
 * @updater     Hiroko Yanai（2018/04/12）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Export {
    var $session;
    var $DtoUser;
    var $MdbUser;
    var $userDto;
    var $userLogic;
    var $DtoLog;
    var $MdbLog;
    var $MdbAdminuser;
    var $DtoAdminuser;
    var $MdbConfigs;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Exportが実行されました", "Admin_User_Export#execute");

        if ( $this->session->getParameter("login_admin_level") == "3" ) {
            $admin_id = $this->session->getParameter("admin_id");
        } else {
            $admin_id = 0;
        }
        $this->userLogic->user_export(
            $this->DtoUser,$this->MdbUser,$this->userDto,
            $this->DtoAdminuser, $this->MdbAdminuser, $admin_id,
            $this->CommonList
        );

        // Log
        $history_id = 116; // 116:ユーザ管理＞ユーザ一覧のエクスポート
        $memo = "";
        $memo.= "件数：" . $this->DtoUser->getRow_count() . "件";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setUserDto($userDto) {
        $this->userDto = $userDto;
    }
    function setUserLogic($UserLogic) {
        $this->userLogic = $UserLogic;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
    function setMdbAdminuser($MdbAdminuser ) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setDtoAdminuser($DtoAdminuser ) {
        $this->DtoAdminuser = $DtoAdminuser;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    //************************************************************
    // getter
    //************************************************************



}
?>
