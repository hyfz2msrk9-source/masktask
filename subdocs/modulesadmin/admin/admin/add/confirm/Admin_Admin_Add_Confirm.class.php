<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の追加(確認)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Add_Confirm {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $AdminLogic;
    var $MdbConfigs;

    var $re_password;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Add_Confirm","Admin_Admin_Add_Confirm#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Add_Confirm::error", "管理者の登録機能は現在、使用できません。");
            return "error";
        }
        // アカウント文字種チェック(半角英数字記号)
        if(!preg_match("/^[!-~]+$/", $this->DtoAdminUser->getAccount())) {
            $errorList->add("Admin_Admin_Add_Confirm::error", "アカウントを英数字2-30文字で入力してください。");
            return "input";
        }
        // アカウント存在チェック
        $where = array("0" => "account = '". Db_Common::escape_string($this->DtoAdminUser->getAccount())."'","1"=>"del_flg='0'");
        if($this->MdbAdminUser->getExistCheck($where)) {
            $errorList->add("Admin_Admin_Add_Confirm::error", "ご希望のアカウントは既に使用されています。");
            return "input";
        }

        // パスワード文字数チェック
        if (!$this->AdminLogic->checkPasswordLength($this->MdbConfigs, $this->DtoAdminUser->getPassword(), $errorList)) {
            return "input";
        }

        // パスワード文字列チェック
        $err_msg = $this->AdminLogic->checkPassword($this->MdbConfigs,$this->DtoAdminUser->getPassword());
        if ($err_msg) {
            $errorList->add('Admin_Admin_Add_Confirm::error', $err_msg);
            return "input";
        }

        // アカウントと同じパスワードチェック
        if (!$this->AdminLogic->checkPasswordAccountNG($this->MdbConfigs,$this->DtoAdminUser,$this->DtoAdminUser->getPassword())) {
            $errorList->add('Admin_Admin_Add_Confirm::error', 'アカウントと同じパスワードは利用できません。');
            return "input";
        }

        $this->session->setParameter("account", $this->DtoAdminUser->getAccount());
        $this->session->setParameter("password", $this->DtoAdminUser->getPassword());
        $this->session->setParameter("memo", $this->DtoAdminUser->getMemo());
        $this->session->setParameter("level", $this->DtoAdminUser->getLevel());

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("account");
        $this->session->removeParameter("password");
        $this->session->removeParameter("memo");
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
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setRe_password($re_password) {
        $this->re_password = $re_password;
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
    function getMemo() {
        return $this->DtoAdminUser->getMemo();
    }
    function getLevel() {
        return $this->DtoAdminUser->getLevel();
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
