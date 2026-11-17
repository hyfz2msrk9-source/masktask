<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Access_Export {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $DtoAccess;
    var $AccessLogic;
    var $MdbAccess;
    var $AccessDto;


    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Exportが実行されました", "Admin_Config_Access_Export#execute");

        $this->AccessLogic->access_export($this->DtoAccess,$this->MdbAccess);


        // Log
        $history_id = 147; // 147:各種設定＞セキュリティ設定＞アクセス制限に関する設定＞エクスポート
        $memo = "";
        $this->MdbAccess->getAccessCont($this->AccessDto);
        $memo.= "件数：" . $this->AccessDto->getRow_count() . "件";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);


        exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
    function setDtoAccess($DtoAccess){
    	$this->DtoAccess = $DtoAccess;
    }
    function setAccessLogic($AccessLogic){
    	$this->AccessLogic = $AccessLogic;
    }
    function setMdbAccess($MdbAccess){
    	$this->MdbAccess = $MdbAccess;
    }
    function setAccessDto($AccessDto){
    	$this->AccessDto = $AccessDto;
    }

    //************************************************************
    // getter
    //************************************************************



}
?>
