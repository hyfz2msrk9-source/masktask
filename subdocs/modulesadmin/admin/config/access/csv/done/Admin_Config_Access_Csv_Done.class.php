<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Access_Csv_Done {
    var $session;
    var $DtoAccess;
    var $fileUpload;
    var $AccessLogic;
    var $MdbAccess;
    var $AccessDto;
    var $DtoLog;
    var $MdbLog;
    var $MdbConfigs;

    function execute() {
        // ログ
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Csv_Doneが実行されました", "Admin_Config_Access_Csv_Done#execute");

        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if($filesize <= 0) {
            $errorList->add("Admin_Config_Share_Export::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Config_Share_Export::error", "ファイルを確認してください。");
            return "upload";
        }
        if(!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Config_Share_Export::error", "インポートファイルは、CSV形式で作成してください。");
            return "upload";
        }

        $this->AccessLogic->addCsvAccess( $filename, $this->DtoAccess, $this->AccessDto, $this->MdbAccess);

        // Log
        $history_id = 148; // 148:各種設定＞セキュリティ設定＞アクセス制限に関する設定＞CSVファイルでの追加
        $memo = "";
        $memo.= "ファイル名：" . $this->fileUpload->getOriginalName() . "\n";
        $memo.= "設定：" . $this->AccessDto->getRow_count() . "件\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";

    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setDtoAccess($DtoAccess) {
        $this->DtoAccess = $DtoAccess;
    }
    function setAccessLogic($AccessLogic) {
        $this->AccessLogic = $AccessLogic;
    }
    function setMdbAccess($MdbAccess) {
    	$this->MdbAccess = $MdbAccess;
    }
    function setAccessDto($AccessDto) {
    	$this->AccessDto = $AccessDto;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    //************************************************************
    // getter
    //************************************************************
    // エラーメッセージ
    function getErr_msg(){
        return $this->AccessDto->getErr_msg();
    }
    // 何件追加したか
    function getIncount(){
        return $this->AccessDto->getRow_count();
    }

}
?>
