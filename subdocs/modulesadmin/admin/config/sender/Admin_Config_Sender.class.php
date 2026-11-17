<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 送信者(差出人)に関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Sender {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Senderが実行されました","Admin_Config_Sender#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);
        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("sender_setting_flg",$list['sender_setting_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'SenderEditToken',
            'token_value' => $this->token->getValue('SenderEditToken')
        ]);
        exit;
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
    function setToken($token) {
        $this->token = $token;
    }
}
?>
