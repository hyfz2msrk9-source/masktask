<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfileが実行されました","Admin_Archive_Exportfile#execute");

        // セッションクリア
        $this->session->removeParameter("start");
        $this->session->removeParameter("end");

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

    // getter
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