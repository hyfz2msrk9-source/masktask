<?php
/**
 * パスワード強制変更(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.16.0
 * @author      Hiroko Yanai（2018/10/01）
 * @copyright   KEYPOINT.INC
 * @access      public
 **/
require_once( MODULE_DIR . '/common/common.php');

class Admin_Password_Must_Done {

    var $session;
    var $DtoAdminuser;
    var $MdbAdminuser;
    var $AdminPassEntry;
    var $MdbConfigs;
    var $AdminLogic;
    var $MdbLog;
    var $DtoLog;
    var $MdbUser;
    var $DtoUser;
    var $UserLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Password_Must_Doneが実行されました","Admin_Password_Must_Done#execute");

        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 旧パスワードが正しいかどうか
        if (! Password::verify($this->AdminPassEntry->getOld(), $this->DtoAdminuser->getPassword())) {
            $errorList->add('Admin_Password_Must_Done::error', '現在のパスワードが違います。もう一度ご確認ください。');
            return "input";
        }

        // パスワード文字数チェック
        if (!$this->AdminLogic->checkPasswordLength($this->MdbConfigs, $this->AdminPassEntry->getNew1())) {
            $pass_min = $this->MdbConfigs->getDirectValue("pass_min_num");
            $pass_max = $this->MdbConfigs->getDirectValue("pass_max_num");
            $errorList->add('Admin_Password_Must_Done::error', 'パスワードは'.$pass_min.'〜'.$pass_max.'文字で入力してください。');
            return "input";
        }

        // パスワード文字列チェック
        $err_msg = $this->AdminLogic->checkPassword($this->MdbConfigs, $this->AdminPassEntry->getNew1());
        if ($err_msg) {
            $errorList->add('Admin_Password_Must_Done::error', $err_msg);
            return "input";
        }

        // 前回と同じパスワードチェック
        if (!$this->AdminLogic->checkPasswordBoth($this->MdbConfigs, $this->DtoAdminuser,$this->AdminPassEntry->getNew1())) {
            $errorList->add('Admin_Password_Must_Done::error', '現在のパスワードと同じパスワードは利用できません。');
            return "input";
        }

        // アカウントと同じパスワードチェック
        if (!$this->AdminLogic->checkPasswordAccountNG($this->MdbConfigs, $this->DtoAdminuser, $this->AdminPassEntry->getNew1())) {
            $errorList->add('Admin_Password_Done::error', 'アカウントと同じパスワードは利用できません。');
            return "input";
        }

        // パスワードの変更
        $this->DtoAdminuser->setPassword(Password::hash($this->AdminPassEntry->getNew1()));
        $this->MdbAdminuser->updatePassword($this->DtoAdminuser);

        // Log
        $history_id = 23; // 23:パスワード変更
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo("");
        $this->MdbLog->Logging($this->DtoLog);

        // ログインクッキーの削除
        $this->session->removeParameter("admin_id");

        return "success";
    }

    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoAdminuser (&$DtoAdminuser) {
        $this->DtoAdminuser =& $DtoAdminuser;
    }
    function setMdbAdminuser (&$MdbAdminuser) {
        $this->MdbAdminuser =& $MdbAdminuser;
    }
    function setAdminPassEntry (&$AdminPassEntry) {
        $this->AdminPassEntry =& $AdminPassEntry;
    }
    function setDtoLog (&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog (&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbConfigs(&$MdbConfigs) {
        $this->MdbConfigs =& $MdbConfigs;
    }
    function setAdminLogic(&$AdminLogic) {
        $this->AdminLogic =& $AdminLogic;
    }

    function getPassmust_span(){
        return $this->MdbConfigs->getValue("admin_passmust_span");
    }
    function getPass_min_num(){
        return $this->MdbConfigs->getValue("pass_min_num");
    }
    function getPass_max_num(){
        return $this->MdbConfigs->getValue("pass_max_num");
    }
    function getOver_max_user()
    {
        if (MAX_USER != 0) {
            $cnt = $this->UserLogic->getUserActiveCont($this->DtoUser, $this->MdbUser);
            if (MAX_USER <= $cnt) {
                return 1;
            }
        } else {
            return 0;
        }
    }
}
?>
