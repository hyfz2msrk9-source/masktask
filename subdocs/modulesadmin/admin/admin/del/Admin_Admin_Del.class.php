<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の削除(確認)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Del {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;

    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Del","Admin_Admin_Del#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Del::error", "管理者の削除機能は現在、使用できません。");
            $this->removeSession();
            return "error";
        }
        // 管理者情報取得
        $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
        if(!$this->DtoAdminUser->getAdmin_id()) {
            $errorList->add("Admin_Admin_Del::error", "管理者情報を取得できませんでした。");
            $this->removeSession();
            return "error";
        }
        // 管理者が残り１つならエラー (自分は削除できないけど一応)
        $this->MdbAdminUser->getAdminuserList($this->DtoAdminUser);
        if(count($this->DtoAdminUser->getAdminuserList()) < 1) {
            $errorList->add("Admin_Admin_Del::error", "登録されている管理者が1人の為、削除できません。");
            $this->removeSession();
        }
        // スーパー管理者ならエラー(level=10)
        if($this->DtoAdminUser->getLevel() == 10) {
            $errorList->add("Admin_Admin_Del::error", "選択された管理者は、スーパー管理者の為削除できません。");
            $this->removeSession();
            return "error";
        }
        //自分自身は削除できないように
        if($this->DtoAdminUser->getAdmin_id() == $this->session->getParameter("admin_id")){
            $errorList->add("Admin_Admin_Del::error", "選択された管理者は削除できません。");
            $this->removeSession();
            return "error";
        }
        $this->session->setParameter("del_admin_id", $this->DtoAdminUser->getAdmin_id());
        $this->session->setParameter("account", $this->DtoAdminUser->getAccount());

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("del_admin_id");
        $this->session->removeParameter("account");
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
    function setRe_password($re_password) {
        $this->re_password = $re_password;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
    }

    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoAdminUser->getAccount();
    }
    function getMemo() {
        return $this->DtoAdminUser->getMemo();
    }
    function getAccount_lock_flg() {
        return $this->DtoAdminUser->getAccount_lock_flg();
    }
    function getAdmin_account_lock_cnt(){
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
    }
    function getLevel() {
        $level = $this->DtoAdminUser->getLevel();
        if( $level == "" ) $level = "5";
        return $level;
    }

}
?>
