<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Export {

    var $session;
    var $MdbUser;

    var $DtoDomain;
    var $MdbDomain;
    var $DomainLogic;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Exportが実行されました","Admin_Config_Domain_Export#execute");

        $cnt = $this->DomainLogic->exportDomain($this->MdbUser, $this->MdbDomain);

        // Log
        $history_id = 157; // 各種設定＞ドメイン送信制限に関する設定＞エクスポート
        $memo = "";
        $memo.= "件数：" . $cnt . "件";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $id = $this->MdbLog->Logging($this->DtoLog);

        exit;
    }
    // setter
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoDomain($DtoDomain){
        $this->DtoDomain = $DtoDomain;
    }
    function setMdbDomain($MdbDomain) {
        $this->MdbDomain = $MdbDomain;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
    }
    function setDtoLog(&$obj) {
        $this->DtoLog = &$obj;
    }
    function setMdbLog(&$obj) {
        $this->MdbLog = &$obj;
    }
    function setSession($obj) {
        $this->session = &$obj;
    }

    // getter
}
?>