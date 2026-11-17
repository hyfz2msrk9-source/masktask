<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Clientauth {
    var $session;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_ClientAuthが実行されました","Admin_Config_ClientAuth#execute");

        if(!CLIENT_AUTH_FLG) {
            echo json_encode([
                'status' => 'error',
                'message' => 'この機能をご利用頂くためには、クライアント認証オプションが必要です。'
            ]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'ClientEditToken',
            'token_value' => $this->token->getValue('ClientEditToken')
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