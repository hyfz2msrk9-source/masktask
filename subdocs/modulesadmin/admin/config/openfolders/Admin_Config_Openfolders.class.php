<?php
/**
 * [機能説明] 各種設定 - 操作設定 - フォルダ公開に関する設定
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Openfolders {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Openfoldersが実行されました","Admin_Config_Openfolders#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("openfolders_mailer_flg",        $list['openfolders_mailer_flg']);
        $this->session->setParameter("openfolders_mailer_default_flg",        $list['openfolders_mailer_default_flg']);
        $this->session->setParameter("openfolders_flg",   $list['openfolders_flg']);
        $this->session->setParameter("open_subfolder_flg",$list['open_subfolder_flg']);
        $this->session->setParameter("thumbnail_flg",     $list['thumbnail_flg']);
        $this->session->setParameter("default_openpass_flg",   $list['default_openpass_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'OpenfolderEditToken',
            'token_value' => $this->token->getValue('OpenfolderEditToken')
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
