<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_System {

    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $MdbMchoice;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Systemが実行されました","Admin_Config_System#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);
        if($this->session->getParameter("login_admin_level") != 10){
            return "detail";
        }

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }

    // getter
    function getManual_Dl_Options() {
        return GlobalArray::getManual_Dl_Options();
    }
    function getUser_manual_dl_flg() {
        return $this->MdbConfigs->getDirectValue("user_manual_dl_flg");
    }
    function getMax_file_size() {
        return $this->MdbConfigs->getDirectValue("max_file_size");
    }
    function getUser_delete_limit_dt() {
        return $this->MdbConfigs->getDirectValue("user_delete_limit_dt");
	}
    function getUser_file_delete_limit_dt() {
        return $this->MdbConfigs->getDirectValue("user_file_delete_limit_dt");
    }

    function getSend_Wait_Options() {
        return GlobalArray::getSend_Wait_Options();
    }
    function getAdmin_Manual_dl_flg() {
        return $this->MdbConfigs->getDirectValue("admin_manual_dl_flg");
    }
    function getSend_wait_flg() {
        return $this->MdbConfigs->getDirectValue("send_wait_flg");
    }
    function getCn_images() {
        return file_exists(WF_PATH."/htdocs/images-cn");
	}
    function getApi_Flg_Options()
    {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg()
    {
        return $this->MdbConfigs->getDirectValue("api_flg");
    }

    function getOutlookImgPath() {
        if (file_exists(WF_PATH."/htdocs/images/outlook_addin/icon-80.png")) {
            return APP_PATH . "/images/outlook_addin/icon-80.png";
        } else {
            return APP_PATH . "/images/outlook_addin/icon-128-org.gif";
        }
    }
    //ロゴの取得
    function getLogoByLanguage($lang)
    {
        $postfix = "";
        if (in_array($lang, ['en', 'cn', 'tcn'])) { //他言語
            $postfix  = "-" . $lang;
        }
        if (file_exists(WF_PATH . "/htdocs/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif")) {
            return APP_PATH . "/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif";
        } else {
            return APP_PATH . "/images" . $postfix . "/default_logo.gif";
        }
    }
    function getLogo()
    {
        return $this->getLogoByLanguage("");
    }
    function getLogoEn()
    {
        return $this->getLogoByLanguage("en");
    }
    function getLogoCn()
    {
        return $this->getLogoByLanguage("cn");
    }
    function getLogoTcn()
    {
        return $this->getLogoByLanguage("tcn");
    }
}
?>
