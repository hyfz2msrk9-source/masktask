<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - セキュリティ設定 - アカウントロック通知に関する設定
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author      Hiroko Yanai（2017/12/??）
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Accountlock {
    var $session;
    var $MdbConfigs;
    var $CommonList;
    var $TemplateLogic;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Accountlockが実行されました","Admin_Config_Accountlock#execute");

        $this->session->setParameter("accountlock_mail_flg",  $this->getAccountlock_mail_flg());
        $this->session->setParameter("accountlock_mail_type", $this->getAccountlock_mail_type());
        $this->session->setParameter("accountlock_mail_addr", $this->getAccountlock_mail_addr());
        if($this->getLock_edit_flg()){
            $this->session->setParameter("user_account_lock_cnt",  $this->getUser_account_lock_cnt());
            $this->session->setParameter("user_account_lock_time", $this->getUser_account_lock_time());
            $this->session->setParameter("admin_account_lock_cnt", $this->getAdmin_account_lock_cnt());
            $this->session->setParameter("admin_account_lock_time", $this->getAdmin_account_lock_time());
        }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'AccountlockEditToken',
            'token_value' => $this->token->getValue('AccountlockEditToken')
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
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setToken($token) {
        $this->token = $token;
    }

    // getter
    function getAccountlock_mail_flg() {
        return $this->MdbConfigs->getDirectValue("accountlock_mail_flg");
    }
    function getAccountlock_mail_type() {
        return explode(",", $this->MdbConfigs->getDirectValue("accountlock_mail_type"));
    }
    function getAccountlock_mail_addr() {
        return $this->MdbConfigs->getDirectValue("accountlock_mail_addr");
    }
    function getAccountlock_mail_body() {
        return $this->TemplateLogic->get_template_file_contents("", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_en() {
        return $this->TemplateLogic->get_template_file_contents("en", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_cn() {
        return $this->TemplateLogic->get_template_file_contents("cn", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_tcn() {
        return $this->TemplateLogic->get_template_file_contents("tcn", "accountlock_mail_body.txt");
    }
    function getLock_edit_flg(){
        if($this->session->getParameter("login_admin_level") == 10){
            return true;
        }else{
            return false;
        }
    }
    function getUser_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_cnt");
    }
    function getUser_account_lock_time() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_time");
    }
    function getAdmin_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
    }
    function getAdmin_account_lock_time() {
        return $this->MdbConfigs->getDirectValue("admin_account_lock_time");
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
