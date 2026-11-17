<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の追加
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Add {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $MdbConfigs;

    var $edit_back_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Add","Admin_Admin_Add#execute");


        if(!ADMIN_MANAGER_FLG) {
            //エラー初期化
            $container   = &DIContainerFactory::getContainer();
            $actionChain = &$container->getComponent("ActionChain");
            $errorList   = &$actionChain->getCurErrorList();
            $errorList->add("Admin_Admin_Add::error", "管理者の登録機能は現在、使用できません。");
            $this->removeSession();
            return "error";
        }

        if(!$this->edit_back_flg) {
            $this->DtoAdminUser->setAccount("");
            $this->DtoAdminUser->setPassword("");
            $this->DtoAdminUser->setMemo("");
            $this->DtoAdminUser->setLevel(null);
            $this->removeSession();
        }

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("account");
        $this->session->removeParameter("password");
        $this->session->removeParameter("memo");
        $this->session->removeParameter("level");
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
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setEdit_back_flg($edit_back_flg) {
        $this->edit_back_flg = $edit_back_flg;
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
    function getLevel() {
        $level = $this->DtoAdminUser->getLevel();
        if( $level == "" ) $level = "5";
        return $level;
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
