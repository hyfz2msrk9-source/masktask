<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Superior {

    var $session;
    var $MdbConfigs;
    var $token;

    function execute(){
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Superiorが実行されました", "Admin_Config_Superior#execute");

        // sessionにセット
        $this->session->setParameter("superior_file_check_type", $this->MdbConfigs->getDirectValue("superior_file_check_type"));

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'SuperiorEditToken',
            'token_value' => $this->token->getValue('SuperiorEditToken')
        ]);
        exit;
    }

    /************************************
    * setter
    *************************************/

    function setSession($session){
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs){
        $this->MdbConfigs = $MdbConfigs;
    }
    function setToken($token) {
        $this->token = $token;
    }
}

?>
