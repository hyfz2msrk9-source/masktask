<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 引取り(収集)に関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Collect {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Collectが実行されました","Admin_Config_Collect#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);
        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("sends_flg",         $list['sends_flg']);
        $this->session->setParameter("file_move_flg",     $list['file_move_flg']);
        $this->session->setParameter("mailer_flg",        $list['mailer_flg']);
        $this->session->setParameter("collect_folder_flg",   $list['collect_folder_flg']);
        $this->session->setParameter("default_collectpass_flg",   $list['default_collectpass_flg']);
        $this->session->setParameter("send_mail_limit",   $list['send_mail_limit']);
        $this->session->setParameter("file_collect_limit",$list['file_collect_limit']);
        $this->session->setParameter("limitdt_mark",      $list['limitdt_mark']);
        $this->session->setParameter("collect_limitdt_mark",  $list['collect_limitdt_mark']);
        $this->session->setParameter("collect_mailer_flg",  $list['collect_mailer_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'CollectEditToken',
            'token_value' => $this->token->getValue('CollectEditToken')
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
