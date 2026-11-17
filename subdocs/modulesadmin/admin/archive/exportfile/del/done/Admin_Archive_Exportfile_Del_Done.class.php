<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_Del_Done {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;

    var $DtoLog;
    var $MdbLog;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Del_Doneが実行されました","Admin_Archive_Exportfile_Del_Done#execute");

        // エクスポートファイル情報取得
        if (!$this->MdbArchiveExport->setInfo($this->DtoArchiveExport)) {
            return "error";
        }
        $archive_export_id = $this->DtoArchiveExport->getArchive_export_id();

        // エクスポートファイル削除
        $data = array();
        $data['archive_export_id'] = $archive_export_id;
        $this->MdbArchiveExport->delete($data);
        @unlink(EVI_ARCHIVE_EXPORT_PATH . "/" . $archive_export_id . ".zip");

        // Log
        $memo = '';
        $memo.= $this->DtoArchiveExport->getFile_name();
        $history_id = 144; // 144:アーカイブシステム＞エクスポートファイル一覧＞エクスポートファイルの削除
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