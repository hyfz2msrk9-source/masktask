<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Import_Done {
    var $session;
    var $fileUpload;

    var $MdbUser;
    var $DtoDomain;
    var $MdbDomain;
    var $DomainLogic;
    var $MdbLog;
    var $DtoLog;

    // response
    var $count;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Import_Doneが実行されました","Admin_Config_Domain_Import_Done#execute");

        // インポート
        $ret = $this->DomainLogic->importDomain($this->MdbUser, $this->MdbDomain, $this->DtoDomain, $this->fileUpload);
        if ($ret === false) {
            return "upload";
        } else {
            $this->count = $ret;

            // Log
            $history_id = 158; // 各種設定＞ドメイン送信制限に関する設定＞CSV追加
            $memo = "";
            $memo.= "ファイル名：" . $this->fileUpload->getOriginalName()."\n";
            $memo.= "設定変更ドメイン：".$this->count."件";
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $id = $this->MdbLog->Logging($this->DtoLog);
        }

        return "success";
    }
    // setter
    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoDomain($DtoDomain) {
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
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
    function getCount() {
        return $this->count;
    }
}
?>