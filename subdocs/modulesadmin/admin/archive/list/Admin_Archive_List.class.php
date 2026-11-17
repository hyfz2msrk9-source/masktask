<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_List {
    var $session;

    var $DtoFlexi;
    var $DtoArchive;
    var $MdbArchive;
    var $AdminArchiveLogic;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Listが実行されました","Admin_Archive_List#execute");

        // 一覧取得
        echo $this->AdminArchiveLogic->getArchiveList($this->DtoFlexi, $this->DtoArchive, $this->MdbArchive);
        exit;
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setDtoArchive($DtoArchive) {
        $this->DtoArchive = $DtoArchive;
    }
    function setMdbArchive($MdbArchive) {
        $this->MdbArchive = $MdbArchive;
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