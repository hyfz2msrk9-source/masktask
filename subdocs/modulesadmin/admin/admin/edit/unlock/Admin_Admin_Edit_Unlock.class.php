<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者のロック解除?
 * @updater
 *
 * @package     WebFile
 * @version     3.19.0時点で不使用
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Edit_Unlock {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $MdbConfigs;
    var $admin_unlock_token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Edit_Unlock","Admin_Admin_Edit_Unlock#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Edit_Unlock::error", "管理者の編集機能は、現在、使用できません。");
            $this->session->removeParameter("AdminUnlockToken");
            return "error";
        }
        //URL直接入力された場合の対処
        if (!$this->session->getParameter("AdminUnlockToken") ||
            ($this->session->getParameter("AdminUnlockToken") != $this->admin_unlock_token)) {
            $errorList->add("Admin_Admin_Edit_Unlock::error", "不正な操作が確認されました。");
            $this->session->removeParameter("AdminUnlockToken");
            return "error";
        }
        if(!$this->session->getParameter("edit_admin_id")){
            $errorList->add("Admin_Admin_Edit_Unlock::error", "管理者情報を取得できませんでした。");
            $this->session->removeParameter("AdminUnlockToken");
            return "error";
        }

        $this->DtoAdminUser->setAdmin_id($this->session->getParameter("edit_admin_id"));
        //自分自身は編集できないように
        if($this->DtoAdminUser->getAdmin_id() == $this->session->getParameter("admin_id")){
            $errorList->add("Admin_Admin_Edit_Unlock::error", "選択された管理者は編集できません。");
            $this->session->removeParameter("AdminUnlockToken");
            return "error";
        }
        // 管理者情報取得
        $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
        if(!$this->DtoAdminUser->getAdmin_id()) {
            $errorList->add("Admin_Admin_Edit_Unlock::error", "管理者情報を取得できませんでした。");
            $this->session->removeParameter("AdminUnlockToken");
            return "error";
        }

        // ロックを解除する
        $this->MdbAdminUser->UnlockAccount($this->DtoAdminUser);

        $this->session->removeParameter("AdminUnlockToken");
        return "success";
    }

    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbAdminUser(&$MdbAdminUser) {
        $this->MdbAdminUser = & $MdbAdminUser;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = & $DtoAdminUser;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setAdmin_unlock_token($admin_unlock_token) {
        $this->admin_unlock_token = $admin_unlock_token;
    }

    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoAdminUser->getAccount();
    }
    function getAccount_lock_flg() {
        return $this->DtoAdminUser->getAccount_lock_flg();
    }
    function getAccount_lock_flg_org() {
        return $this->session->getParameter('account_lock_flg_org');
    }
    function getAdmin_account_lock_cnt(){
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
    }
    function getLevel() {
        return $this->DtoAdminUser->getLevel();
    }

}
?>
