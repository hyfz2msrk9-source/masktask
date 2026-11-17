<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Detail_Download {
    var $session;

    var $DtoArchiveFile;
    var $MdbArchiveFile;
    var $DtoLog;
    var $MdbLog;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Detail_Downloadが実行されました","Admin_Archive_Detail_Download#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // アーカイブファイル情報
        if (!$this->MdbArchiveFile->setInfo($this->DtoArchiveFile)) {
            $errorList->add("Admin_Archive_Detail_Download::error", "アーカイブファイル情報の取得に失敗しました。");
            return "error";
        }
        // ファイル存在確認
        $file_path = EVI_ARCHIVE_PATH . "/" . $this->DtoArchiveFile->getArchive_file_id() . ".archive";
        if (!file_exists($file_path)) {
            $errorList->add("Admin_Archive_Detail_Download::error", "アーカイブファイルが存在しませんでした。");
            return "error";
        }
        // ファイル名の変換
        $browser = new CrossBrowser();
        $file_name = $browser->FilenameExchange($this->DtoArchiveFile->getFile_name());
        $browser->DownloadHeader($file_name, $this->DtoArchiveFile->getFile_size(), $this->DtoArchiveFile->getFile_type());
        //@readfile($file_path);
        FileUtil::freadfile($file_path);

        // Log
        $memo = '';
        $memo = 'ファイル名:'.$this->DtoArchiveFile->getFile_name();
        $history_id = 159; // 159:アーカイブシステム＞詳細＞ダウンロード
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id( $this->DtoArchiveFile->getFile_id() );
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoArchiveFile($DtoArchiveFile) {
        $this->DtoArchiveFile = $DtoArchiveFile;
    }
    function setMdbArchiveFile($MdbArchiveFile) {
        $this->MdbArchiveFile = $MdbArchiveFile;
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