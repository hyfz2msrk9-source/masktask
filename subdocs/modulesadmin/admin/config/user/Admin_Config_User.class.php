<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_User {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbMchoice;
    var $use_capacity;
    var $group_count;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Userが実行されました","Admin_Config_User#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        if($this->session->getParameter("login_admin_level") != 10){
            return "error";
        }

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("max_file_size",             $list['max_file_size']);
        $this->session->setParameter("user_delete_limit_dt",      $list['user_delete_limit_dt']);
        $this->session->setParameter("user_file_delete_limit_dt", $list['user_file_delete_limit_dt']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'UserEditToken',
            'token_value' => $this->token->getValue('UserEditToken')
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
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>
