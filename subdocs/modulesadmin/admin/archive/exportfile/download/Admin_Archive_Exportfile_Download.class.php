<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_Download {
    var $session;

    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $DtoLog;
    var $MdbLog;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Downloadが実行されました","Admin_Archive_Exportfile_Download#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // アーカイブファイル情報
        if (!$this->MdbArchiveExport->setInfo($this->DtoArchiveExport)) {
            $errorList->add("Admin_Archive_Exportfile_Download::error", "エクスポートファイル情報の取得に失敗しました。");
            return "error";
        }
        // ファイル存在確認
        $file_path = EVI_ARCHIVE_EXPORT_PATH . "/" . $this->DtoArchiveExport->getArchive_export_id() . ".zip";
        if (!file_exists($file_path)) {
            $errorList->add("Admin_Archive_Exportfile_Download::error", "エクスポートファイルが存在しませんでした。");
            return "error";
        }

        // ファイル名の変換
        $browser = new CrossBrowser();
        $file_name = $browser->FilenameExchange($this->DtoArchiveExport->getFile_name());
        $browser->DownloadHeader($file_name, $this->DtoArchiveExport->getFile_size(), 'application/zip');
        //@readfile($file_path);
        FileUtil::freadfile($file_path);

        // Log
        $memo = '';
        $memo = 'ファイル名:'.$this->DtoArchiveExport->getFile_name();
        $history_id = 160; // 160:アーカイブシステム>エクスポートファイル一覧＞ダウンロード
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);        

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