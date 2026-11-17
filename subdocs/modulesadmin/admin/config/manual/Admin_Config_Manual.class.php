<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Manual {
    var $session;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Manualが実行されました","Admin_Config_Manual#execute");

        
        $this->session->setParameter("user_manual_dl_flg", $this->MdbConfigs->getDirectValue("user_manual_dl_flg"));
        $this->session->setParameter("admin_manual_dl_flg", $this->MdbConfigs->getDirectValue("admin_manual_dl_flg"));

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'ManualEditToken',
            'token_value' => $this->token->getValue('ManualEditToken')
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
    function setToken($token) {
        $this->token = $token;
    }
}
?>
