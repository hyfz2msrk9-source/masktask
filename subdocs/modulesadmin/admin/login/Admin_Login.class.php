<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Login {

    var $session;
    var $MdbUser;
    var $DtoUser;
    var $UserLogic;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Loginが実行されました", "Admin_Login#execute");
        $this->session->setParameter("admin_manual_flg", $this->MdbConfigs->getDirectValue("admin_manual_dl_flg"));
        //二要素認証で利用したsessionの削除
        $this->session->removeParameter("two_factor_authenticated");
        $this->session->removeParameter("admin_two_step_auth_login_flag");   
        return "success";
    }

    function setSession($session) {
        $this->session =& $session;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = &$MdbUser;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = &$DtoUser;
    }
    function setUserLogic(&$UserLogic) {
        $this->UserLogic = &$UserLogic;
    }
    function setMdbConfigs(&$MdbConfigs) {
        $this->MdbConfigs = &$MdbConfigs;
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
