<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Exportfile_Del {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportfile_Delが実行されました","Admin_Archive_Exportfile_Del#execute");

        // エクスポートファイル情報取得
        if (!$this->MdbArchiveExport->setInfo($this->DtoArchiveExport)) {
            return "error";
        }

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
    function getArchive_export_id() {
        return $this->DtoArchiveExport->getArchive_export_id();
    }
    function getFile_name() {
        return $this->DtoArchiveExport->getFile_name();
    }
    function getFile_size() {
        return $this->DtoArchiveExport->getFile_size();
    }
    function getFile_size_str() {
        return StrChange::CapacityUnit($this->DtoArchiveExport->getFile_size());
    }
    function getStart() {
        return $this->DtoArchiveExport->getStart();
    }
    function getEnd() {
        return $this->DtoArchiveExport->getEnd();
    }
    function getSearch_account() {
        return $this->DtoArchiveExport->getSearch_account();
    }
    function getSearch_user_name() {
        return $this->DtoArchiveExport->getSearch_user_name();
    }
    function getSearch_mailaddr() {
        return $this->DtoArchiveExport->getSearch_mailaddr();
    }
    function getSearch_file_name() {
        return $this->DtoArchiveExport->getSearch_file_name();
    }
    function getSearch_to_send() {
        return $this->DtoArchiveExport->getSearch_to_send();
    }
    function getSearch_to_collect() {
        return $this->DtoArchiveExport->getSearch_to_collect();
    }
    function getIn_dt() {
        return $this->DtoArchiveExport->getIn_dt();
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