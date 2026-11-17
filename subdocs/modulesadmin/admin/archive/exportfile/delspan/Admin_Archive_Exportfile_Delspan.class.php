<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_Delspan {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;

    var $start;
    var $end;
    var $count;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Delspanが実行されました","Admin_Archive_Exportfile_Delspan#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // プロセス監視
        $filename = EVI_PID_PATH."/archive_del_pid";
        $lockname = $filename."_lock";
        if (file_exists($filename) || file_exists($lockname)) {
            $errorList->add("Admin_Archive_Exportfile_Delspan::error", "すでにエクスポートファイルの削除が実行されています。");
            return "error";
        }

        $start = date("Y-m-d 00:00:00", strtotime($this->DtoArchiveExport->getStart()));
        $end   = date("Y-m-d 23:59:59", strtotime($this->DtoArchiveExport->getEnd()));
        $this->count = $this->MdbArchiveExport->getDeleteFileCount($start, $end);
        if ($this->count <= 0) {
            $errorList->add("Admin_Archive_Exportfile_Delspan::error", "選択した日付には、削除可能なエクスポートファイルが存在しません。");
            return "error";
        }

        // セッションセット
        $this->session->setParameter("start", $start);
        $this->session->setParameter("end",   $end);

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

    function getSearchStartYear() {
        return $this->MdbArchiveExport->getSearchStartYear();
    }
    function getSearchEndYear() {
        return $this->MdbArchiveExport->getSearchEndYear();
    }
    function getStart() {
        return $this->DtoArchiveExport->getStart();
    }
    function getEnd() {
        return $this->DtoArchiveExport->getEnd();
    }
    function getCount() {
        return $this->count;
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