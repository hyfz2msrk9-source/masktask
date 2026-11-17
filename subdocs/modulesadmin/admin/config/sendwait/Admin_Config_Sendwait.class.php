<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Sendwait {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Sendwaitが実行されました","Admin_Config_Sendwait#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入

        if (SEND_WAIT_FLG) {
            $this->session->setParameter("send_wait_flg",    $list['send_wait_flg']);
            $this->session->setParameter("dl_start_time_flg", $list['dl_start_time_flg']);
            $this->session->setParameter("dl_start_time_value", $list['dl_start_time_value']);
            $this->session->setParameter("send_collect_start_time_flg", $list['send_collect_start_time_flg']);
            $this->session->setParameter("send_collect_start_time_value", $list['send_collect_start_time_value']);
            $this->session->setParameter("up_start_time_flg", $list['up_start_time_flg']);
            $this->session->setParameter("up_start_time_value", $list['up_start_time_value']);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'SendwaitEditToken',
            'token_value' => $this->token->getValue('SendwaitEditToken')
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
