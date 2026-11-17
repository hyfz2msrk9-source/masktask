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

class Admin_Password_First_Done {

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
        $log->debug("Admin_Password_First_Doneが実行されました","Admin_Password_First_Done#execute");

        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // トークンがあっているかの確認
        if ($this->AdminPassEntry->getToken() != $this->session->getParameter("FirstPasswordToken")) {
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "invalidToken";
        }

         // アカウント文字種チェック(半角英数字記号)
        if(!preg_match("/^[!-~]+$/", $this->AdminPassEntry->getAccount())) {
            $errorList->add("'Admin_Password_First_Done::error", "アカウントは半角英数字(記号を含む)2-30文字以内で入力してください。");
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }
        // アカウント存在チェック
        $where = array("0" => "account = '". Db_Common::escape_string($this->AdminPassEntry->getAccount())."'","1"=>"del_flg='0'");
        if($this->MdbAdminuser->getExistCheck($where)) {
            $errorList->add("'Admin_Password_First_Done::error", "ご希望のアカウントは既に使用されています。");
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // 旧パスワードが正しいかどうか
        if (! Password::verify($this->AdminPassEntry->getOld(), $this->DtoAdminuser->getPassword())) {
            $errorList->add('Admin_Password_First_Done::error', '現在のパスワードが違います。もう一度ご確認ください。');
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // パスワード文字数チェック
        if (!$this->AdminLogic->checkPasswordLength($this->MdbConfigs, $this->AdminPassEntry->getNew1())) {
            $pass_min = $this->MdbConfigs->getDirectValue("pass_min_num");
            $pass_max = $this->MdbConfigs->getDirectValue("pass_max_num");
            $errorList->add('Admin_Password_First_Done::error', 'パスワードは'.$pass_min.'〜'.$pass_max.'文字で入力してください。');
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // パスワード文字列チェック
        $err_msg = $this->AdminLogic->checkPassword($this->MdbConfigs, $this->AdminPassEntry->getNew1());
        if ($err_msg) {
            $errorList->add('Admin_Password_First_Done::error', $err_msg);
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // 前回と同じパスワードチェック
        if (!$this->AdminLogic->checkPasswordBoth($this->MdbConfigs, $this->DtoAdminuser,$this->AdminPassEntry->getNew1())) {
            $errorList->add('Admin_Password_First_Done::error', '現在のパスワードと同じパスワードは利用できません。');
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // アカウントと同じパスワードチェック
        if ( $this->AdminPassEntry->getAccount() == $this->AdminPassEntry->getNew1() ) {
            $errorList->add('Admin_Password_First_Done::error', 'アカウントとパスワードに同じ文字列は利用できません。');
            // トークンは作り直す
            $this->session->setParameter("FirstPasswordToken", md5(uniqid(rand(),1)));
            return "input";
        }

        // パスワードの変更
        $this->DtoAdminuser->setPassword(Password::hash($this->AdminPassEntry->getNew1()));
        $this->DtoAdminuser->setAccount($this->AdminPassEntry->getAccount());
        $this->MdbAdminuser->updateSuperAdmin($this->DtoAdminuser);

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
        $this->session->removeParameter("FirstPasswordToken");

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

    function setMdbUser($MdbUser){
        $this->MdbUser = $MdbUser;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setUserLogic($UserLogic){
        $this->UserLogic = $UserLogic;
    }
    // 入力したアカウントを保持する
    function getInputAccount() {
        return $this->AdminPassEntry->getAccount();
    }
    function getOver_max_user() {
        if (MAX_USER != 0) {
            $cnt = $this->UserLogic->getUserActiveCont($this->DtoUser,$this->MdbUser);
            if (MAX_USER <= $cnt) {
                return 1;
            }
        } else {
            return 0;
        }
    }
}
?>
