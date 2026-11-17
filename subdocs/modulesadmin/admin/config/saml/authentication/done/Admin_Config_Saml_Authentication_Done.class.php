<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - その他設定 - SAML認証に関する設定(完了)
 * @updater     Hiroko Yanai（2019/01/07）strictモードに対応
 *
 * @package     WebFile
 * @version     3.16.2
 * @author      Hiroko Yanai（2018/04/16）
 * @copyright   KEYPOINT.INC
 * @access      public
 */

class Admin_Config_Saml_Authentication_Done {
    var $session;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $AdminUserLogic;
    var $CommonList;

    // request
    var $saml_authentication_issuer;
    var $saml_authentication_saml_endpoint;
    var $saml_authentication_cert;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Saml_Authentication_Doneが実行されました","Admin_Config_Saml_Authentication_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (!isset($usage_auth_types[2])) {
            $errorList->add("Admin_Config_Saml_Authentication", "この機能は、現在ご利用頂けません。");
            return "error";
        }

        // 入力チェック
        if (!$this->AdminUserLogic->is_url($this->saml_authentication_issuer)) {
            $errorList->add("Admin_Config_Saml_Authentication_Done::error", "Issuer URLを正しく入力してください。");
            return "input";
        }
        if (!$this->AdminUserLogic->is_url($this->saml_authentication_saml_endpoint)) {
            $errorList->add("Admin_Config_Saml_Authentication_Done::error", "SAML 2.0 Endpoint (HTTP)を正しく入力してください。");
            return "input";
        }

        // 更新とLog
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if ($this->saml_authentication_issuer != $this->session->getParameter("saml_authentication_issuer")) {
            $this->MdbConfigs->setValue('saml_authentication_issuer', $this->saml_authentication_issuer);

            $memo .= "Issuer URL："
                .(mb_strlen($this->session->getParameter("saml_authentication_issuer")) ? $this->session->getParameter("saml_authentication_issuer") : "(未設定)")
                ."→".$this->saml_authentication_issuer."\n";

            $this->session->setParameter("saml_authentication_issuer", $this->getSaml_authentication_issuer());     // X.509 Certificateのエラー時も、他の項目は保存する
        }
        if ($this->saml_authentication_saml_endpoint != $this->session->getParameter("saml_authentication_saml_endpoint")) {
            $this->MdbConfigs->setValue('saml_authentication_saml_endpoint', $this->saml_authentication_saml_endpoint);

            $memo .= "SAML 2.0 Endpoint (HTTP)："
                .(mb_strlen($this->session->getParameter("saml_authentication_saml_endpoint")) ? $this->session->getParameter("saml_authentication_saml_endpoint") : "(未設定)")
                ."→".$this->saml_authentication_saml_endpoint."\n";

            $this->session->setParameter("saml_authentication_saml_endpoint", $this->getSaml_authentication_saml_endpoint());   // X.509 Certificateのエラー時も、他の項目は保存する
        }
        if ($this->saml_authentication_cert != $this->session->getParameter("saml_authentication_cert")) {
            // ファイルが存在しなければ、作成する。存在すれば、上書き
            if (file_put_contents(SAML_IDP_X509CERTIFICATE_FILE, $this->saml_authentication_cert) === false) {
                $errorList->add("Admin_Config_Saml_Authentication_Done::error", "X.509 Certificateを保存できませんでした。他の項目の変更は保存済みです");
                $x509_cert_file_error = true;
            } else {
                // 変更を保存完了
                $memo .= "X.509 Certificate：変更あり\n";
            }
        }

        if ($memo) {
            $memo = "[その他＞SAML認証に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        if (isset($x509_cert_file_error)) {
            return "input";
        }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setSaml_authentication_issuer($saml_authentication_issuer) {
        $this->saml_authentication_issuer = $saml_authentication_issuer;
    }
    function setSaml_authentication_saml_endpoint($saml_authentication_saml_endpoint) {
        $this->saml_authentication_saml_endpoint = $saml_authentication_saml_endpoint;
    }
    function setSaml_authentication_cert($saml_authentication_cert) {
        $this->saml_authentication_cert = $saml_authentication_cert;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getSaml_authentication_issuer() {
        return $this->saml_authentication_issuer;
    }
    function getSaml_authentication_saml_endpoint() {
        return $this->saml_authentication_saml_endpoint;
    }
    function getSaml_authentication_cert() {
        return $this->saml_authentication_cert;
    }
    function getSP_URLs() {
        // WebFileの各種URL
        $sp_urls = array();
        $sp_urls['audience']    = APP_URL . APP_PATH . '/index.php';
        $sp_urls['acs_url']     = APP_URL . APP_PATH . '/index.php?action=saml&acs';
        $sp_urls['api_audience']    = API_BASEURL;
        $sp_urls['api_acs_url']     = API_BASEURL . '/saml/login';

        // WebFileからログアウトする時にSAML(OneLogin)からはログアウトしないので、使わない
        //$sp_urls['logout_url'] = $base_url . '/index.php?action=saml&sls';
        return $sp_urls;
    }

}
?>
