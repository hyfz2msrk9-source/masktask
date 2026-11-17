<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - その他設定 - SAML認証に関する設定
 * @updater     Hiroko Yanai（2019/01/07）strictモードに対応
 *
 * @package     WebFile
 * @version     3.16.2
 * @author      Hiroko Yanai（2018/04/16）
 * @copyright   KEYPOINT.INC
 * @access      public
 */

class Admin_Config_Saml_Authentication {
    var $session;
    var $MdbConfigs;
    var $CommonList;
    var $x509_cert;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Saml_Authenticationが実行されました", "Admin_Config_Saml_Authentication#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (!isset($usage_auth_types[2])) {
            $errorList->add("Admin_Config_Saml_Authentication", "この機能は、現在ご利用頂けません。");
            return "error";
        }

        if (!file_exists(SAML_IDP_X509CERTIFICATE_FILE)) {
            $this->x509_cert = '';
        } else {
            $this->x509_cert = file_get_contents(SAML_IDP_X509CERTIFICATE_FILE);
        }

        $this->session->setParameter("saml_authentication_issuer", $this->getSaml_authentication_issuer());
        $this->session->setParameter("saml_authentication_saml_endpoint", $this->getSaml_authentication_saml_endpoint());
        $this->session->setParameter("saml_authentication_cert", $this->getSaml_authentication_cert());
        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getSaml_authentication_issuer() {
        return $this->MdbConfigs->getDirectValue("saml_authentication_issuer");
    }
    function getSaml_authentication_saml_endpoint() {
        return $this->MdbConfigs->getDirectValue("saml_authentication_saml_endpoint");
    }
    /*
    function getSaml_authentication_slo_endpoint() {
        // WebFileからログアウトする時にSAML(OneLogin)からはログアウトしないので、使わない
        return $this->MdbConfigs->getDirectValue("saml_authentication_slo_endpoint");
    }
    */
    function getSaml_authentication_cert() {
        return $this->x509_cert;
    }
    function getSP_URLs() {
        // WebFileの各種URL
        $sp_urls = array();
        $base_url = APP_URL . APP_PATH;

        $sp_urls['audience']    = $base_url . '/index.php';
        $sp_urls['acs_url']     = $base_url . '/index.php?action=saml&acs';
        $sp_urls['api_audience']    = $base_url . API_PATH;
        $sp_urls['api_acs_url']     = $base_url . API_PATH . '/saml/login';
        // WebFileからログアウトする時にSAML(OneLogin)からはログアウトしないので、使わない
        //$sp_urls['logout_url'] = $base_url . '/index.php?action=saml&sls';
        return $sp_urls;
    }

}
/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>
