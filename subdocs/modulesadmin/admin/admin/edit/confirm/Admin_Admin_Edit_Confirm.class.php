<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の編集(確認)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Edit_Confirm {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $AdminLogic;
    var $MdbConfigs;
    var $AdminuserDto;
    var $re_password;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Edit_Confirm","Admin_Admin_Edit_Confirm#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Edit_Confirm::error", "管理者の編集機能は、現在、使用できません。");
            $this->removeSession();
            return "error";
        }
        //URL直接入力された場合の対処
        if(!$this->session->getParameter("edit_admin_id")){
            $errorList->add("Admin_Admin_Edit_Confirm::error", "管理者情報を取得できませんでした。");
            $this->removeSession();
            return "error";
        }
        //自分自身は編集できないように
        if($this->DtoAdminUser->getAdmin_id() == $this->session->getParameter("admin_id")){
            $errorList->add("Admin_Admin_Edit_Confirm::error", "選択された管理者は編集できません。");
            $this->removeSession();
            return "error";
        }

        // アカウント文字種チェック(半角英数字記号)
        if(!preg_match("/^[!-~]+$/", $this->DtoAdminUser->getAccount())) {
            $errorList->add("Admin_Admin_Edit_Confirm::error", "アカウントを英数字2-30文字で入力してください。");
            return "input";
        }

        // アカウント存在チェック
        $where = array("0" => "account = '".Db_Common::escape_string($this->DtoAdminUser->getAccount())."'", "1"=>"del_flg='0'", "2" => "admin_id !='".Db_Common::escape_string($this->session->getParameter('edit_admin_id'))."'");

        if($this->MdbAdminUser->getExistCheck($where)) {
            $errorList->add("Admin_Admin_Edit_Confirm::error", "ご希望のアカウントは既に使用されています。");
            return "input";
        }
        // パスワードチェック(入力がある場合のみ)
        if($this->DtoAdminUser->getPassword()) {
            if($this->DtoAdminUser->getPassword() != $this->re_password) {
                $errorList->add("Admin_Admin_Edit_Confirm::error", "パスワードと確認用パスワードが同じではありません。");
                return "input";
            }

            // パスワード文字数チェック
            if (!$this->AdminLogic->checkPasswordLength($this->MdbConfigs, $this->DtoAdminUser->getPassword(), $errorList)) {
                return "input";
            }

            // パスワード文字列チェック
            $err_msg = $this->AdminLogic->checkPassword($this->MdbConfigs,$this->DtoAdminUser->getPassword());
            if ($err_msg) {
                $errorList->add('Admin_Admin_Edit_Confirm::error', $err_msg);
                return "input";
            }

            // アカウントと同じパスワードチェック
            if (!$this->AdminLogic->checkPasswordAccountNG($this->MdbConfigs,$this->DtoAdminUser,$this->DtoAdminUser->getPassword())) {
                $errorList->add('Admin_Admin_Edit_Confirm::error', 'アカウントと同じパスワードは利用できません。');
                return "input";
            }

        }
        // アカウントロックをセット
        if($this->DtoAdminUser->getAccount_lock_flg() === NULL) {
            $this->DtoAdminUser->setAccount_lock_flg('1');
        }
        // 権限移行チェック
        if ($this->session->getParameter('edit_admin_id') != $this->AdminuserDto->getOwner_admin_id()) {
            $owner_info = $this->MdbAdminUser->getAdminuserInfo2($this->AdminuserDto->getOwner_admin_id());
            if (!$owner_info || $owner_info["del_flg"]==1) {
                $errorList->add("Admin_Admin_Edit_Confirm::error", "権限を移行する管理者は既に削除されています。");
                return "input";
            }
        }

        $this->session->setParameter("account", $this->DtoAdminUser->getAccount());
        $this->session->setParameter("password", $this->DtoAdminUser->getPassword());
        $this->session->setParameter("account_lock_flg", $this->DtoAdminUser->getAccount_lock_flg());
        $this->session->setParameter("memo", $this->DtoAdminUser->getMemo());
        $this->session->setParameter("level", $this->DtoAdminUser->getLevel());
        $this->session->setParameter("owner_admin_id", $this->AdminuserDto->getOwner_admin_id());

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("account");
        $this->session->removeParameter("password");
        $this->session->removeParameter("memo");
        $this->session->removeParameter("account_lock_flg");
        $this->session->removeParameter("account_org");
        $this->session->removeParameter("password_org");
        $this->session->removeParameter("memo_org");
        $this->session->removeParameter("account_lock_flg_org");
        $this->session->removeParameter("edit_admin_id");
        $this->session->removeParameter("owner_admin_id");
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
    function setAdminLogic($AdminLogic)
    {
        $this->AdminLogic = $AdminLogic;
    }
    function setRe_password($re_password) {
        $this->re_password = $re_password;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
    }
    function setAdminuserDto($AdminuserDto) {
        $this->AdminuserDto = $AdminuserDto;
    }

    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoAdminUser->getAccount();
    }
    function getPasswd_cont() {
        $cont = "";
        for($i=0;$i < strlen($this->DtoAdminUser->getPassword()); $i++) {
            $cont .= "*";
        }
        return $cont;
    }
    function getAccount_lock_flg() {
        return $this->DtoAdminUser->getAccount_lock_flg();
    }
    function getPassword() {
        return $this->DtoAdminUser->getPassword();
    }
    function getAccount_lock_flg_org() {
        return $this->session->getParameter('account_lock_flg_org');
    }
    function getAdmin_account_lock_cnt(){
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
    }
    function getMemo()
    {
        return $this->DtoAdminUser->getMemo();
    }
    function getLevel() {
        return $this->DtoAdminUser->getLevel();
    }
    function getOwner_admin() {
        if ($this->session->getParameter('edit_admin_id') != $this->AdminuserDto->getOwner_admin_id()) {
            $owner_info = $this->MdbAdminUser->getAdminuserInfo2($this->AdminuserDto->getOwner_admin_id());
            if ($owner_info && $owner_info["del_flg"]==0) {
                return $owner_info["account"];
            }
        }
        return false;
    }
    function getAdmin_id() {
        $edit = $this->session->getParameter("edit_admin_id");
        $owner = $this->session->getParameter("owner_admin_id");
        if ($owner && $edit != $owner) {
            return $owner;
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
