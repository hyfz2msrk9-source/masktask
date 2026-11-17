<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - 操作設定 - 配布に関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Sends {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Sendsが実行されました","Admin_Config_Sends#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("sends_flg",         $list['sends_flg']);
        $this->session->setParameter("mailer_flg",        $list['mailer_flg']);
        $this->session->setParameter("mailer_default_flg",$list['mailer_default_flg']);
        $this->session->setParameter("sendcollect_flg",   $list['sendcollect_flg']);
        $this->session->setParameter("sendcollect_mailer_flg",   $list['sendcollect_mailer_flg']);
        $this->session->setParameter("sample_temp_flg",   $list['sample_temp_flg']);
        $this->session->setParameter("default_pass_flg",   $list['default_pass_flg']);
        $this->session->setParameter("zip_pass_flg",   $list['zip_pass_flg']);
        $this->session->setParameter("send_mail_limit",   $list['send_mail_limit']);
        $this->session->setParameter("file_send_limit",   $list['file_send_limit']);
        $this->session->setParameter("limitdt_mark",      $list['limitdt_mark']);
        $this->session->setParameter("maxdltime_mark",    $list['maxdltime_mark']);
        $this->session->setParameter("collect_limitdt_mark",      $list['collect_limitdt_mark']);
        $this->session->setParameter("default_auto_del_flg",   $list['default_auto_del_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'SendsEditToken',
            'token_value' => $this->token->getValue('SendsEditToken')
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
    function setToken(&$token) {
        $this->token =& $token;
    }
}
?>
