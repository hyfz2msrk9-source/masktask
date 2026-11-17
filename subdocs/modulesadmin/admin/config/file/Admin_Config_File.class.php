<?php
/**
 * [機能説明] 各種設定 - 操作設定 - アップロードに関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_File {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbMchoice;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Fileが実行されました","Admin_Config_File#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("file_move_flg",     $list['file_move_flg']);
        $this->session->setParameter("del_span",          $list['del_span']);
        $this->session->setParameter("file_upload_limit", $list['file_upload_limit']);
        $this->session->setParameter("folder_dl_flg",      $list['folder_dl_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'FileEditToken',
            'token_value' => $this->token->getValue('FileEditToken')
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

    // getter
    function getUpload_Return_Flg_Options()
    {
        return GlobalArray::getUpload_Return_Flg_Options();
    }
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getFileUploadLimit()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"file_upload_limit");
        return $this->DtoConfigs->getValue();
    }
}
?>
