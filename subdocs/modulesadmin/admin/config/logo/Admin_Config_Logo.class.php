<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Logo {
    var $session;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Logoが実行されました","Admin_Config_Logo#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if( !CHANGE_LOGO_FLG ){
            $errorList->add("archive::error", "この機能は、現在ご利用頂けません。","Sorry, this function cannot be used now. ");
            return "error2";
        }

        if($this->session->getParameter("login_admin_level") != 10){
            return "error";
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'LogoEditToken',
            'token_value' => $this->token->getValue('LogoEditToken')
        ]);
        exit;
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setToken($token) {
        $this->token = $token;
    }

    // getter
    function getCn_images() {
        return file_exists(WF_PATH."/htdocs/images-cn") && CHINESE_FLG;
    }
    function getTcn_images() {
        return file_exists(WF_PATH."/htdocs/images-tcn") && CHINESE_FLG;
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
