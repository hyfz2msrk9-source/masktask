<?php
require_once( MODULE_DIR . "/common/common.php");

/**
 * Admin_Config_Continuouslogin module
 * セキュリティ設定 (連続ログインに関する設定)：入力
 * @updater     Hiroko Yanai（2018/01/29）メール本文
 *
 * @package     WebFile
 * @author      Hiroko Yanai（2017/12/04）
 * @copyright   KEYPOINT.INC
 */

class Admin_Config_Continuouslogin {
    var $session;
    var $MdbConfigs;
    var $TemplateLogic;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Continuousloginが実行されました", "Admin_Config_Continuouslogin#execute");

        $this->session->setParameter("continuous_login_notice_flg",  $this->getContinuous_login_notice_flg());
        $this->session->setParameter("continuous_login_span_minute", $this->getContinuous_login_span_minute());
        $this->session->setParameter("continuous_login_num", $this->getContinuous_login_num());
        $this->session->setParameter("continuous_login_notice_address", $this->getContinuous_login_notice_address());
        $this->session->setParameter("continuous_login_notice_each_time_flg", $this->getContinuous_login_notice_each_time_flg());
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'ContinuousloginEditToken',
            'token_value' => $this->token->getValue('ContinuousloginEditToken')
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
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setToken($token) {
        $this->token = $token;
    }

    // getter
    function getContinuous_login_notice_flg() {
        return $this->MdbConfigs->getDirectValue("Continuous_login_notice_flg");
    }
    function getContinuous_login_span_minute() {
        return $this->MdbConfigs->getDirectValue("continuous_login_span_minute");
    }
    function getContinuous_login_num() {
        return $this->MdbConfigs->getDirectValue("continuous_login_num");
    }
    function getContinuous_login_notice_address() {
        return $this->MdbConfigs->getDirectValue("continuous_login_notice_address");
    }
    function getContinuous_login_notice_each_time_flg() {
        return $this->MdbConfigs->getDirectValue("continuous_login_notice_each_time_flg");
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
