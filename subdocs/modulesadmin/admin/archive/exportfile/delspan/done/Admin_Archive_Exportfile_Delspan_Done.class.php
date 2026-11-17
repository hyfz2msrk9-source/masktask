<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_Delspan_Done {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $ArchiveLogic;

    var $DtoLog;
    var $MdbLog;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Delspan_Doneが実行されました","Admin_Archive_Exportfile_Delspan_Done#execute");

        // 削除
        $start = $this->session->getParameter("start");
        $end   = $this->session->getParameter("end");
        $this->ArchiveLogic->deleteTermExport($start, $end);

        // Log
        $memo = '';
        $memo.= $start . " 〜 " . $end;
        $history_id = 145; // 145:アーカイブシステム＞エクスポートファイル一覧＞エクスポートファイルの期間指定削除
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoArchiveExport($DtoArchiveExport) {
        $this->DtoArchiveExport = $DtoArchiveExport;
    }
    function setMdbArchiveExport($MdbArchiveExport) {
        $this->MdbArchiveExport = $MdbArchiveExport;
    }
    function setArchiveLogic($ArchiveLogic) {
        $this->ArchiveLogic = $ArchiveLogic;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
}
/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>