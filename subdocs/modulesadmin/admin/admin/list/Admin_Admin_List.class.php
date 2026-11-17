<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者一覧(JSON)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_List {
    var $session;

    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminuserLogic;
    var $DtoFlexi;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_List executed.","Admin_Admin_List#execute");

        // パラメータセット
        if ($this->DtoFlexi->getSortname() == "user_id") {
            $this->DtoFlexi->setSortname("admin_id");
        }
        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());
        // 一覧の取得
        $this->DtoAdminuser->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoAdminuser->setLogin_admin_level($this->session->getParameter('login_admin_level'));

        $this->MdbAdminuser->getFlexiAdminUserList($this->DtoAdminuser, $this->DtoFlexi, $this->AdminuserDto);
        echo $this->AdminuserLogic->getFlexiList(
                                                 $this->MdbAdminuser, $this->DtoAdminuser,
                                                 $this->DtoFlexi, $this->AdminuserDto
                                                 );
        exit();
    }

    function setSession(&$session) {
        $this->session =& $session;
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
}
?>
