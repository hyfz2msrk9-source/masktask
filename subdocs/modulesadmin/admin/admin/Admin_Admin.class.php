<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者一覧
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin {
    var $session;

    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminuserLogic;
    var $MdbConfigs;

    var $PageLink;
    var $admin_list_back;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin","Admin_Admin#execute");

        if (!$this->admin_list_back) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
            $this->AdminuserDto->setPage(0);
        }
        $this->session->setParameter("page",$this->AdminuserDto->getPage());
        // 一覧の取得
        $this->AdminuserLogic->getList($this->MdbAdminuser,$this->DtoAdminuser,$this->AdminuserDto);

        return "success";
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
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
    }

    function setPageLink($PageLink) {
        $this->PageLink = & $PageLink;
    }
    function setAdmin_list_back($admin_list_back) {
        $this->admin_list_back = $admin_list_back;
    }
    /************************************************************
     * ユーザ一覧情報取得
     ************************************************************/
    function getAdminuserList() {
        return $this->DtoAdminuser->getAdminuserList();
    }
    function getPage() {
        return $this->PageLink->getLink($this->AdminuserDto);
    }
    function getAdmin_account_lock_cnt(){
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
    }
     /*
     function getSystem_max() {
        return SYS_MAX_SIZE;
     }
    */
}
?>
