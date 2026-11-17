<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_List {
    var $session;
    var $DtoFlexi;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $AdminArchiveLogic;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Listが実行されました","Admin_Archive_Exportfile_List#execute");

        // エクスポートファイル一覧
        echo $this->AdminArchiveLogic->getArchiveExportfileList(
                                                                $this->DtoFlexi,
                                                                $this->DtoArchiveExport,
                                                                $this->MdbArchiveExport
                                                                );
        exit;
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setDtoArchiveExport($DtoArchiveExport) {
        $this->DtoArchiveExport = $DtoArchiveExport;
    }
    function setMdbArchiveExport($MdbArchiveExport) {
        $this->MdbArchiveExport = $MdbArchiveExport;
    }
    function setAdminArchiveLogic($AdminArchiveLogic) {
        $this->AdminArchiveLogic = $AdminArchiveLogic;
    }

    // getter
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