<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ一覧(ログイン成功でaction:admin_user)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Top {
    var $session;
    var $DtoAdminuser;
    var $MdbAdminuser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Top executed.","Admin_Top#execute");

        // user data set
        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);

        return "success";
    }

    function setSession(&$session) {
        $this->session =& $session;
    }
    function setMdbAdminuser (&$MdbAdminuser) {
        $this->MdbAdminuser =& $MdbAdminuser;
    }
    function setDtoAdminuser (&$DtoAdminuser) {
        $this->DtoAdminuser =& $DtoAdminuser;
    }

    function getInputAccount(){
        return "";
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
