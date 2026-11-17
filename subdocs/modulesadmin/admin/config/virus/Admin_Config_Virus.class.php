<?php
/**
 * [機能説明] 各種設定 - セキュリティ設定 - ウイルスチェックに関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Virus {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug(__CLASS__. "が実行されました", __CLASS__. "#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("virus_pass_protect_flg", $list['virus_pass_protect_flg']);
        $this->session->setParameter("virus_timeout_flg", $list['virus_timeout_flg']);
        $this->session->setParameter("virus_riskware_flg", $list['virus_riskware_flg']);
        $this->session->setParameter("virus_malice_flg", $list['virus_malice_flg']);
        $this->session->setParameter("virus_cant_upload_when_scanner_error_flg", $list['virus_cant_upload_when_scanner_error_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'VirusEditToken',
            'token_value' => $this->token->getValue('VirusEditToken')
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

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
}
?>
