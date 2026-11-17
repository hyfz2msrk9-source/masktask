<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ一覧(JSON)
 * @updater     Hiroko Yanai（2018/04/09）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_List {
    var $session;

    var $MdbUser;
    var $DtoUser;
    var $UserDto;
    var $UserLogic;
    var $MdbCollect;
    var $MdbUpload;
    var $DtoUpload;
    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminuserLogic;
    var $DtoFlexi;
    var $MdbConfigs;
    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_List executed.","Admin_User_List#execute");

        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());
        // 一覧の取得
        if ( $this->session->getParameter("login_admin_level") == "3" ) {
            $admin_id = $this->session->getParameter("admin_id");
        } else {
            $admin_id = 0;
        }
        $this->UserLogic->getFlexiList($this->MdbUser, $this->DtoUser, $this->DtoFlexi, $this->UserDto, $admin_id);
        echo $this->UserLogic->getFlexiList2(
            $this->DtoUser, $this->DtoFlexi,
            $this->UserDto, $this->MdbUpload,
            $this->MdbCollect, $this->DtoUpload,
            $this->MdbAdminuser,$this->DtoAdminuser,$this->AdminuserDto,$this->AdminuserLogic,
            $this->MdbConfigs,
            $this->CommonList
        );
        exit();
    }
    /*******************************
     * setter
     *******************************/
    function setSession(&$session) {
        $this->session =& $session;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setUserDto($UserDto) {
        $this->UserDto = & $UserDto;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = & $UserLogic;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = & $MdbCollect;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = & $MdbUpload;
    }
    function setDtoUpload($DtoUpload) {
        $this->DtoUpload = & $DtoUpload;
    }
    function setMdbAdminuser(&$MdbAdminuser) {
        $this->MdbAdminuser = & $MdbAdminuser;
    }
    function setDtoAdminuser($DtoAdminuser) {
        $this->DtoAdminuser = & $DtoAdminuser;
    }
    function setAdminuserDto($AdminUserDto) {
        $this->AdminuserDto = & $AdminUserDto;
    }
    function setAdminuserLogic($AdminuserLogic) {
        $this->AdminuserLogic = & $AdminuserLogic;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
}
?>
