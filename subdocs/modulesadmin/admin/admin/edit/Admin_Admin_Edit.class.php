<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の編集
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Edit {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $MdbConfigs;
    var $edit_back_flg;
    var $owner_admin_id;
    var $admin_unlock_token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Edit","Admin_Admin_Edit#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Edit::error", "管理者の編集機能は、現在、使用できません。");
            $this->removeSession();
            return "error";
        }
        if($this->edit_back_flg) {
            $this->DtoAdminUser->setAdmin_id($this->session->getParameter("edit_admin_id"));
            $where = array("0"=>"admin_id='".Db_Common::escape_string($this->DtoAdminUser->getAdmin_id())."'");
            if(!$this->MdbAdminUser->getExistCheck($where)) {
                $errorList->add("Admin_Admin_Edit::error", "管理者情報を取得できませんでした。");
                $this->removeSession();
                return "error";
            }
        } else {
            $this->removeSession();

            // 管理者情報取得
            $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
            if(!$this->DtoAdminUser->getAdmin_id()) {
                $errorList->add("Admin_Admin_Edit::error", "管理者情報を取得できませんでした。");
                $this->removeSession();
                return "error";
            }
            // スーパー管理者ならエラー(level=10)
            /*
            if($this->DtoAdminUser->getLevel() == 10) {
                $errorList->add("Admin_Admin_Edit::error", "選択された管理者は、スーパー管理者の為編集できません。");
                $this->removeSession();
                return "error";
            }
            */
            //自分自身は編集できないように
            if($this->DtoAdminUser->getAdmin_id() == $this->session->getParameter("admin_id")){
                $errorList->add("Admin_Admin_Edit::error", "選択された管理者は編集できません。");
                $this->removeSession();
                return "error";
            }

            $this->session->setParameter("edit_admin_id", $this->DtoAdminUser->getAdmin_id());
            $this->session->setParameter("account_org", $this->DtoAdminUser->getAccount());
            $this->session->setParameter("memo_org", $this->DtoAdminUser->getMemo());
            $this->session->setParameter("password_org", $this->DtoAdminUser->getPassword());
            $this->session->setParameter("account_lock_flg_org", $this->DtoAdminUser->getAccount_lock_flg());
        }
        if($this->DtoAdminUser->getLevel() == 10) {
            $this->admin_unlock_token = mt_rand();
            $this->session->setParameter("AdminUnlockToken", $this->admin_unlock_token);
            return "unlock";
        }

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("account");
        $this->session->removeParameter("password");
        $this->session->removeParameter("account_lock_flg");
        $this->session->removeParameter("account_org");
        $this->session->removeParameter("password_org");
        $this->session->removeParameter("account_lock_flg_org");
        $this->session->removeParameter("edit_admin_id");
        $this->session->removeParameter("owner_admin_id");
        $this->session->removeParameter("memo_org");
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
        $this->MdbConfigs = & $MdbConfigs;
    }
    function setEdit_back_flg($edit_back_flg) {
        $this->edit_back_flg = $edit_back_flg;
    }
    function setOwner_admin_id($owner_admin_id) {
        $this->owner_admin_id = $owner_admin_id;
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
    function getMemo() {
        return $this->DtoAdminUser->getMemo();
    }
    function getLevel() {
        $level = $this->DtoAdminUser->getLevel();
        if( $level == "" ) $level = "5";
        return $level;
    }
    function getAdmin_id() {
        $edit   = $this->session->getParameter("edit_admin_id");
        $owner1 = $this->session->getParameter("owner_admin_id");
        $owner2 = $this->owner_admin_id;
        if ($owner1 && $edit != $owner1) {
            return $owner1;
        } elseif ($owner2 && $edit != $owner2) {
            return $owner2;
        }
        return $edit;
    }
    function getAdmin_list() {
        $select_list = array();
        $order = array("account");
        $list = $this->MdbAdminUser->getAdminUserSelect();
        foreach ($list as $key => $val) {
            $select_list[$val["admin_id"]] = $val["account"];
        }
        return $select_list;
    }
    function getAdmin_unlock_token() {
        return $this->admin_unlock_token;
    }
    function getPass_min_length()
    {
        return $this->MdbConfigs->getDirectValue("pass_min_num");
    }
    function getPass_max_length()
    {
        return $this->MdbConfigs->getDirectValue("pass_max_num");
    }
}
?>
